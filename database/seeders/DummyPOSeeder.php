<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyPOSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('purchase_orders')->insert([
            'id' => 'dummy-001',
            'supplier_id' => 'dummy-001',
            'number' => 'PO-DUMMY',
            'po_date' => null,
            'delivery_date' => null,
            'status' => 1
        ]);
    }
}
