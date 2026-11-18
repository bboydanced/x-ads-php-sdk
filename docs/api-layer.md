# Api layer overview

Namespace: `Hborras\TwitterAdsSDK\TwitterAds\Api`

| Class | Endpoint(s) |
|---|---|
| `AccountApi` | `GET accounts/{account_id}` |
| `CardApi` | `accounts/{account_id}/cards[/{type}]` |
| `ScheduledTweetApi` | `accounts/{account_id}/scheduled_tweets`, `GET accounts/{account_id}/tweet_previews` |
| `MediaLibraryApi` | `accounts/{account_id}/media_library` |

Every class is constructed the same way:

```php
new CardApi(TwitterAds $client, string $accountId, ?BearerTokenRefresher $refresher = null);
```

They do not use the global `TwitterAds::instance()`, so many accounts and tokens can be used side by side in one process.

## Shared CRUD methods (`AbstractCrudApi`)

| Method | HTTP | Returns |
|---|---|---|
| `all(array $params = [])` | `GET {collection}` | **whole body** of one page: `['data' => [...], 'next_cursor' => ?string, 'request' => [...]]` |
| `allPages(array $params = [], int $maxPages = 1000)` | `GET {collection}` repeatedly | merged `data` of every page (follows `next_cursor`) |
| `find(string $id, array $params = [])` | `GET {collection}/{id}` | the entity (`data`) |
| `create(array $data, bool $asJson = false)` | `POST {collection}` | the created entity |
| `update(string $id, array $data, bool $asJson = false)` | `PUT {collection}/{id}` | the updated entity |
| `delete(string $id)` | `DELETE {collection}/{id}` | the deleted entity |

- Results are plain PHP arrays (decoded JSON). An empty body gives `[]`.
- Any 2xx status is a success. Every status `>= 400` throws, see [errors](errors-and-rate-limits.md).
- IDs are URL-encoded when inserted into the path.

## Parameter conversion

Unless `$asJson` is `true`, `$data` / `$params` go through `ParamNormalizer::normalize()`:

| PHP value | Sent as |
|---|---|
| `null`, `''` | dropped |
| `DateTimeInterface` | ISO 8601 (`2026-01-02T03:04:05+00:00`) |
| `array` | comma-separated list (`['a','b']` → `a,b`) |
| `true` / `false` | `true` / `false` |
| anything else | `(string)` |

With `$asJson = true` the array is sent unchanged as a JSON body (`Content-Type: application/json`).
Use that for endpoints that take nested objects, such as `POST accounts/{id}/cards` with `components`.

## Pagination

```php
// One page, you control the cursor
$page = $api->all(['count' => 200]);
while (true) {
    foreach ($page['data'] as $item) { /* ... */ }
    if (empty($page['next_cursor'])) break;
    $page = $api->all(['count' => 200, 'cursor' => $page['next_cursor']]);
}

// Or let the SDK walk every page
$items = $api->allPages(['count' => 200]);
```

`allPages()` keeps every item in memory. For very large collections use `all()` page by page.
