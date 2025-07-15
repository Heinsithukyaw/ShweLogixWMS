<?php

namespace Database\Seeders\BaseData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BaseUom;

class BaseUOMSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $datas = [
            [
                'short_code' => 'PC',
                'name' => 'Piece',
            ],
            [
                'short_code' => 'BX',
                'name' => 'Box',
            ],
            [
                'short_code' => 'CRT',
                'name' => 'Carton',
            ],
            [
                'short_code' => 'PLT',
                'name' => 'Pallet',
            ],
            [
                'short_code' => 'KG',
                'name' => 'Kilogram',
            ],
            [
                'short_code' => 'GM',
                'name' => 'Gram',
            ],
            [
                'short_code' => 'LTR',
                'name' => 'Liter',
            ],
            [
                'short_code' => 'ML',
                'name' => 'Militer',
            ],
        ];

        foreach ($datas as $data) {
            BaseUom::create($data);
        }
    }
}
