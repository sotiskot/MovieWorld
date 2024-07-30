<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MovieApiService
{
    private HttpClientInterface $client;
    private string $apiUrl = 'https://api.themoviedb.org/3';
    private string $apiKey = 'YOUR_API_KEY_HERE'; // Use your actual API key here

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function searchMovies(string $query, int $page = 1): array
    {
        $response = $this->client->request('GET', $this->apiUrl . '/search/movie', [
            'headers' => [
                'Authorization' => 'Bearer YOUR_www.themoviedb.org_API_KEY',
                'Accept' => 'application/json',
            ],
            'query' => [
                'query' => $query,
                'include_adult' => 'false',
                'language' => 'en-US',
                'page' => $page,
            ],
        ]);

        return $this->handleResponse($response);
    }

    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();

        if ($statusCode !== 200) {
            throw new \Exception('Error: ' . $content);
        }

        return json_decode($content, true);
    }
}
