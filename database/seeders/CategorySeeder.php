<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Novela', 'Infantil', 'Técnico', 'Poesía', 'Biografía'];

        foreach ($categories as $name) {
            DB::table('library.category')->insert([
                'id' => Str::uuid(),
                'name' => $name,
            ]);
        }
    }
}
