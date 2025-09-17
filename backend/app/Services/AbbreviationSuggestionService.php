<?php

namespace App\Services;

use App\Services\SuggestionProviders\AcronymFinderProvider;
use Illuminate\Support\Facades\Cache;

class AbbreviationSuggestionService
{
    private const CACHE_TTL = 3600; // 1 hour cache

    private AcronymFinderProvider $acronymFinderProvider;

    public function __construct()
    {
        $this->acronymFinderProvider = new AcronymFinderProvider;
    }

    /**
     * Get suggestions for abbreviation meanings from Acronym Finder
     */
    /**
     * @return list<array<string, mixed>>
     */
    public function getSuggestions(string $abbreviation): array
    {
        $cacheKey = 'abbreviation_suggestions_' . strtolower($abbreviation);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($abbreviation) {
            // Get suggestions from Acronym Finder only
            $suggestions = $this->acronymFinderProvider->getSuggestions($abbreviation);

            // Simple processing - just return the results
            return array_slice($suggestions, 0, 10); // Limit to 10 results
        });
    }
}
