<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/orders.csv');
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('The Harbor Desk orders CSV could not be opened.');
        }

        try {
            $headers = fgetcsv($handle, escape: '');

            if ($headers !== ['id', 'customer', 'city', 'status', 'total']) {
                throw new RuntimeException('The Harbor Desk orders CSV has an invalid header.');
            }

            $orders = [];

            while (($values = fgetcsv($handle, escape: '')) !== false) {
                if (count($values) !== 5) {
                    continue;
                }

                $orders[] = [
                    'id' => (int) ($values[0] ?? 0),
                    'customer' => $values[1] ?? '',
                    'city' => $values[2] ?? '',
                    'status' => $values[3] ?? '',
                    'total' => $values[4] ?? '',
                ];
            }
        } finally {
            fclose($handle);
        }

        DB::table('orders')->upsert(
            $orders,
            ['id'],
            ['customer', 'city', 'status', 'total'],
        );
    }
}
