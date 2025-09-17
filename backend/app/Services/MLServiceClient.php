<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for communicating with the ML service
 */
class MLServiceClient
{
    private string $mlServiceUrl;

    public function __construct()
    {
        $this->mlServiceUrl = env('ML_SERVICE_URL', 'http://ml-service:5000');
    }

    /**
     * Get general recommendations from ML service
     *
     * @param  int  $limit  Maximum number of recommendations to return
     * @return Response HTTP response from ML service
     *
     * @throws \Exception When ML service communication fails
     */
    public function getRecommendations(int $limit): Response
    {
        try {
            $response = Http::timeout(30)->get(
                $this->mlServiceUrl.'/recommendations',
                ['limit' => $limit]
            );

            Log::info('ML service getRecommendations response status: '.$response->status());

            return $response;
        } catch (\Exception $e) {
            Log::error('ML service communication error (getRecommendations): '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get personalized recommendations from ML service
     *
     * @param  int  $userId  User ID to get recommendations for
     * @param  array<string, mixed>  $userData  User data to send to ML service
     * @return Response HTTP response from ML service
     *
     * @throws \Exception When ML service communication fails
     */
    public function getPersonalizedRecommendations(int $userId, array $userData): Response
    {
        try {
            Log::info('Calling ML service: '.$this->mlServiceUrl."/recommendations/{$userId}");

            $response = Http::timeout(30)->post(
                $this->mlServiceUrl."/recommendations/{$userId}",
                [
                    'user_data' => $userData,
                    'limit' => 10,
                ]
            );

            Log::info('ML service response status: '.$response->status());

            return $response;
        } catch (\Exception $e) {
            Log::error('ML service communication error (getPersonalizedRecommendations): '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Update training data on ML service
     *
     * @param  array<string, mixed>  $data  Training data to send to ML service
     * @return Response HTTP response from ML service
     *
     * @throws \Exception When ML service communication fails
     */
    public function updateTrainingData(array $data): Response
    {
        try {
            $response = Http::timeout(60)
                ->post($this->mlServiceUrl.'/update-training', $data);

            Log::info('Update training data response status: '.$response->status());

            return $response;
        } catch (\Exception $e) {
            Log::error('ML service communication error (updateTrainingData): '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if ML service is available
     *
     * @return bool True if ML service is responding, false otherwise
     */
    public function isAvailable(): bool
    {
        try {
            // Try /health endpoint first
            $response = Http::timeout(5)->get($this->mlServiceUrl.'/health');
            if ($response->successful()) {
                $data = $response->json();

                return isset($data['status']) && $data['status'] === 'healthy';
            }

            // Fallback to root endpoint
            $response = Http::timeout(5)->get($this->mlServiceUrl.'/');
            if ($response->successful()) {
                $data = $response->json();

                return isset($data['status']) && $data['status'] === 'ok';
            }

            return false;
        } catch (\Exception $e) {
            Log::warning('ML service health check failed: '.$e->getMessage());

            return false;
        }
    }
}
