<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = 'techevo404@gmail.com';
        if (User::where('email', $email)->exists()) {
            $this->command->info("Admin already exists: {$email}");
            return;
        }

        User::create([
            'name' => 'Resolute_Admin',
            'email' => $email,
            'password' => bcrypt(value: 'aliceSecret'), // change after first login
            'is_admin' => true,
        ]);

        $this->command->info("Created admin: {$email} / aliceSecret");
    }
}
