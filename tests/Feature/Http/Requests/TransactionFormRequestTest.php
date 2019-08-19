<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\TransactionFormRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionFormRequestTest extends TestCase
{
    use DatabaseMigrations;

    public function authenticatedUserProvider()
    {
        return [
            'Not authenticated' => [false],
            'Authenticated User' => [true],
        ];
    }

    /** @test
     * @dataProvider authenticatedUserProvider
     */
    function unauthorized_user_will_not_be_validated($authenticated)
    {
        $user = factory(User::class)->create([
            'email' => 'richard@tutuka.com'
        ]);

        $this->assertTrue($user instanceof User);
        //not authenticated but exists
        $request = new TransactionFormRequest();

        if($authenticated) {
            Auth::login($user);

            $this->assertTrue($request->authorize());
        } else {

            $this->assertFalse($request->authorize());
        }
    }

    public function invalidFileProvider()
    {
        //what is a valid transaction file?
        //It has 7 columns which are of the following....
        $headers = [
            "ProfileName",
            "TransactionDate",
            "TransactionAmount",
            "TransactionNarrative",
            "TransactionDescription",
            "TransactionID",
            "TransactionType",
            "WalletReference",
        ];
        //assuming that is the case, enforce the above rules

        //randomly remove 1 or more of the headers and attempt submitting it

        $provider = [];
        foreach ($headers as $key => $header) {
            $temp = $headers; unset($temp[$key]);
            array_push($provider, $temp);
            foreach($headers as $i => $n_header) {
                if($i <= $key) {
                    continue;
                }

                unset($temp[$i]);
                if(count($temp) > 0) {
                    array_push($provider, $temp);
                }
            }
        }

        return $provider;
    }

    /**
     * @test
     * @dataProvider invalidFileProvider
     */
    function invalid_file_cannot_be_submitted($payload)
    {
        //fake storage
        Storage::fake('transactions');

        $user = factory(User::class)->create([
            'email' => 'richard@tutuka.com'
        ]);

        $this->assertTrue($user instanceof User);
        $this->actingAs($user);

        $file_names = [
            'csv_file1' => 'file1.csv',
            'csv_file2' => 'file2.csv'
        ];

        $data = [];

        foreach ($file_names as $key => $file) {
            //write something fake to that file
            if(is_string($payload)) {
                $payload = [$payload];
            }

            $line = implode(',', $payload);
            Storage::disk('local')->put($key, $line);//as if it already existed

            $data[$key] = UploadedFile::fake()->create($file, 12);
        }

        $response = $this->json('POST', '/compare/transaction/files', $data);

        $this->assertEquals(422, $response->getStatusCode());

        $this->assertContains('Invalid Transaction CSV file passed.', json_encode(json_decode($response->content(), true)['errors']));

        foreach($file_names as $key => $file) {
            Storage::disk('local')->delete($key);
        }
    }
}
