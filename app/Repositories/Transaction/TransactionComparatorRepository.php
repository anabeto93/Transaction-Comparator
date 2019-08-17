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

        //get equal contents of both files
        $equal = false;
        if(count($file_contents[0]) === count($file_contents[1])) {
            $equal = true;
        }

        $primary_file = null;
        $secondary_file = null;
        $larger_file_index = null;

        if(!$equal) {
            if(count($file_contents[1]) > count($file_contents[0])) {
                $eq_size = array_slice($file_contents[1], 0,count($file_contents[0]));
                $remnant = array_slice($file_contents[1], count($file_contents[0]) - count($file_contents[1]));
                $smaller = $secondary_file = $file_contents[0];
                $primary_file = $file_contents[1];
                $larger_file_index = 1;
            }else {
                $eq_size = array_slice($file_contents[0], 0,count($file_contents[1]));
                $remnant = array_slice($file_contents[0], count($file_contents[1]) - count($file_contents[0]));
                $smaller = $secondary_file = $file_contents[1];
                $primary_file = $file_contents[0];
                $larger_file_index = 0;
            }

            //merge the two arrays to get the final differences between the two files
            $differences = array_merge(array_values(array_diff_assoc($eq_size, $smaller)), array_values($remnant));

            //the unmatched differences
            foreach($result as $key => $file) {
                $index = $key === 'file1' ? 0: 1;

                $result[$key]['total'] = count($file_contents[$index]);//accurate for either file

                if($larger_file_index === $index) {
                    //dealing with the larger file
                    $result[$key]['matching'] = intval(count($file_contents[$index]) - count($differences));
                    $result[$key]['unmatched'] = count($differences);
                }else {
                    //dealing with the smaller file
                    $result[$key]['matching'] = intval(count($smaller) - count(array_diff_assoc($eq_size, $smaller)));
                    $result[$key]['unmatched'] = count(array_diff_assoc($eq_size, $smaller));
                }
            }
        }else {
            //find the differences between the two equal file contents
            $differences = array_diff_assoc($file_contents[0], $file_contents[1]);

            //the unmatched differences
            foreach($result as $key => $file) {
                $index = $key === 'file1' ? 0: 1;
                $result[$key]['total'] = count($file_contents[$index]);
                $result[$key]['matching'] = intval(count($file_contents[$index]) - count($differences));
                $result[$key]['unmatched'] = count($differences);
            }
            //either can be the primary and secondary when they are equal in size
            $primary_file = $file_contents[0];
            $secondary_file = $file_contents[1];
            //either can serve as large_file_index
            $larger_file_index = 0;
        }

        if(count($differences) > 0) {
            //there are differences that need to be sorted out
            foreach ($differences as $pkey => $current_transaction) {
                //try finding the most similar or closest transaction
                $similarities = [
                    'similarity' => 0,//assume initially no similarity with any
                    'index' => 0,
                ];

                //run it against each other transaction in the secondary file
                foreach ($secondary_file as $skey => $secondary_transaction) {
                    $temp = $this->findDifferenceInTransactions($current_transaction, $secondary_transaction);
                    $new_sim = $this->computeSimilarity($temp);

                    if($new_sim > $similarities['similarity']) {
                        //set it as the new most similar transaction
                        $similarities['similarity'] = $new_sim;
                        $similarities['index'] = $skey;
                    }
                }

                //generate report and proceed
                $r = $this->generateReportForTransactions($similarities['similarity'], $current_transaction, $secondary_file[$similarities['index']]);
                $r['temp'] = $temp;

                if($larger_file_index === 0) {
                    array_push($reports['file1'], $r);
                } else {
                    array_push($reports['file2'], $r);
                }
            }
        }

        //Delete the files from storage before moving on
        foreach($names as $name) {
            Storage::disk('local')->delete($name);
        }

        $reports = $this->cleanUpTheFinalReportForBothFiles($reports, $differences, $file_contents[1]);

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

    /**
     * In the format of the transaction report
     * @param $percentage
     * @param $first_transaction
     * @param $second_transaction
     * @return array
     */
    protected function generateReportForTransactions($percentage, $first_transaction, $second_transaction): array
    {
        /* Each report should be an array with the following details
                [
                    'date' => $date,
                    'ref' => $ref,
                    'amount' => $amt,
                    'advice' => 'Similar to what transaction'
                ]
         * */
        //generate the report for the current transaction
        $c_t = $this->convertTransactionToArray($first_transaction);
        $s_t = $this->convertTransactionToArray($second_transaction);
        $r = [
            'date' => $c_t[1],
            'reference' => $c_t[5], //which is the transaction_id
            'amount' => $c_t[2],
            'advice' => 'None',
        ];

        if($percentage !== 0 && $percentage > 50) {//above 50 is good enough
            $r['advice'] = $s_t[5];
        }

        return $r;
    }

    /**
     * @param array $reports
     * @param array $differences
     * @param array $second_file_contents
     * @return array
     */
    protected function cleanUpTheFinalReportForBothFiles($reports, $differences, $second_file_contents): array
    {
        //Assumption here is, some other transaction in file1 must have an advice that references the current transaction
        foreach (array_keys($differences) as $ikey => $array_key) {
            try {
                $current = $second_file_contents[$array_key];
            }catch (\Exception $e) {
                //no matching transaction
                $reports['file2'][$ikey] = [
                    'data' => null,
                    'reference' => null,
                    'amount' => null,
                    'advice' => 'None'
                ];

                continue;
            }
            //find the one whose advice is the reference of the current transaction
            $c_t = $this->convertTransactionToArray($current);

            $found = false;
            $index = 0;

            do{
                $report = $reports['file1'][$index++];

                if( $report['advice'] === $c_t[5]) {
                    $found = true;
                    //change both advices
                    $ref = 'REF: '.$report['reference'].' ';

                    //in order to be fancy, find the keys or indices where they differ
                    $differs= 'DIFF_BY: '; $column_names = ['Profile','Date','Amount','Narrative','Description','ID', 'Type', 'WalletReference'];
                    foreach($report['temp'] as $k => $t) {
                        if(is_array($t)) {
                            $differs .= $column_names[$k-1].',';
                        }
                    }

                    $final_ref = $ref.$differs;

                    $reports['file2'][$ikey] = [
                        'date' => $c_t[1],
                        'reference' => $c_t[5],
                        'amount' => $c_t[2],
                        'advice' => $final_ref
                    ];


                    //modify the original report
                    unset($reports['file1'][$index-1]['temp']);
                    $reports['file1'][$index-1]['advice'] = 'REF: '.$reports['file1'][$index-1]['advice'].' '.$differs;
                }
            }while(!$found && $index < count($reports['file1']));
        }

        return $reports;
    }


    /*
        Paul's Simple Diff Algorithm v 0.1
        (C) Paul Butler 2007 <http://www.paulbutler.org/>
        //No need reinventing the wheel here, slight modifications to work here
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