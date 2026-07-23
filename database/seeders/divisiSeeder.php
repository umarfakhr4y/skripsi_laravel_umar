<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Divisi;

class divisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Divisi::create([
            'nama_divisi' => 'IT Development'
        ]);
        Divisi::create([
            'nama_divisi' => 'Digital Marketing'
        ]);
        Divisi::create([
            'nama_divisi' => 'Content creator'
        ]);
    }
}
