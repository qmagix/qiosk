<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\InvitationCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the initial invitation code for the first admin
        InvitationCode::create([
            'code' => '86AngelsAdmin',
            'is_used' => false,
            'created_by' => null, // System generated
        ]);
    }
}
