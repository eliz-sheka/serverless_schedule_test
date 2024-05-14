<?php

namespace App\Services;

use App\Models\User;
use Bref\Context\Context;
use Bref\Event\Sns\SnsEvent;
use Bref\Event\Sns\SnsHandler as BrefSnsHandler;
use Illuminate\Support\Facades\Log;

class SNSHandler extends BrefSnsHandler
{

    public function handleSns(SnsEvent $event, Context $context): void
    {
        foreach ($event->getRecords() as $record) {
            $message = $record->getMessage();

            Log::info($message);

            User::query()->firstOrFail()->meetings()->create([
                'date' => now()->format('Y-m-d'),
                'start_time' => now()->format('H:i'),
                'end_time' => now()->format('H:i'),
            ]);
        }
    }
}
