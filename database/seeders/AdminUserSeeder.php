<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'John Alfred',
                'email' => 'techevo404@gmail.com',
                'password' => 'amazingGrace26',
            ],
            [
                'name' => 'Samuel Ziro',
                'email' => 'samuelziro76@gmail.com',
                'password' => 'samuelziro76?',
            ],
            [
                'name' => 'Victor Chitole',
                'email' => 'victorchitole@gmail.com',
                'password' => 'victor504Eror??',
            ],
            [
                'name' => 'Jeremiah Katumo',
                'email' => 'jeremykatush@gmail.com',
                'password' => 'jeremykatush???',
            ],
        ];

        foreach ($admins as $adminData) {
            // normalize email (trim + lowercase)
            $email = trim(strtolower($adminData['email']));

            // build attributes to update/create
            $attributes = [
                'name'     => $adminData['name'],
                'is_admin' => true,
            ];

            // only set password if provided and non-empty
            if (!empty($adminData['password'])) {
                $attributes['password'] = Hash::make($adminData['password']);
            }

            $user = User::where('email', $email)->first();

            if ($user) {
                // update existing admin
                $user->update($attributes);
                $this->command->info("Updated admin: {$email}");
            } else {
                // create new admin
                $attributes['email'] = $email;
                User::create($attributes);
                $this->command->info("Created admin: {$email}");
            }
        }
    }
}
