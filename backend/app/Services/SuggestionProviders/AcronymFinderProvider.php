<?php

namespace App\Services\SuggestionProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AcronymFinderProvider extends BaseProvider
{
    public function getProviderName(): string
    {
        return 'AcronymFinder';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSuggestions(string $abbreviation): array
    {
        try {
            // Try stand4.com first (more reliable)
            $suggestions = $this->getFromStand4($abbreviation);

            if (empty($suggestions)) {
                // Fallback to acronymfinder if needed
                $suggestions = $this->getFromAcronymFinder($abbreviation);
            }

            return $suggestions;
        } catch (\Exception $e) {
            Log::warning("AcronymFinder API failed for {$abbreviation}: " . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getFromStand4(string $abbreviation): array
    {
        try {
            // Use Acromine API from Nactem (National Centre for Text Mining)
            $url = "http://www.nactem.ac.uk/software/acromine/dictionary.py";
            $params = ['sf' => $abbreviation];

            $response = Http::timeout(10)->get($url, $params);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();

            if (empty($data) || !isset($data[0]['lfs'])) {
                return [];
            }

            $suggestions = [];
            $acronymData = $data[0]; // First result contains the acronym data

            // Get top 5 most frequent long forms
            $longForms = array_slice($acronymData['lfs'], 0, 5);

            foreach ($longForms as $longForm) {
                if (isset($longForm['lf']) && !empty($longForm['lf'])) {
                    $frequency = $longForm['freq'] ?? 0;
                    $since = $longForm['since'] ?? null;

                    // Calculate confidence based on frequency and recency
                    $confidence = $this->calculateAcromineConfidence($frequency, $since);

                    $suggestions[] = $this->normalizeSuggestion([
                        'meaning' => $this->cleanAcromineMeaning($longForm['lf']),
                        'source' => 'Acromine (Nactem)',
                        'category' => $this->suggestCategory($longForm['lf']),
                        'type' => 'academic_acronym',
                        'original_meaning' => $longForm['lf'],
                        'frequency' => $frequency,
                        'since' => $since,
                        'confidence' => $confidence,
                    ]);
                }
            }

            return $suggestions;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getFromAcronymFinder(string $abbreviation): array
    {
        try {
            // This is also a placeholder - most acronym services require payment
            // You could implement scraping or use free alternatives
            $response = Http::timeout(5)
                ->get("https://www.acronymfinder.com/{$abbreviation}.html");

            if ($response->successful()) {
                $html = $response->body();
                $meanings = $this->parseAcronymFinderHtml($html, $abbreviation);

                $suggestions = [];
                foreach ($meanings as $meaning) {
                    if (strlen($meaning) > 10) {
                        $suggestions[] = $this->normalizeSuggestion([
                            'meaning' => $this->translateToHrvatski($meaning),
                            'source' => $this->getProviderName(),
                            'category' => $this->suggestCategory($meaning),
                            'type' => 'english_meaning',
                            'original_meaning' => $meaning,
                        ]);
                    }
                }

                return array_slice($suggestions, 0, 3); // Limit to 3 suggestions
            }
        } catch (\Exception $e) {
            Log::warning("AcronymFinder scraping failed for {$abbreviation}: " . $e->getMessage());
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function parseAcronymFinderHtml(string $html, string $abbreviation): array
    {
        $meanings = [];

        // This is a simplified parser - real implementation would be more robust
        if (preg_match_all('/<td[^>]*class="meaning"[^>]*>([^<]+)<\/td>/i', $html, $matches)) {
            foreach ($matches[1] as $match) {
                $meaning = trim(strip_tags($match));
                if (! empty($meaning) && strlen($meaning) > 5) {
                    $meanings[] = $meaning;
                }
            }
        }

        return array_unique($meanings);
    }

    private function translateToHrvatski(string $text): string
    {
        // Simple Croatian translations for common terms
        $translations = [
            'Corporation' => 'Korporacija',
            'Company' => 'Tvrtka',
            'Association' => 'Udruga',
            'Organization' => 'Organizacija',
            'Institute' => 'Institut',
            'University' => 'Sveučilište',
            'Technology' => 'Tehnologija',
            'System' => 'Sustav',
            'Network' => 'Mreža',
            'Service' => 'Usluga',
            'Department' => 'Odjel',
            'Administration' => 'Uprava',
            'Management' => 'Upravljanje',
            'Development' => 'Razvoj',
            'Research' => 'Istraživanje',
        ];

        foreach ($translations as $english => $croatian) {
            $text = str_ireplace($english, $croatian, $text);
        }

        return $text;
    }

    private function suggestCategory(string $meaning): string
    {
        $meaning = strtolower($meaning);

        if (preg_match('/\b(tech|computer|software|IT|internet|web|app|system|database|program)\b/i', $meaning)) {
            return 'Tehnologija';
        }

        if (preg_match('/\b(medic|health|hospital|clinic|disease|treatment)\b/i', $meaning)) {
            return 'Medicina';
        }

        if (preg_match('/\b(business|company|corporation|management|market|finance)\b/i', $meaning)) {
            return 'Poslovanje';
        }

        if (preg_match('/\b(education|school|university|college|student|academic)\b/i', $meaning)) {
            return 'Obrazovanje';
        }

        if (preg_match('/\b(government|administration|department|agency|ministry)\b/i', $meaning)) {
            return 'Vlada';
        }

        return 'Općenito';
    }

    /**
     * Calculate confidence score for Acromine results based on frequency and recency
     */
    private function calculateAcromineConfidence(int $frequency, ?int $since): float
    {
        $baseConfidence = 0.7; // Acromine is academic source, start with good confidence

        // Frequency bonus (0.1 for every 50 occurrences, max 0.2)
        $frequencyBonus = min(0.2, ($frequency / 50) * 0.1);

        // Recency bonus (more recent = better)
        $recencyBonus = 0;
        if ($since) {
            $currentYear = (int) date('Y');
            $yearsSince = $currentYear - $since;

            if ($yearsSince <= 5) {
                $recencyBonus = 0.1; // Very recent
            } elseif ($yearsSince <= 15) {
                $recencyBonus = 0.05; // Somewhat recent
            }
            // Older than 15 years gets no bonus
        }

        return min(0.95, $baseConfidence + $frequencyBonus + $recencyBonus);
    }

    /**
     * Clean and normalize Acromine meaning text
     */
    private function cleanAcromineMeaning(string $meaning): string
    {
        // Capitalize first letter
        $meaning = trim($meaning);
        $meaning = ucfirst(strtolower($meaning));

        // Handle some common abbreviation patterns - simple replacement
        $commonAbbrevs = ['api', 'fda', 'who', 'unesco', 'nato', 'usa', 'uk', 'eu'];
        foreach ($commonAbbrevs as $abbrev) {
            $meaning = preg_replace('/\b' . $abbrev . '\b/i', strtoupper($abbrev), $meaning);
        }

        return $meaning;
    }
}
