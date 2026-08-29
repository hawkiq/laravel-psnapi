<?php

namespace Hawkiq\LaravelPsnApi\Resolvers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TitleResolver
{
    public function resolve(string $gameId, string $accountId, string $accessToken): string
    {
        $gameId = strtoupper(trim($gameId));
        $gameId = str_replace('-', '', $gameId);

        if (str_starts_with($gameId, 'NPWR')) {
            return $gameId;
        }

        if (str_starts_with($gameId, 'CUSA') || str_starts_with($gameId, 'PPSA')) {
            if (!str_contains($gameId, '_')) {
                $gameId .= '_00';
            }

            $cacheKey = "psn_title_map_{$gameId}";
            if ($cachedNpwr = Cache::get($cacheKey)) {
                return $cachedNpwr;
            }

            $url = "https://m.np.playstation.com/api/trophy/v1/users/{$accountId}/titles/trophyTitles";
            $response = Http::withToken($accessToken)->get($url, ['npTitleIds' => $gameId]);

            if ($response->successful()) {
                $title = $response->json('titles.0.trophyTitles.0.npCommunicationId');
                if ($title) {
                    Cache::forever($cacheKey, $title);
                    return $title;
                }
            }
        }

        throw new \InvalidArgumentException("Provided game ID '{$gameId}' is invalid or the provided user '{$accountId}' has not played it.");
    }
}
