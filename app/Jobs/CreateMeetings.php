<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateMeetings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $userId, private array $activities)
    {

    }

    public function handle(): void
    {
        $user = User::query()->whereKey($this->userId)->firstOrFail();
        $meetings = [];

        foreach ($this->activities as $activity) {
            if ('meeting' !== $activity[3]) {
                continue;
            }

            $meetings[] = [
                'date' => $activity[0],
                'start_time' => $activity[1],
                'end_time' => $activity[2],
            ];
        }

        if ($meetings) {
            $user->meetings()->createMany($meetings);
        }
    }
}
