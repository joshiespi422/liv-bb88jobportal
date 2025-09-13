<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeLog;
use Illuminate\Support\Facades\Log;

class UpdateOpenTimeLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-open-time-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates all open time-ins for the current day with a time_out';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get today's date based on your app's timezone ('Asia/Manila')
        $today = now()->toDateString();

        // Find all time logs for today that have not been timed out yet
        $openLogs = TimeLog::where('date', $today)
                           ->whereNull('time_out');

        // Get the count for logging purposes before updating
        $count = $openLogs->count();

        if ($count > 0) {
            // Update the found records with the current time
            $openLogs->update(['time_out' => now()->toTimeString()]);

            // Log a success message for your records
            Log::info("Successfully updated {$count} open time logs.");
            $this->info("Successfully updated {$count} open time logs.");
        } else {
            // Log that no records needed updating
            Log::info('No open time logs found to update.');
            $this->info('No open time logs found to update.');
        }

        return 0; // Return 0 for success
    }
}
