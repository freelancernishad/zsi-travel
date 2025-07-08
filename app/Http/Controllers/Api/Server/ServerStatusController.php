<?php

namespace App\Http\Controllers\Api\Server;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

class ServerStatusController extends Controller
{
     /**
     * Check the server status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus()
    {
        // Check the database connection
        $databaseStatus = $this->checkDatabaseConnection();

        // Check the cache status
        $cacheStatus = $this->checkCacheStatus();

        // Check if queue is running
        $queueStatus = $this->checkQueueStatus();

        // Check if the application is in maintenance mode
        $maintenanceModeStatus = $this->checkMaintenanceMode();

        // Prepare the status data
        $status = [
            'database' => $databaseStatus,
            'cache' => $cacheStatus,
            'queue' => $queueStatus,
            'maintenance_mode' => $maintenanceModeStatus,
            'uptime' => $this->getServerUptime(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_space' => $this->getDiskSpace(),
            'timestamp' => now(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $status
        ]);
    }

    /**
     * Check the database connection status.
     *
     * @return string
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return 'connected';
        } catch (\Exception $e) {
            return 'disconnected';
        }
    }

    /**
     * Check the cache status.
     *
     * @return string
     */
    private function checkCacheStatus()
    {
        try {
            Cache::store()->put('test', 'test', 1);
            return 'working';
        } catch (\Exception $e) {
            return 'not working';
        }
    }

    /**
     * Check if the queue is running.
     *
     * @return string
     */
    private function checkQueueStatus()
    {
        $queueStatus = Artisan::call('queue:listen');
        return $queueStatus == 0 ? 'running' : 'not running';
    }

    /**
     * Check if the application is in maintenance mode.
     *
     * @return string
     */
    private function checkMaintenanceMode()
    {
        return app()->isDownForMaintenance() ? 'active' : 'inactive';
    }

    /**
     * Get server uptime.
     *
     * @return string
     */
    private function getServerUptime()
    {
        $uptime = shell_exec('uptime -p');
        return $uptime ?: 'N/A';
    }

    /**
     * Get memory usage.
     *
     * @return string
     */
    private function getMemoryUsage()
    {
        $memory = shell_exec('free -h | grep Mem');
        return $memory ?: 'N/A';
    }

    /**
     * Get disk space.
     *
     * @return string
     */
    private function getDiskSpace()
    {
        $disk = shell_exec('df -h /');
        return $disk ?: 'N/A';
    }
}
