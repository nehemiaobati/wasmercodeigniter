<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'username' => 'admin',
            'email'    => 'admin@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT), // Replace with a strong password
            'is_admin' => true,
            'is_verified' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'balance' => '0.00',
            'verification_token' => null,
            'reset_token' => null,
            'reset_expires' => null,
        ];

        // Insert the admin user into the database
        $this->db->table('users')->insert($data);
    }
}

