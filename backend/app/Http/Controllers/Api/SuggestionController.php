<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Abbreviation;
use App\Services\AbbreviationSuggestionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SuggestionController extends Controller
{
    public function __construct(
        private AbbreviationSuggestionService $suggestionService
    ) {}

    /**
     * Generate suggestions for text input
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|min:1|max:500',
            'category' => 'nullable|string|max:100',
            'context' => 'nullable|string|max:1000',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $text = $request->input('text');
        $category = $request->input('category');
        $context = $request->input('context');
        $limit = $request->input('limit', 10);

        try {
            $suggestions = [];

            // First, check for existing abbreviations in database
            $words = $this->extractPotentialAbbreviations($text);
            $existingSuggestions = $this->getExistingAbbreviations($words);

            foreach ($existingSuggestions as $existing) {
                $suggestions[] = [
                    'id' => $existing['id'],
                    'abbreviation' => $existing['abbreviation'],
                    'meaning' => $existing['meaning'],
                    'category' => $existing['category'],
                    'description' => $existing['description'] ?? '',
                    'confidence_score' => 0.95, // High confidence for existing
                    'source' => 'database',
                    'status' => 'approved',
                ];
            }

            // Then get AI suggestions for words not found in database
            $remainingWords = array_diff($words, array_column($existingSuggestions, 'abbreviation'));

            foreach ($remainingWords as $word) {
                if (count($suggestions) >= $limit) {
                    break;
                }

                try {
                    $wordSuggestions = $this->suggestionService->getSuggestions($word);

                    foreach ($wordSuggestions as $suggestion) {
                        if (count($suggestions) >= $limit) {
                            break;
                        }

                        // Clean up the meaning text
                        $meaning = $this->cleanMeaning($suggestion['meaning'] ?? '');
                        $source = $this->determineSuggestionSource($suggestion);

                        if (! empty($meaning)) {
                            $suggestionData = [
                                'id' => 0,
                                'abbreviation' => $word,
                                'meaning' => $meaning,
                                'category' => $category ?? $this->mapCategory($suggestion['category'] ?? 'Ostalo'),
                                'description' => $this->cleanDescription($suggestion['description'] ?? ''),
                                'confidence_score' => round($suggestion['confidence'] ?? 0.5, 2),
                                'source' => $source,
                                'status' => 'pending',
                            ];

                            // Add original meaning if available (from AI providers like Groq)
                            if (isset($suggestion['original_meaning']) && ! empty($suggestion['original_meaning'])) {
                                $suggestionData['original_meaning'] = $this->cleanMeaning($suggestion['original_meaning']);
                            }

                            $suggestions[] = $suggestionData;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to get suggestions for word: {$word}", [
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }
            }

            // If still no suggestions, create basic ones
            if (empty($suggestions)) {
                $abbreviation = $this->generateAbbreviation($text);
                $suggestions[] = [
                    'id' => 0,
                    'abbreviation' => $abbreviation,
                    'meaning' => $text,
                    'category' => $category ?? 'Ostalo',
                    'description' => '',
                    'confidence_score' => 0.3,
                    'source' => 'generated',
                    'status' => 'pending',
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => array_slice($suggestions, 0, $limit),
            ]);
        } catch (\Exception $e) {
            Log::error('Suggestion generation failed', [
                'text' => $text,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate suggestions',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Extract potential abbreviations from text
     */
    /**
     * @return list<string>
     */
    private function extractPotentialAbbreviations(string $text): array
    {
        // Split text and look for potential abbreviations
        $words = preg_split('/\s+/', trim($text));
        $abbreviations = [];

        foreach ($words as $word) {
            $word = preg_replace('/[^\w]/', '', $word);

            // Look for words that might be abbreviations
            if (strlen($word) >= 2 && strlen($word) <= 10) {
                // Check if it's all uppercase (likely abbreviation)
                if (ctype_upper($word)) {
                    $abbreviations[] = $word;
                }
                // Or if it contains mixed case and might be an acronym
                elseif (preg_match('/[A-Z]/', $word) && strlen($word) <= 6) {
                    $abbreviations[] = strtoupper($word);
                }
            }
        }

        // If no potential abbreviations found, use the whole text to generate one
        if (empty($abbreviations)) {
            $abbreviations[] = $this->generateAbbreviation($text);
        }

        return array_unique($abbreviations);
    }

    /**
     * Generate an abbreviation from text
     */
    private function generateAbbreviation(string $text): string
    {
        $words = preg_split('/\s+/', trim($text));
        $abbreviation = '';

        // Take first letter of each word
        foreach ($words as $word) {
            $word = preg_replace('/[^\w]/', '', $word);
            if (! empty($word)) {
                $abbreviation .= strtoupper($word[0]);
            }
        }

        // If too short, add more letters from first word
        if (strlen($abbreviation) < 2 && ! empty($words)) {
            $firstWord = preg_replace('/[^\w]/', '', $words[0]);
            $abbreviation = strtoupper(substr($firstWord, 0, min(4, strlen($firstWord))));
        }

        return $abbreviation ?: 'GEN';
    }

    /**
     * Get existing abbreviations from database for given words
     */
    /**
     * @param  list<string>  $words
     * @return array<int, array<string, mixed>>
     */
    private function getExistingAbbreviations(array $words): array
    {
        if (empty($words)) {
            return [];
        }

        try {
            // Search for existing approved abbreviations that match the words
            $existing = Abbreviation::where('status', 'approved')
                ->whereIn('abbreviation', array_map('strtoupper', $words))
                ->with('user')
                ->get()
                ->map(function ($abbrev) {
                    return [
                        'id' => $abbrev->id,
                        'abbreviation' => $abbrev->abbreviation,
                        'meaning' => $abbrev->meaning,
                        'category' => $abbrev->category,
                        'description' => $abbrev->description,
                    ];
                })
                ->toArray();

            return $existing;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch existing abbreviations', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Clean and format meaning text
     */
    private function cleanMeaning(string $meaning): string
    {
        // Remove excessive details and keep it concise
        $meaning = trim($meaning);

        // If it's too long, truncate at sentence boundary
        if (strlen($meaning) > 150) {
            $sentences = explode('.', $meaning);
            $meaning = $sentences[0].'.';
        }

        // Remove newlines and extra spaces
        $meaning = preg_replace('/\s+/', ' ', $meaning);

        return $meaning;
    }

    /**
     * Clean description text
     */
    private function cleanDescription(string $description): string
    {
        $description = trim($description);

        // Limit description length
        if (strlen($description) > 200) {
            $description = substr($description, 0, 197).'...';
        }

        return $description;
    }

    /**
     * Determine suggestion source based on suggestion data
     */
    /**
     * @param  array<string, mixed>  $suggestion
     */
    private function determineSuggestionSource(array $suggestion): string
    {
        if (isset($suggestion['source'])) {
            return $suggestion['source'];
        }

        // Determine based on content patterns
        if (isset($suggestion['wikipedia']) && $suggestion['wikipedia']) {
            return 'wikipedia';
        }

        if (isset($suggestion['wiktionary']) && $suggestion['wiktionary']) {
            return 'wiktionary';
        }

        if (isset($suggestion['acronym_finder']) && $suggestion['acronym_finder']) {
            return 'acronym_finder';
        }

        return 'ai';
    }

    /**
     * Map category names to Croatian equivalents
     */
    private function mapCategory(string $category): string
    {
        $categoryMap = [
            'General' => 'Ostalo',
            'Technology' => 'Tehnologija',
            'Business' => 'Poslovanje',
            'Science' => 'Znanost',
            'Medical' => 'Medicina',
            'Education' => 'Obrazovanje',
            'Government' => 'Vlada',
            'Military' => 'Vojska',
            'Sports' => 'Sport',
            'Entertainment' => 'Zabava',
        ];

        return $categoryMap[$category] ?? $category;
    }

    /**
     * Get suggestions by category
     */
    public function getByCategory(Request $request, string $category): JsonResponse
    {
        $limit = $request->query('limit', 20);

        try {
            // For now, return empty array as this would need a proper suggestions database
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get suggestions by category',
            ], 500);
        }
    }

    /**
     * Store a new suggestion
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'abbreviation' => 'required|string|max:20',
            'meaning' => 'required|string|max:200',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            // For now, return a mock response
            // In a real implementation, you'd save to database
            $suggestion = [
                'id' => rand(1000, 9999),
                'abbreviation' => $request->input('abbreviation'),
                'meaning' => $request->input('meaning'),
                'category' => $request->input('category'),
                'description' => $request->input('description'),
                'confidence_score' => 0.8,
                'source' => 'user',
                'status' => 'pending',
                'user_id' => null, // Would get from auth
                'created_at' => Carbon::now()->toISOString(),
                'original_meaning' => null,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $suggestion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create suggestion',
            ], 500);
        }
    }

    /**
     * Get pending suggestions
     */
    public function getPending(): JsonResponse
    {
        try {
            // Return empty array for now
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get pending suggestions',
            ], 500);
        }
    }

    /**
     * Approve a suggestion
     */
    public function approve(string $suggestionId): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve suggestion',
            ], 500);
        }
    }

    /**
     * Reject a suggestion
     */
    public function reject(Request $request, string $suggestionId): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject suggestion',
            ], 500);
        }
    }

    /**
     * Delete a suggestion
     */
    public function destroy(string $suggestionId): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete suggestion',
            ], 500);
        }
    }

    /**
     * Get similar suggestions
     */
    public function getSimilar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'abbreviation' => 'required|string|max:20',
            'meaning' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            // Return empty array for now
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get similar suggestions',
            ], 500);
        }
    }

    /**
     * Validate a suggestion
     */
    public function validateSuggestion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'abbreviation' => 'required|string|max:20',
            'meaning' => 'required|string|max:200',
            'category' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'isValid' => false,
                'issues' => $validator->errors()->all(),
            ]);
        }

        // Basic validation passed
        return response()->json([
            'isValid' => true,
            'issues' => [],
        ]);
    }

    /**
     * Get suggestion statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            return response()->json([
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'byCategory' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get suggestion statistics',
            ], 500);
        }
    }
}
