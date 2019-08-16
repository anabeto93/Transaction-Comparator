<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(!app()->environment(['staging','production'])) {
            //only seed on any other environment except on test or production servers
            factory(App\User::class, 1)->create([
                'email' => 'richard@tutuka.com',
                'password' => bcrypt('default1234$'),
            ]);
        }
    }
}
