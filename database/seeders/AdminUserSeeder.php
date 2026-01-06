<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        
        // Create admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@colivraison.express'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'safe_mode' => 0,
            ]
        );
        
        // Assign admin role to user
        $adminUser->assignRole($adminRole);
        
        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@colivraison.express');
        $this->command->info('Password: admin123');
    }
}
