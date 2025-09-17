<?php

namespace App\Services\SuggestionProviders;

abstract class BaseProvider
{
    /**
     * Get suggestions from this provider
     */
    /**
     * @return list<array<string, mixed>>
     */
    abstract public function getSuggestions(string $abbreviation): array;

    /**
     * Get the provider name
     */
    abstract public function getProviderName(): string;

    /**
     * Normalize suggestion format across providers
     */
    /**
     * @param  array<string, mixed>  $suggestion
     * @return array<string, mixed>
     */
    protected function normalizeSuggestion(array $suggestion): array
    {
        return [
            'meaning' => $suggestion['meaning'] ?? '',
            'source' => $suggestion['source'] ?? $this->getProviderName(),
            'category' => $suggestion['category'] ?? 'general',
            'type' => $suggestion['type'] ?? 'general',
            'original_meaning' => $suggestion['original_meaning'] ?? null,
            'description' => $suggestion['description'] ?? null,
            'url' => $suggestion['url'] ?? null,
        ];
    }

    /**
     * Check if response is valid
     */
    /**
     * @param  array<string, mixed>|string|null  $response
     */
    protected function isValidResponse(array|string|null $response): bool
    {
        return ! empty($response) && is_array($response);
    }
}
