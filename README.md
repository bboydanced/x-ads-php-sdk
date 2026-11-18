# X Ads PHP SDK

PHP SDK for the [X (formerly Twitter) Ads API](https://docs.x.com/x-ads-api) **v12**.

Maintained fork of [`hborras/twitter-php-ads-sdk`](https://github.com/hborras/twitter-php-ads-sdk) (no longer maintained).
The package name and the `Hborras\TwitterAdsSDK` namespace are kept so existing code keeps working.

- PHP `^8.1`, `ext-curl`, `ext-json`
- Host `https://ads-api.x.com`, API version `12`
- OAuth 1.0a **and** OAuth 2.0 Bearer tokens
- Stateless, account-scoped clients for Accounts, Cards, Scheduled Tweets and the Media Library (`TwitterAds\Api\*`)

## Installation

The fork is not on Packagist. Add it as a VCS repository:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/bboydanced/x-ads-php-sdk" }
    ],
    "require": {
        "hborras/twitter-php-ads-sdk": "^12.0"
    }
}
```

```bash
composer update hborras/twitter-php-ads-sdk
```

## Quick start

```php
use Hborras\TwitterAdsSDK\TwitterAds;
use Hborras\TwitterAdsSDK\TwitterAds\Api\ScheduledTweetApi;

// OAuth 2.0 user access token (needs the ads.read / ads.write scopes)
$client = TwitterAds::initWithBearer($consumerKey, $consumerSecret, $accessToken);
$client->setTimeouts(10, 30); // connect, total (seconds)

$scheduled = new ScheduledTweetApi($client, $accountId);

$tweet = $scheduled->create([
    'as_user_id'   => $userId,
    'text'         => 'Hello from the X Ads API',
    'scheduled_at' => new DateTimeImmutable('+1 day'),
    'nullcast'     => true,
]);

echo $tweet['id'];
```

## Documentation

| Topic | |
|---|---|
| [Authentication](docs/authentication.md) | OAuth 1.0a, OAuth 2.0 Bearer, refreshing tokens on 401 |
| [Api layer overview](docs/api-layer.md) | Shared methods, parameter conversion, return values, pagination |
| [Accounts](docs/accounts.md) | `AccountApi` |
| [Cards](docs/cards.md) | `CardApi` |
| [Scheduled Tweets](docs/scheduled-tweets.md) | `ScheduledTweetApi` (+ tweet previews) |
| [Media Library](docs/media-library.md) | `MediaLibraryApi` |
| [Errors & rate limits](docs/errors-and-rate-limits.md) | Exception classes, retry headers |
| [Upgrading from v11](docs/upgrading-from-v11.md) | Breaking changes and migration |

Runnable scripts are in [`examples/`](examples).

The original resource classes (`Account`, `Campaign`, `LineItem`, `PromotedTweet`, analytics, …) are still available.
They rely on the global `TwitterAds::init()` instance, so prefer the `Api` classes in long-running processes
(queue workers) that serve several accounts or tokens.

## Tests

```bash
composer test                # offline unit tests
composer test:integration    # WARNING: real API calls, needs TEST_* env vars (see tests/sample_env)
```

## License

MIT, see the original project.
