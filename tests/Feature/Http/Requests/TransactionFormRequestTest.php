<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\TransactionFormRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
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
}
