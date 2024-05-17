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
    /**
     * @param CSVReader $csvReader
     */
    public function __construct(private CSVReader $csvReader)
    {

    }

    /**
     * @param SnsEvent $event
     * @param Context $context
     * @return void
     * @throws \Bref\Event\InvalidLambdaEvent
     * @throws \League\Csv\Exception
     * @throws \League\Csv\InvalidArgument
     */
    public function handleSns(SnsEvent $event, Context $context): void
    {
        foreach ($event->getRecords() as $record) {
            $message = json_decode($record->getMessage(), true);

            if (!isset($message['UserID']) || !isset($message['FileName'])) {
                throw new \RuntimeException('Message attribute "UserID" or "FileName" missing');
            }

            $userKey = $message['UserID'];
            User::query()->whereKey($userKey)->firstOrFail();

            $path = sprintf('users/%s/schedule/%s', $userKey, $message['FileName']);

            $total = $this->csvReader->createFromStream($path)->count();
            Log::info('Total: '.$total);

            $limit = 1000;

            for ($offset = 0; $offset <= $total; $offset += $limit) {
                CreateMeetings::dispatch($userKey, $path, $offset, $limit);
            }
        }
    }
}
