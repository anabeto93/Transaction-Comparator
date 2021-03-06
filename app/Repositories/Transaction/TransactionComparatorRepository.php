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

        //Step 2. Ensure there are no duplicate transactions on the same file in either file
        $duplicate_transactions = [
            'file1' => [],
            'file2' => [],
        ];

        foreach($file_contents as $key => $file) {
            $i = $key === 0 ? 'file1' : 'file2';

            $duplicates = array_diff_assoc($file, array_unique(array_values($file)));

            //remove the duplicated transactions and report on them
            foreach($duplicates as $duplicate_line => $duplicate) {
                unset($file_contents[$key][$duplicate_line]);//remove the duplicated transactions

                //report on the duplicate transactions as well
                $r = $this->generateReportForTransactions(100.00, $duplicate, $duplicate);
                $r['advice'] = 'Duplicate Transaction on Same file.';
                array_push($duplicate_transactions[$i], $duplicate);

                array_push($reports, $r);
            }
        }

        //form the two groups of remnants to test against
        $group_one = $file_contents[0];
        $group_two = $file_contents[1];

        //compare each transaction from group_one against those in group_two, starting from the top, do this n times where n=size_of($group_one)
        $keys_to_unset = [];
        foreach($group_one as $ik => $transaction) {
            foreach($group_two as $j => $other_transaction) {
                //going by the assumption that transactions match 'perfectly' find the exact match
                if($transaction === $other_transaction) {
                    //a match has been found, now remove them from both groups to reduce the sample set
                    array_push($keys_to_unset, $ik);
                    unset($group_two[$j]);
                    $group_two =  array_values($group_two);//reduce the size of group_two
                }
            }
        }

        foreach($keys_to_unset as $nk => $key) {
            unset($group_one[$key]);
        }

        //remnants after removing all
        $group_one = array_merge(array_values($duplicate_transactions['file1']), array_values($group_one));

        $group_two = array_merge(array_values($duplicate_transactions['file2']), array_values($group_two));

        $result = [
            'file1' => [
                'total' => count($file_contents[0]) + count($duplicate_transactions['file1']),
                'matching' => count($file_contents[0]) + count($duplicate_transactions['file1']) - count($group_one),
                'unmatched' => count($group_one)
            ],
            'file2' => [
                'total' => count($file_contents[1]) + count($duplicate_transactions['file2']),
                'matching' => count($file_contents[1])+ count($duplicate_transactions['file2']) - count($group_two),
                'unmatched' => count($group_two)
            ]
        ];

        //NB: This section below is not really necessary if one of the remnant groups is empty
        //find the similarities and where there are possibilities of matching
        foreach([$group_one, $group_two] as $i => $group) {
            if($i===0) {
                $index = 1; $current_file = 'file1';
            } else {
                $index = 0; $current_file = 'file2';
            }

            if(count($group) <= 0) {
                //create the report and move on
                $reports[$current_file] = [
                    //no report to give here
                ];

                continue;
            }

            //the remnant here will be checking against the other group to see possible matches
            $other_group = $file_contents[$index];

            //compare each member of the current group against the members of the other_group
            foreach($group as $j => $member) {
                //current similarities
                $similarity = [
                    'index' => 0,
                    'percentage' => 0
                ];
                foreach($other_group as $k => $transaction) { //This level of nesting is pretty bad
                    $temp = $this->findDifferenceInTransactions($member, $transaction);
                    $new_sim = $this->computeSimilarity($temp);

                    //check for the highest and most similar transaction
                    if($new_sim > $similarity['percentage']) {
                        $similarity['percentage'] = $new_sim;
                        $similarity['index'] = $k;
                    }
                }

                //ensure that the highest percentage obtained is above 80% else it ain't worth it
                if($similarity['percentage'] > 80) {
                    $r = $this->generateReportForTransactions($similarity['percentage'],$member, $other_group[$similarity['index']]);

                    array_push($reports[$current_file], $r);
                }else {
                    $c_t = $this->convertTransactionToArray($member);

                    $r = [
                        'date' => $c_t[1],
                        'reference' => $c_t[5], //which is the transaction_id
                        'amount' => $c_t[2],
                        'advice' => 'Matches None',
                    ];

                    array_push($reports[$current_file], $r);
                }
            }
        }

        //Delete the files from storage before moving on
        foreach($names as $name) {
            Storage::disk('local')->delete('transactions/'.$name);
        }

        return ['results' => $result, 'reports' => $reports, 'names' => $original_names];

    }

    /**
     * Convert the CSV transaction string to a sensible array
     * @param string $transaction
     * @return array
     */
    public function convertTransactionToArray($transaction): array
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
    public function findDifferenceInTransactions($first, $second): array
    {
        $transaction_one = $this->convertTransactionToArray($first);
        $transaction_two = $this->convertTransactionToArray($second);

        $diffTest = diff_in_arrays($transaction_one, $transaction_two);
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
            'advice' => 'Matches None',
        ];

        if($percentage !== 0 && $percentage > 50) {//above 50 is good enough
            $r['advice'] = $s_t[5];
        }

        return $r;
    }
}
