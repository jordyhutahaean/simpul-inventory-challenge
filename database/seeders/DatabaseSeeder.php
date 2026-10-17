<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
public function run(): void
    {
        $products = [
            ['P001', 'Surgical Gloves (Box of 100)',           'Medical Supplies', false],
            ['P002', 'Vaccine - Hepatitis B (10-dose vial)',    'Pharmaceutical',   true],
            ['P003', 'Disposable Syringe (Box of 50)',          'Medical Supplies', false],
            ['P004', 'Vaccine - Tetanus Toxoid (10-dose vial)', 'Pharmaceutical',   true],
            ['P005', 'Paracetamol Tablets (Box of 100)',        'Pharmaceutical',   false],
        ];
        foreach ($products as [$sku, $name, $cat, $cold]) {
            Product::create([
                'sku' => $sku, 'name' => $name,
                'category' => $cat, 'requires_cold_chain' => $cold,
            ]);
        }

        // code, city, km, fridge storage, fridge vans
        $warehouses = [
            ['W01', 'Surabaya', 5,   false, false],
            ['W02', 'Surabaya', 12,  true,  false],
            ['W03', 'Sidoarjo', 25,  false, false],
            ['W04', 'Gresik',   30,  true,  true],
            ['W05', 'Malang',   90,  false, false],
            ['W06', 'Malang',   95,  true,  false],
            ['W07', 'Pasuruan', 45,  true,  true],
            ['W08', 'Jember',   150, true,  true],
        ];
        foreach ($warehouses as [$code, $city, $km, $storage, $vans]) {
            Warehouse::create([
                'code' => $code, 'name' => "$code - $city", 'location' => $city,
                'distance_km' => $km,
                'has_refrigerated_storage' => $storage,
                'has_refrigerated_vans' => $vans,
            ]);
        }

        // warehouse code => [sku => quantity]
        $stock = [
            'W01' => ['P001' => 200, 'P003' => 500, 'P005' => 300],
            'W02' => ['P001' => 50,  'P002' => 30,  'P004' => 20],
            'W03' => ['P001' => 300, 'P003' => 200],
            'W04' => ['P002' => 40,  'P004' => 25,  'P005' => 100],
            'W05' => ['P001' => 400, 'P003' => 300, 'P005' => 500],
            'W06' => ['P002' => 20,  'P004' => 30,  'P003' => 100],
            'W07' => ['P002' => 8,   'P004' => 10,  'P001' => 150],
            'W08' => ['P002' => 60,  'P004' => 50,  'P005' => 200],
        ];

        $wh = Warehouse::pluck('id', 'code');
        $pr = Product::pluck('id', 'sku');

        foreach ($stock as $code => $items) {
            foreach ($items as $sku => $qty) {
                Inventory::create([
                    'warehouse_id' => $wh[$code],
                    'product_id'   => $pr[$sku],
                    'quantity'     => $qty,
                ]);
            }
        }
    }
}
