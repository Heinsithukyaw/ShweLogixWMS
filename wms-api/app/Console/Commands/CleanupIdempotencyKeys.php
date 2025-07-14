<?php

namespace App\Console\Commands;

use App\Services\IdempotencyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupIdempotencyKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wms:cleanup-idempotency-keys {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired idempotency keys';

    /**
     * The idempotency service.
     *
     * @var \App\Services\IdempotencyService
     */
    protected $idempotencyService;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\IdempotencyService  $idempotencyService
     * @return void
     */
    public function __construct(IdempotencyService $idempotencyService)
    {
        parent::__construct();
        $this->idempotencyService = $idempotencyService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting idempotency keys cleanup...');
        
        try {
            // Get statistics before cleanup
            $beforeStats = $this->idempotencyService->getStatistics();
            
            $this->info('Statistics before cleanup:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Keys', $beforeStats['total_keys']],
                    ['Active Keys', $beforeStats['active_keys']],
                    ['Expired Keys', $beforeStats['expired_keys']],
                    ['Completed Keys', $beforeStats['completed_keys']],
                    ['Failed Keys', $beforeStats['failed_keys']],
                    ['Processing Keys', $beforeStats['processing_keys']],
                ]
            );

            if ($this->option('dry-run')) {
                $this->warn('DRY RUN MODE - No keys will be deleted');
                $this->info("Would delete {$beforeStats['expired_keys']} expired keys");
                return 0;
            }

            // Perform cleanup
            $deletedCount = $this->idempotencyService->cleanupExpiredKeys();
            
            if ($deletedCount > 0) {
                $this->info("Successfully deleted {$deletedCount} expired idempotency keys");
                
                // Get statistics after cleanup
                $afterStats = $this->idempotencyService->getStatistics();
                
                $this->info('Statistics after cleanup:');
                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Total Keys', $afterStats['total_keys']],
                        ['Active Keys', $afterStats['active_keys']],
                        ['Expired Keys', $afterStats['expired_keys']],
                        ['Completed Keys', $afterStats['completed_keys']],
                        ['Failed Keys', $afterStats['failed_keys']],
                        ['Processing Keys', $afterStats['processing_keys']],
                    ]
                );
            } else {
                $this->info('No expired idempotency keys found to delete');
            }

            Log::info('Idempotency keys cleanup completed', [
                'deleted_count' => $deletedCount,
                'before_stats' => $beforeStats,
                'after_stats' => $afterStats ?? $beforeStats,
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('Idempotency keys cleanup failed: ' . $e->getMessage());
            
            Log::error('Idempotency keys cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}