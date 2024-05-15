<?php

namespace App\Handlers;

use App\Jobs\CreateMeetings;
use App\Models\User;
use App\Services\CSVReader;
use Bref\Context\Context;
use Bref\Event\Sns\SnsEvent;
use Bref\Event\Sns\SnsHandler as BrefSnsHandler;
use Illuminate\Support\Facades\Log;

class UserScheduleSNSHandler extends BrefSnsHandler
{

    public function handleSns(SnsEvent $event, Context $context): void
    {
        foreach ($event->getRecords() as $record) {
            $message = json_decode($record->getMessage(), true);

            if (!isset($message['UserID']) || !isset($message['FileName'])) {
                Log::info($record->getMessage());
                throw new \RuntimeException('Message attribute "UserID" or "FileName" missing');
            }

            $user = User::query()->whereKey($message['UserID'])->firstOrFail();
            $userKey = $user->getKey();

            $generator = CSVReader::readRows(sprintf('users/%s/schedule/%s', $userKey, $message['FileName']));
            $chunksGenerator = CSVReader::chunkGenerator($generator, 1000);

            $chunks = 0;
            $rows = 0;

            foreach ($chunksGenerator as $data) {
                CreateMeetings::dispatch($userKey, $data);

                $chunks++;
                $rows += count($data);
            }

            Log::info('Chunks count: '.$chunks);
            Log::info('Rows count: '.$rows);
        }
    }
}
