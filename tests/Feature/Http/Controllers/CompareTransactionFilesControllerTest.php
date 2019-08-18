<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompareTransactionFilesControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function requiredUploadDataProvider()
    {
        $complete = [
            'csv_file1' => 'some_file.txt',
            'csv_file2' => 'other_file.txt',
        ];

        //csv_file1 not available
        $temp = $complete; unset($temp['csv_file1']);
        $not_f1 = $temp; $f1_resp = ['csv_file1' => ['The csv file1 field is required.']];

        //csv_file2 not available
        $temp = $complete; unset($temp['csv_file2']);
        $not_f2 = $temp; $f2_resp = ['csv_file2' => ['The csv file2 field is required.']];

        //invalid f1 mime type
        $temp = $complete; $temp['csv_file1'] = 'haha.png';
        $iv_f1 = $temp; $iv_f1_resp = ['csv_file1' => ['The csv file1 must be a file of type: csv, txt.']];

        //invalid f2 mime type
        $temp = $complete; $temp['csv_file2'] = 'haha.png';
        $iv_f2 = $temp; $iv_f2_resp = ['csv_file2' => ['The csv file2 must be a file of type: csv, txt.']];

        return [
            'File 1 missing' => [$not_f1, $f1_resp],
            'File 2 missing' => [$not_f2, $f2_resp],
            'Invalid F1 type' => [$iv_f1, $iv_f1_resp],
            'Invalid F2 type' => [$iv_f2, $iv_f2_resp],
        ];
    }

    /** @test
     * @dataProvider requiredUploadDataProvider
     */
    function required_parameters_in_order_to_compare_transaction_files($file_names, $expected_response)
    {
        Storage::fake('transactions');

        $user = factory(User::class)->create([
            'email' => 'me_working@tutuka.com'
        ]);

        $this->actingAs($user);

        $payload = [];

        foreach ($file_names as $key => $file) {
            $payload[$key] = UploadedFile::fake()->create($file);
        }

        $response = $this->json('POST', '/compare/transaction/files', $payload);

        $this->assertEquals(422, $response->getStatusCode());
        $decoded = json_decode($response->content(), true);
        foreach($expected_response as $name => $t) {
            $this->assertTrue(array_key_exists($name, $decoded['errors']));
            $this->assertEquals($decoded['errors'][$name][0], $t[0]);
        }
    }

    /** @test */
    function unauthenticated_user_cannot_attempt_transactions_comparison()
    {
        $payload = [
            'csv_file1' => 'some_file.txt',
            'csv_file2' => 'other_file.txt',
        ];

        $response = $this->json('POST', '/compare/transaction/files', $payload);

        $this->assertEquals(401, $response->getStatusCode());
    }
}
