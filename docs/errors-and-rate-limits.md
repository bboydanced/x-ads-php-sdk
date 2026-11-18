# Errors & rate limits

Every HTTP status `>= 400` throws a subclass of `Hborras\TwitterAdsSDK\TwitterAdsException`.
cURL failures (DNS, timeout, TLS) throw `TwitterAdsException` itself, with the cURL error code.

| Status | Exception (`TwitterAds\Errors\…`) |
|---|---|
| 400 | `BadRequest` |
| 401 | `NotAuthorized` |
| 403 | `Forbidden` |
| 404 | `NotFound` |
| 429 | `RateLimit` |
| 500 / other | `ServerError` |
| 503 | `ServiceUnavailable` |

`getErrors()` returns X's `errors` (or `operation_errors`) array, e.g.
`[{code: "INVALID_PARAMETER", message: "...", parameter: "scheduled_at"}]`. Items are objects.

```php
use Hborras\TwitterAdsSDK\TwitterAds\Errors\NotFound;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\RateLimit;
use Hborras\TwitterAdsSDK\TwitterAdsException;

try {
    $tweet = $scheduled->find($id);
} catch (NotFound $e) {
    // deleted on X
} catch (RateLimit $e) {
    $e->getLimit();      // x-rate-limit-limit
    $e->getRemaining();  // x-rate-limit-remaining
    $e->getResetAt();    // x-rate-limit-reset (unix time)
    $e->getRetryAfter(); // seconds to wait: retry-after header, else reset - now
} catch (TwitterAdsException $e) {
    error_log($e->getCode() . ' ' . json_encode($e->getErrors()));
}
```

`ServiceUnavailable::getRetryAfter()` returns the `retry-after` header when X sends one.

## Response headers

```php
$client->getResponse()->getHeaders(); // lower-case keys with '_' instead of '-', e.g. x_rate_limit_remaining
$client->getLastHttpCode();
$client->getLastBody();
```

`getsHeaders()` still works but is deprecated. Use `getHeaders()` instead.

Never log the client object or exception traces with arguments, because they contain the access token.
