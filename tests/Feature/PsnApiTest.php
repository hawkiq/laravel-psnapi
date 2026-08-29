<?php

use Hawkiq\LaravelPsnApi\Facades\PsnApi;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'https://ca.account.sony.com/api/authz/v3/oauth/authorize*' => Http::response(null, 302, [
            'Location' => 'com.playstation.PlayStationApp://redirect?code=mock_authorization_code',
        ]),

        'https://ca.account.sony.com/api/authz/v3/oauth/token' => Http::response([
            'access_token' => 'mock_psn_access_token',
            'refresh_token' => 'mock_psn_refresh_token',
            'expires_in' => 3600,
        ], 200),
    ]);
});

it('fetches trophy definitions successfully using NPWR ID', function () {
    Http::fake([
        'https://m.np.playstation.com/api/trophy/v1/npCommunicationIds/NPWR10668_00/*' => Http::response([
            'trophies' => [
                [
                    'trophyId' => 0,
                    'trophyName' => 'It Spot',
                    'trophyType' => 'platinum',
                    'trophyIconUrl' => 'https://example.com/icon.png',
                ],
            ],
        ], 200),
    ]);

    $data = PsnApi::getGameTrophies('NPWR10668_00', '8982354720274779799');

    expect($data)->toHaveKey('trophies')
        ->and($data['trophies'][0]['trophyName'])->toBe('It Spot');
});

it('resolves CUSA title id and fetches trophies', function () {
    Http::fake([
        'https://m.np.playstation.com/api/trophy/v1/users/*/titles/trophyTitles*' => Http::response([
            'titles' => [
                [
                    'trophyTitles' => [
                        ['npCommunicationId' => 'NPWR10668_00'],
                    ],
                ],
            ],
        ], 200),
        'https://m.np.playstation.com/api/trophy/v1/npCommunicationIds/NPWR10668_00/*' => Http::response([
            'trophies' => [],
        ], 200),
    ]);

    $data = PsnApi::getGameTrophies('CUSA00341', '8982354720274779799');

    expect($data)->toBeArray();
});
