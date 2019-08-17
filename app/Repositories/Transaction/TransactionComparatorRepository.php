<?php

namespace App\Repositories\Transaction;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TransactionComparatorRepository implements TransactionComparatorInterface
{
    public function compare(Request $request): array
    {
        //The returned response must contain three values, results, reports and names
        $result = [
            'file1' => [
                'total' => 0,
                'matching' => 0,
                'unmatched' => 0,
            ],
            'file2' => [
                'total' => 0,
                'matching' => 0,
                'unmatched' => 0,
            ],
        ];

        $reports = [
            'file1' => [
                /* Each report should be an array with the following details
                [
                    'date' => $date,
                    'ref' => $ref,
                    'amount' => $amt,
                    'advice' => 'Similar to what by what percentage'
                ]
                 * */
            ],
            'file2' => [

            ]
        ];

        $original_names = [];

        //Step1. store the files first
        $names = [];
        foreach(['csv_file1','csv_file2'] as $k => $file) {
            $name = now()->timestamp.'_'.$file.'.csv';
            $request->$file->storeAs('transactions', $name);
            array_push($names, $name);

            $i = $k === 0 ? 'file1': 'file2';
            $original_names[$i] = $request->file($file)->getClientOriginalName();
        }

        $file_contents = [];
        foreach($names as $key => $filename) {
            $path = storage_path('app/transactions/'.$filename);

            $content = array_slice(file($path, FILE_IGNORE_NEW_LINES), 1);//ignore the first line

            $file_contents[$key] = [];
            //move through in chunks
            foreach(array_chunk($content, 200) as $transaction) {
                $file_contents[$key] = array_merge($file_contents[$key], $transaction);
            }
        }

        //find the differences between the two files
        $differences = array_diff_assoc($file_contents[0], $file_contents[1]);

        //the unmatched differences
        foreach($result as $key => $file) {
            $index = $key === 'file1' ? 0: 1;
            $result[$key]['total'] = count($file_contents[$index]);
            $result[$key]['matching'] = intval(count($file_contents[$index]) - count($differences));
            $result[$key]['unmatched'] = count($differences);
        }

        if(count($differences) > 0) {
            //there are differences that need to be sorted out
            foreach($differences as $index => $diff) {
                //take each diff in first file against each and compare against all the differences in file2
                $current_transaction = $file_contents[0][$index];

                $similarities = [
                    'similarity' => 0,
                    'index' => 0
                ];//all possible similarities
                foreach(array_keys($differences) as $key => $array_key) {
                    if(array_key_exists($array_key, $file_contents[1])) {
                        $temp = $this->findDifferenceInTransactions($current_transaction, $file_contents[1][$array_key]);
                        $new_sim = $this->computeSimilarity($temp);

                        if($new_sim > $similarities['similarity']) {
                            //set it as the new most similar transaction
                            $similarities['similarity'] = $new_sim;
                            $similarities['index'] = $array_key;
                        }
                    }
                }

                //generate the report for the current transaction
                $c_t = $this->convertTransactionToArray($current_transaction);
                $s_t = $this->convertTransactionToArray($file_contents[1][$similarities['index']]);
                $r = [
                    'date' => $c_t[1],
                    'reference' => $c_t[5], //which is the transaction_id
                    'amount' => $c_t[2],
                    'advice' => 'No similarity above 50%',
                ];

                if($similarities['similarity'] !== 0 && $similarities['similarity'] > 50) {//above 50 is good enough
                    $r['advice'] = 'Similarity found. Reference: '.$s_t[5].'. Percentage: '.$similarities['similarity'];
                }

                array_push($reports, $r);
                dd($reports);
            }
            //Use File1 as reference against File2
        }

        //Delete the files from storage before moving on
        foreach($names as $name) {
            Storage::disk('local')->delete($name);
        }


        return ['results' => $result, 'reports' => $reports, 'names' => $original_names];

    }

    /**
     * Convert the CSV transaction string to a sensible array
     * @param string $transaction
     * @return array
     */
    protected function convertTransactionToArray($transaction): array
    {
        $result = explode(',', $transaction);
        //remove the first and last parts which might be empty
        //unset the first part which will be empty
        if(empty($result[0]) || trim($result[0]) == '') {
            unset($result[0]);
        }
        //unset the last index as well that may be empty
        $li = count($result) -1; //last index
        if(empty($result[$li]) || trim($result[$li]) == '') {
            unset($result[$li]);
        }

        return $result;
    }

    /**
     * @param string $first
     * @param string $second
     * @return array
     */
    protected function findDifferenceInTransactions($first, $second): array
    {
        $transaction_one = $this->convertTransactionToArray($first);
        $transaction_two = $this->convertTransactionToArray($second);

        $diffTest = $this->diff($transaction_one, $transaction_two);
        unset($diffTest[0]); unset($diffTest[count($transaction_one)+1]);

        return $diffTest;
    }

    /** Advice on the differences found in two transactions
     * @param array $difference
     * @return float
     */
    protected function computeSimilarity($difference): float
    {
        $size = count($difference);

        $diffSize = 0;
        foreach ($difference as $d) {
            if(is_array($d)) $diffSize++;
        }

        //compute the percentage difference
        return (100.0 - (float) ($diffSize / $size * 100.0));
    }


    /*
        Paul's Simple Diff Algorithm v 0.1
        (C) Paul Butler 2007 <http://www.paulbutler.org/>
        //No need reinventing the wheel here
    */
    protected function diff($old, $new){
        $matrix = array();
        $maxlen = 0;
        foreach($old as $oindex => $ovalue){
            $nkeys = array_keys($new, $ovalue);
            foreach($nkeys as $nindex){
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if($matrix[$oindex][$nindex] > $maxlen){
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
        return array_merge(
            $this->diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            $this->diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
    }
}