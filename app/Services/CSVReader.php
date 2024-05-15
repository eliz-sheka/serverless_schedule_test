<?php

namespace App\Services;

use Generator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;

class CSVReader
{
    private Reader $csv;

    public function __construct(string $path, string $delimiter = ',', int $headerOffset = 0)
    {
        if (!Storage::disk('s3')->exists($path)) {
            throw new \RuntimeException('File not found');
        }

        $stream = Storage::disk('s3')->readStream($path);
        $this->csv = Reader::createFromStream($stream);
        $this->csv->setDelimiter($delimiter);
        $this->csv->setHeaderOffset($headerOffset);
    }

    /**
     * @return int
     * @throws \League\Csv\Exception
     */
    public function countTotal(): int
    {
        return $this->csv->count();
    }

    /**
     * @param $offset
     * @param $limit
     * @return array
     * @throws \League\Csv\Exception
     * @throws \League\Csv\InvalidArgument
     * @throws \League\Csv\SyntaxError
     */
    public function getCsvRecords($offset, $limit): array
    {
        $stmt = (new Statement())->offset($offset)->limit($limit);
        $records = $stmt->process($this->csv);

        return iterator_to_array($records);
    }
}
