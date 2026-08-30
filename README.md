# laravel-psnapi

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hawkiq/laravel-psnapi.svg?style=flat-square)](https://packagist.org/packages/hawkiq/laravel-psnapi)
[![Total Downloads](https://img.shields.io/packagist/dt/hawkiq/laravel-psnapi.svg?style=flat-square)](https://packagist.org/packages/hawkiq/laravel-psnapi)
[![License](https://img.shields.io/packagist/l/hawkiq/laravel-psnapi.svg?style=flat-square)](https://packagist.org/packages/hawkiq/laravel-psnapi)
[![Plumb score](https://plumbphp.dev/badges/hawkiq/laravel-psnapi/composite.svg)](https://plumbphp.dev/hawkiq/laravel-psnapi)


> ⚠️ **Development Status**: This package is currently under active development as features expand. However, the core trophy fetching architecture is thoroughly structured and stable for use in production environments.

A clean, production-ready Laravel package to interact with the PlayStation Network (PSN) API and fetch game trophy data.

---

## Installation

Install the package via Composer:

```bash
composer require hawkiq/laravel-psnapi

```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=psnapi-config

```

---

## Configuration & Obtaining NPSSO

To fetch trophies from PSN, you must supply a valid `NPSSO` token. This token acts as your authentication key with Sony's servers.

### How to Get Your NPSSO Code (Step-by-Step)

1. Open your browser and log into your official account at [PlayStation.com](https://www.playstation.com).
2. Once logged in, open a new browser tab and navigate to:
[https://ca.account.sony.com/api/v1/ssocookie](https://ca.account.sony.com/api/v1/ssocookie)
3. You will see a JSON response on your screen looking like this:
```json
{"npsso":"64_character_alphanumeric_string_here"}

```


4. Copy the **64-character** value inside the `"npsso"` field.

> **Note:** NPSSO tokens can expire periodically (usually after several weeks or if you manually log out of PlayStation.com). If your API calls start throwing authentication errors, repeat these steps to grab a fresh code.

### Set Environment Variables

Add your `NPSSO` string to your application's `.env` file:

```env
PSN_NPSSO=your_64_character_npsso_code_here

```

---

## Usage Examples

The package provides a `PsnApi` facade to handle fetching trophies directly. You can pass either a PlayStation Title ID (`CUSA...` or `PPSA...`) or a direct `NPWR` Communication ID as the game identifier.

### 1. Basic Trophy Fetching (`getGameTrophies`)

Pass the game identifier along with a target user's PSN Account ID:

```php
use Hawkiq\LaravelPsnApi\Facades\PsnApi;

// psnAccountId can be fetch from any external service ;) Google it.
// Using a CUSA Title ID (e.g., Uncharted 4 on PS4)
$trophies = PsnApi::getGameTrophies('CUSA00341', 'psnAccountId');

// Using a PPSA Title ID (PS5 native game)
$trophies = PsnApi::getGameTrophies('PPSA01419', 'psnAccountId');

// Using an NPWR Communication ID directly
$trophies = PsnApi::getGameTrophies('NPWR10668_00', 'psnAccountId');

```

### 2. Localization Support

By default, trophy titles and descriptions are returned in English (`en`). Pass a language code as the 3rd parameter to retrieve localized data (e.g., `ar`, `es`, `fr`, `ja`):

```php
$trophies = PsnApi::getGameTrophies('CUSA00341', 'psnAccountId', 'ar');

```

### 3. Fetching User Earned Trophies (`getUserEarnedTrophies`)

To check which specific trophies a player has unlocked for a title:

```php
$earned = PsnApi::getUserEarnedTrophies('NPWR10668_00', 'psnAccountId');

```

---

## Production Ready Controller Example

Here is a copy-and-paste implementation for your Laravel Controller or Livewire Component:

```php
namespace App\Http\Controllers;

use Hawkiq\LaravelPsnApi\Facades\PsnApi;
use Illuminate\Http\JsonResponse;

class TrophyImportController extends Controller
{
    public function import(string $gameId, string $psnAccountId): JsonResponse
    {
        try {
            // Fetch raw trophy payload from PSN
            $data = PsnApi::getGameTrophies($gameId, $psnAccountId);
            $trophies = $data['trophies'] ?? [];

            $formatted = [];

            foreach ($trophies as $t) {
                $formatted[] = [
                    'trophy_id'   => $t['trophyId'],
                    'title'       => $t['trophyName'],
                    'type'        => $t['trophyType'], // bronze, silver, gold, platinum
                    'icon_url'    => $t['trophyIconUrl'],
                    'description' => $t['trophyDetail'] ?? '',
                    'earned_rate' => $t['trophyEarnedRate'] ?? '0.0',
                    'is_hidden'   => $t['trophyHidden'] ?? false,
                ];
            }

            return response()->json([
                'success' => true,
                'total'   => count($formatted),
                'data'    => $formatted,
            ]);

        } catch (\InvalidArgumentException $e) {
            // Invalid Game ID format or player hasn't played the title
            return response()->json(['error' => $e->getMessage()], 400);

        } catch (\RuntimeException $e) {
            // Network failure, expired NPSSO, or PSN API downtime
            return response()->json(['error' => 'PSN API Error: ' . $e->getMessage()], 502);
        }
    }
}

```
