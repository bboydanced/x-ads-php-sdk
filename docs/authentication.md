# Authentication

`TwitterAds` is the HTTP client. All `Api` classes take an instance of it.

## OAuth 2.0 Bearer (recommended)

Use the user access token obtained through the OAuth 2.0 Authorization Code (PKCE) flow, with the
`ads.read` scope (plus `ads.write` for writes).

```php
use Hborras\TwitterAdsSDK\TwitterAds;

$client = TwitterAds::initWithBearer($consumerKey, $consumerSecret, $accessToken);

// Swap the token later (e.g. after refreshing it)
$client->setBearerToken($newAccessToken);
$client->getBearerToken(); // current token or null
```

The v11 form still works: `TwitterAds::init($key, $secret, '', $accessToken)`.

The SDK does not perform the OAuth 2.0 flow or store tokens. That belongs to your application.

## OAuth 1.0a

```php
$client = TwitterAds::init($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
```

## Refreshing an expired token automatically

Implement `BearerTokenRefresher` and pass it to any `Api` class. When a request fails with
`401 Not Authorized` while Bearer auth is used, the Api class:

1. calls `refresh($staleToken)` **once**,
2. sets the returned token on the client (`setBearerToken`),
3. retries the request **once**. A second 401 is thrown to the caller.

A 401 is rejected before X performs the action, so retrying a `POST`/`PUT`/`DELETE` cannot double-write.

```php
use Hborras\TwitterAdsSDK\TwitterAds\Api\BearerTokenRefresher;
use Hborras\TwitterAdsSDK\TwitterAds\Api\CardApi;

final class DbTokenRefresher implements BearerTokenRefresher
{
    public function __construct(private TokenRepository $tokens, private int $tokenId) {}

    public function refresh(string $staleToken): string
    {
        // Use the refresh_token, persist the new pair and return the new access token.
        // If another worker already refreshed it (stored token !== $staleToken), just return the stored one.
        return $this->tokens->refreshIfStale($this->tokenId, $staleToken);
    }
}

$cards = new CardApi($client, $accountId, new DbTokenRefresher($tokens, $tokenId));
```

The client is updated in place, so other Api objects sharing the same `$client` use the new token too.

## Client options

```php
$client->setTimeouts(10, 30);             // connect timeout, total timeout (seconds)
$client->setUserAgent('my-app/1.0');
$client->setProxy([
    'CURLOPT_PROXY' => 'proxy.local',
    'CURLOPT_PROXYUSERPWD' => 'user:pass',
    'CURLOPT_PROXYPORT' => 3128,
]);
$client->setCaInfo('/path/to/ca-bundle.pem'); // or a hashed cert directory
```

TLS peers are verified against, in order: `setCaInfo()`, php.ini `curl.cainfo`, the system store
(`SSL_CERT_FILE`, `SSL_CERT_DIR`, then OpenSSL's default file/dir), and finally the bundled `src/cacert.pem`.

## Sandbox

`TwitterAds::init(..., sandbox: true)` sends requests to `API_HOST_SANDBOX`
(`https://ads-api-sandbox.twitter.com`). X's current docs no longer describe a sandbox, so check it is still available
before you rely on it.

## Custom host / version

The host and version are class constants resolved with late static binding, so a subclass can override them:

```php
class MyTwitterAds extends \Hborras\TwitterAdsSDK\TwitterAds
{
    const API_VERSION = '12';
    const API_HOST = 'https://ads-api.x.com';
}

$client = MyTwitterAds::initWithBearer($key, $secret, $token);
```
