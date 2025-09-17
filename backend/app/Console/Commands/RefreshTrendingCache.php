<?php

namespace App\Console\Commands;

use App\Services\TrendingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshTrendingCache extends Command
{
    protected $signature = 'trending:refresh';

    protected $description = 'Refresh trending abbreviations cache';

    public function handle(): int
    {
        $this->info('Refreshing trending cache...');

        try {
            // Clear existing cache
            Cache::forget('trending_abbreviations_10');
            Cache::forget('trending_abbreviations_5');
            Cache::forget('trending_abbreviations_20');

            // Get trending service from container and pre-warm cache with different limits
            $trendingService = app(TrendingService::class);

            // Pre-warm cache for different limits
            $limits = [5, 10, 20];
            foreach ($limits as $limit) {
                $cacheKey = "trending_abbreviations_{$limit}";
                $trendingData = $trendingService->calculateTrendingAbbreviations($limit);
                Cache::put($cacheKey, $trendingData, now()->addHours(1)); // Cache for 1 hour
                $this->info("Cached trending data for limit: {$limit}");
            }

            $this->info('Trending cache refreshed successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to refresh trending cache: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
