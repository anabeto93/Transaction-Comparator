<?php

namespace Tests\Unit;

use App\Repositories\Transaction\TransactionComparatorRepository;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionComparatorRepositoryTest extends TestCase
{
    /** @test */
    function can_convert_csv_transaction_string_to_array()
    {
        $repo = new TransactionComparatorRepository();

        $transaction = "Card Campaign,2014-01-12 05:34:25,-10000,ENGEN TSOLAMOSESI         GABORONE      BW,DEDUCT,0584012056667274,1,P_NzUyMDI4NjRfMTM4NTM2NjE4OC44Njcy,";

        $arr = $repo->convertTransactionToArray($transaction);

        $this->assertTrue(is_array($arr));
        $this->assertTrue(count($arr) === 8);
    }

    /** @test */
    function can_find_the_difference_between_two_csv_transaction_strings()
    {
        $first = "Card Campaign,2014-01-11 23:28:11,-5000,CAPITAL BANK              MOGODITSHANE  BW,DEDUCT,0464011844938429,1,P_NzI0NjE1NzhfMTM4NzE4ODExOC43NTYy,";
        $second = "Card Campaign,2014-01-12 04:43:34,-15000,*DARY SOUTHRING           GABORONE      BW,DEDUCT,0384012170157788,1,P_NzIzNDk2ODZfMTM4NTY1OTU3My4yMDQ1,";
        $third = "Card Campaign,2014-01-12 04:43:34,-15000,*DARY SOUTHRING           GHANA      BW,DEDUCT,0384012170157788,1,P_NzIzNDk2ODZfMTM4NTY1OTU3My4yMDQ1,";


        $repo = new TransactionComparatorRepository();

        $result = $repo->findDifferenceInTransactions($first, $second);
        $this->assertTrue(is_array($result));
        //diff exists in specific columns, 2, 4, 6
        foreach($result as $i => $column) {
            if(in_array($i, [2,4,6])) {
                $this->assertIsArray($column);
            }
        }

        $result = $repo->findDifferenceInTransactions($third, $second);
        $this->assertTrue(is_array($result));
        //only 1 diff, GHANA
        foreach($result as $i => $column) {
            if($i === 4) {
                $this->assertIsArray($column);
            } else {
                $this->assertIsNotArray($column);
            }
        }
    }
}
