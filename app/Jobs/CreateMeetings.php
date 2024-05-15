<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\CSVReader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateMeetings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $userId
     * @param string $filepath
     * @param int $offset
     * @param int $limit
     */
    public function __construct(
        private int $userId,
        private string $filepath,
        private int $offset,
        private int $limit,
    ) {

    }

    /**
     * @return void
     * @throws \League\Csv\Exception
     * @throws \League\Csv\InvalidArgument
     * @throws \League\Csv\SyntaxError
     */
    public function handle(): void
    {
        /** @var User $user */
        $user = User::query()->whereKey($this->userId)->firstOrFail();
        $records = (new CSVReader($this->filepath))->getCsvRecords($this->offset, $this->limit);

        foreach ($records as $activity) {
            if ('meeting' !== $activity['Type']) {
                continue;
            }

            try {
                $user->meetings()->create([
                    'date' => $activity['Date'],
                    'start_time' => $activity['Start_time'],
                    'end_time' => $activity['End_time'],
                ]);
            } catch (QueryException $e) {
                Log::error($e->getMessage());
            }
        }
    }
}
