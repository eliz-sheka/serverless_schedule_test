<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCSVCommand extends Command
{
    protected $signature = 'import:csv {filename}';
    protected $description = 'Import CSV data into the database';

    public function handle()
    {
        $filename = $this->argument('filename');
        $generator = $this->readCSVRows($filename);

        $chunkSize = 1000;

        foreach ($this->chunkGenerator($generator, $chunkSize) as $chunk) {
            $data[] = $chunk;
        }

        $this->info('CSV data imported successfully.');
    }

    private function readCSVRows($filename): \Generator
    {
        if (!file_exists($filename)) {
            throw new \Exception("File $filename not found.");
        }

        $file = fopen($filename, 'r');

        // Skip header row
        fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            yield [
                'date' => $row[0],
                'start_time' => $row[1],
                'end_time' => $row[2],
                'type' => $row[3],
            ];
        }

        fclose($file);
    }

    private function chunkGenerator($generator, $chunkSize): \Generator
    {
        $chunk = [];

        foreach ($generator as $row) {
            $chunk[] = $row;

            if (count($chunk) === $chunkSize) {
                yield $chunk;
                $chunk = [];
            }
        }

        if (count($chunk) > 0) {
            yield $chunk;
        }
    }
}
