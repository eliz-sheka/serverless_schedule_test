<?php

namespace App\Services;

use Generator;
use Illuminate\Support\Facades\Storage;

class CSVReader
{
    /**
     * @param string $path
     * @param bool $skipHeader
     * @return Generator
     */
    public static function readRows(string $path, bool $skipHeader = true): Generator
    {

        if (!Storage::disk('s3')->exists($path)) {
            throw new \RuntimeException('File not found');
        }

        $stream = Storage::disk('s3')->readStream($path);

        while (!feof($stream)) {
            if ($skipHeader) {
                // Skip the first header row
                $skipHeader = false;
                continue;
            }

            $line = fgets($stream);
            $row = str_getcsv($line);

            yield $row;
        }

        fclose($stream);
    }

    /**
     * @param $generator
     * @param $chunkSize
     * @return Generator
     */
    public static function chunkGenerator($generator, $chunkSize): Generator
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
