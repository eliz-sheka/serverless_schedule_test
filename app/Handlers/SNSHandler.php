<?php

namespace App\Handlers;

use App\Jobs\CreateMeetings;
use App\Models\User;
use App\Services\CSVReader;
use Bref\Context\Context;
use Bref\Event\Sns\SnsEvent;
use Bref\Event\Sns\SnsHandler as BrefSnsHandler;
use Illuminate\Support\Facades\Log;

class SNSHandler extends BrefSnsHandler
{

    public function handleSns(SnsEvent $event, Context $context): void
    {
        foreach ($event->getRecords() as $record) {
            $attributes = $record->getMessageAttributes();

            foreach ($attributes as $name => $value) {
                if ('MessageType' === $name && 'ScheduleLoaded' !== $value->getValue()) {
                    throw new \RuntimeException('Loaded wrong message attribute "' . $name . '"');
                }
            }

            $message = json_decode($record->getMessage(), true);
            Log::info($record->getMessage());

            if (!isset($message['UserID']) || !isset($message['FileName'])) {
                Log::info($record->getMessage());
                throw new \RuntimeException('Message attribute "UserID" or "FileName" missing');
            }

            $user = User::query()->whereKey($message['UserID'])->firstOrFail();
            $userKey = $user->getKey();

            $generator = CSVReader::readRows(sprintf('users/%s/schedule/%s', $userKey, $message['FileName']));
            Log::info('Rows generator created');
            $chunksGenerator = CSVReader::chunkGenerator($generator, 1000);
            Log::info('Chunks generator created');
            $chunks = 0;
            $rows = 0;

            foreach ($chunksGenerator as $data) {
                Log::info('Inside foreach');
                CreateMeetings::dispatch($userKey, $data);
                Log::info('Job dispatched');

                $chunks++;
                $rows += count($data);
            }

            Log::info('Chunks count: '.$chunks);
            Log::info('Rows count: '.$rows);
        }
    }
}
