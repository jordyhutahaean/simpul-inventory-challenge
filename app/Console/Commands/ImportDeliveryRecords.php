<?php

namespace App\Console\Commands;

use App\Models\DeliveryRecord;
use App\Models\Warehouse;
use Illuminate\Console\Command;

class ImportDeliveryRecords extends Command
{
    protected $signature = 'deliveries:import {path=database/data/customer_1_history.csv}';
    protected $description = 'Import historical delivery records from CSV';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));
        if (!is_file($path)) {
            $this->error("File not found: $path");
            return self::FAILURE;
        }

        // The source file is Mac Roman with CR-only line endings.
        $text = preg_replace('/[^\x20-\x7E\r\n]/', '', file_get_contents($path));
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $rows = array_map('str_getcsv', array_filter(explode("\n", $text)));
        array_shift($rows); // header

        $days = ['Mon' => 0, 'Tue' => 1, 'Wed' => 2, 'Thu' => 3, 'Fri' => 4, 'Sat' => 5, 'Sun' => 6];
        $wh = Warehouse::pluck('id', 'code');
        $count = 0;

        foreach ($rows as [$ref, $code, $dayTime, $minutes, $notes]) {
            [$day, $time] = array_map('trim', explode(',', $dayTime));
            [$h, $m] = explode(':', $time);
            $notes = trim($notes ?? '');

            DeliveryRecord::updateOrCreate(['reference' => $ref], [
                'warehouse_id'     => $wh[$code],
                'day_of_week'      => $days[$day],
                'hour'             => (int) $h + ((int) $m / 60),
                'duration_minutes' => (int) $minutes,
                'notes'            => in_array($notes, ['', '-', '–', '—'], true) ? null : $notes,
            ]);
            $count++;
        }

        $this->info("Imported $count records.");
        return self::SUCCESS;
    }
}