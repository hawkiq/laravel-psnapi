<?php

namespace Hawkiq\LaravelPsnApi\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PsnAuthenticator
{
    protected string $npsso;
    protected string $clientId;
    protected string $basicAuth;
    protected string $redirectUri;

    public function __construct(array $config)
    {
        $this->npsso = $config['npsso'] ?? '';
        $this->clientId = $config['client_id'] ?? '';
        $this->basicAuth = $config['basic_auth'] ?? '';
        $this->redirectUri = $config['redirect_uri'] ?? '';
    }

    public function getAccessToken(): string
    {
        if ($accessToken = Cache::get('psn_access_token')) {
            return $accessToken;
        }

        if ($refreshToken = Cache::get('psn_refresh_token')) {
            try {
                return $this->refreshAccessToken($refreshToken);
            } catch (\Exception $e) {
                Cache::forget('psn_refresh_token');
            }
        }

        return $this->authenticateWithNpsso();
    }

    protected function authenticateWithNpsso(): string
    {
        if (empty($this->npsso)) {
            throw new \InvalidArgumentException('PSN NPSSO code is missing in configuration.');
        }

        $authParams = http_build_query([
            'access_type' => 'offline',
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => 'psn:mobile.v2.core psn:clientapp',
            'redirect_uri' => $this->redirectUri,
        ]);

        $authResponse = Http::withOptions(['allow_redirects' => false])
            ->withHeaders(['Cookie' => "npsso={$this->npsso}"])
            ->get("https://ca.account.sony.com/api/authz/v3/oauth/authorize?{$authParams}");

        $location = $authResponse->header('Location');

        if (!$location || !str_contains($location, 'code=')) {
            throw new \RuntimeException('Failed to retrieve OAuth authorization code.');
        }

        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $code = $query['code'] ?? null;

        if (!$code) {
            throw new \RuntimeException('Authorization code missing.');
        }

        $tokenResponse = Http::asForm()
            ->withHeaders(['Authorization' => $this->basicAuth])
            ->post('https://ca.account.sony.com/api/authz/v3/oauth/token', [
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri,
                'token_format' => 'jwt',
            ]);

        if ($tokenResponse->failed()) {
            throw new \RuntimeException("NPSSO token exchange failed: " . $tokenResponse->body());
        }

        return $this->storeTokens($tokenResponse->json());
    }

    protected function refreshAccessToken(string $refreshToken): string
    {
        $response = Http::asForm()
            ->withHeaders(['Authorization' => $this->basicAuth])
            ->post('https://ca.account.sony.com/api/authz/v3/oauth/token', [
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
                'scope' => 'psn:mobile.v2.core psn:clientapp',
                'token_format' => 'jwt',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException("Refresh token exchange failed.");
        }

        return $this->storeTokens($response->json());
    }

    protected function storeTokens(array $data): string
    {
        $accessToken = $data['access_token'];
        $refreshToken = $data['refresh_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? 3600;

        Cache::put('psn_access_token', $accessToken, now()->addSeconds($expiresIn - 600));

        if ($refreshToken) {
            Cache::put('psn_refresh_token', $refreshToken, now()->addDays(55));
        }

        return $accessToken;
    }
}
