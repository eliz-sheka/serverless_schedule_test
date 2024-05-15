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
            $path = sprintf('users/%s/schedule/%s', $userKey, $message['FileName']);

            $total = (new CSVReader($path))->countTotal();
            Log::info('Total: '.$total);
            $limit = 1000;

            for ($offset = 0; $offset <= $total; $offset += $limit) {
                CreateMeetings::dispatch($userKey, $path, $offset, $limit);
                Log::info('Dispatched');
            }
        }
    }
}
