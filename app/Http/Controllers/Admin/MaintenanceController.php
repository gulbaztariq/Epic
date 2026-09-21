<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WebScheduler;
use Illuminate\Support\Facades\Artisan;

/**
 * Housekeeping actions for hosting without SSH: the jobs you would otherwise
 * run from a terminal, available to super admins and administrators.
 */
class MaintenanceController extends Controller
{
    /**
     * Rebuild the cached configuration, routes and views. Needed after editing
     * .env on a server where you cannot run artisan.
     */
    public function refreshCaches()
    {
        try {
            // Clearing first leaves the site working even if rebuilding fails.
            Artisan::call('optimize:clear');
            Artisan::call('optimize');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not refresh the caches: '.$e->getMessage()
                .' Check that storage and bootstrap/cache are writable (775).');
        }

        return back()->with('success', 'Caches cleared and rebuilt. Any changes to your .env file are now live.');
    }

    /** Run the recurring tasks immediately. */
    public function runSchedule(WebScheduler $scheduler)
    {
        $ran = $scheduler->runDue(force: true);

        if ($ran === []) {
            return back()->with('info', 'The tasks are already running elsewhere — try again in a moment.');
        }

        return back()->with('success', 'Ran: '.collect($ran)->pluck('label')->implode(', ').'.');
    }
}
