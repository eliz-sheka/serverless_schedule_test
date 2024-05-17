<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;

class CSVReader
{
    /**
     * @param string $path
     * @param string $delimiter
     * @param int $headerOffset
     * @return Reader
     * @throws \League\Csv\Exception
     * @throws \League\Csv\InvalidArgument
     */
    public function createFromStream(string $path, string $delimiter = ',', int $headerOffset = 0): Reader
    {
        if (!Storage::disk('s3')->exists($path)) {
            throw new \RuntimeException('File not found');
        }

        $stream = Storage::disk('s3')->readStream($path);
        $csv = Reader::createFromStream($stream);

        $csv->setDelimiter($delimiter);
        $csv->setHeaderOffset($headerOffset);

        return $csv;
    }

    /**
     * @param Reader $csv
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws \League\Csv\Exception
     * @throws \League\Csv\InvalidArgument
     * @throws \League\Csv\SyntaxError
     */
    public function getCsvRecords(Reader $csv, int $offset, int $limit): array
    {
        $stmt = (new Statement())->offset($offset)->limit($limit);
        $records = $stmt->process($csv);

        return iterator_to_array($records);
    }
}
