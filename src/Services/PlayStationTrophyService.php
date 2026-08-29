<?php

namespace Hawkiq\LaravelPsnApi\Services;

use Hawkiq\LaravelPsnApi\Auth\PsnAuthenticator;
use Hawkiq\LaravelPsnApi\Resolvers\TitleResolver;
use Illuminate\Support\Facades\Http;

class PlayStationTrophyService
{
    public function __construct(
        protected PsnAuthenticator $authenticator,
        protected TitleResolver $resolver
    ) {}

    public function getGameTrophies(string $gameId, string $accountId, string $language = 'en'): array
    {
        $accessToken = $this->authenticator->getAccessToken();
        $npCommunicationId = $this->resolver->resolve($gameId, $accountId, $accessToken);

        $serviceNames = ['trophy2', 'trophy'];
        $trophyGroups = ['all', 'default'];
        $lastStatus = null;

        foreach ($serviceNames as $serviceName) {
            foreach ($trophyGroups as $group) {
                $url = "https://m.np.playstation.com/api/trophy/v1/npCommunicationIds/{$npCommunicationId}/trophyGroups/{$group}/trophies";
                $response = Http::withToken($accessToken)->get($url, [
                    'npServiceName' => $serviceName,
                    'npLanguage' => $language,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }
                $lastStatus = $response->status();
            }
        }

        throw new \RuntimeException("Failed to fetch trophy definitions for NPWR ID '{$npCommunicationId}'. Last HTTP Status: {$lastStatus}.");
    }

    public function getUserEarnedTrophies(string $gameId, string $accountId): array
    {
        $accessToken = $this->authenticator->getAccessToken();
        $npCommunicationId = $this->resolver->resolve($gameId, $accountId, $accessToken);

        $serviceNames = ['trophy2', 'trophy'];
        $lastStatus = null;

        foreach ($serviceNames as $serviceName) {
            $url = "https://m.np.playstation.com/api/trophy/v1/users/{$accountId}/npCommunicationIds/{$npCommunicationId}/trophyGroups/all/trophies";

            $response = Http::withToken($accessToken)->get($url, [
                'npServiceName' => $serviceName
            ]);

            if ($response->successful()) {
                return $response->json();
            }
            $lastStatus = $response->status();
        }

        throw new \RuntimeException("Failed to fetch earned trophies. Last HTTP Status: {$lastStatus}.");
    }
}
