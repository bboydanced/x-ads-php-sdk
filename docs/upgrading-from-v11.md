# Upgrading from v11

## Breaking / behaviour changes

| Change | Impact |
|---|---|
| PHP `^8.1` required | Drop PHP 7.x / 8.0 |
| Default API: `https://ads-api.x.com`, version `12` (was `ads-api.twitter.com`, `11`) | Requests go to v12. Check the [v12 changes](https://docs.x.com/x-ads-api/fundamentals/versioning) for the endpoints you use |
| `API_HOST_OAUTH` is `https://api.x.com` | Affects `oauth()`, `oauth2()`, `url()`, `verifyCredentials()` |
| Host/version constants use `static::` | **Subclass overrides now take effect.** Before, a subclass defining `API_VERSION`/`API_HOST` was silently ignored and v11 on twitter.com was called |
| `PUT` with `['raw' => json]` sends `Content-Type: application/json` | Matches `POST` |
| Header parsing uses the cURL header size | Correct headers behind proxies / `100 Continue` |
| `RateLimit::getRetryAfter()` returns seconds to wait | Before, it wrongly returned `x-rate-limit-limit` |
| `ServiceUnavailable::getRetryAfter()` reads the `retry-after` header | Before, it was always `''` |
| Chunked `upload()` throws when processing fails, or after 60 status checks | Before, it could loop forever |
| `$previous` is passed through in all error classes | `getPrevious()` now works |

Unchanged: the package name, the `Hborras\TwitterAdsSDK` namespace, `init()`, the return types of
`get/post/put/delete` (`Response`), `getLastBody()`, the exception classes and `getErrors()`.

## Adopting the Api layer

If your application wraps raw `get/post/put/delete` calls in its own "service per endpoint" classes,
they map directly onto the new classes:

| Typical wrapper | SDK |
|---|---|
| `AccountService::detail($id)` | `(new AccountApi($client, $id))->get()` |
| `CardService::changeType($t)` then `create/update` | `$cardApi->forType($t)->create($data, $asJson)` |
| `ScheduleService::create/update/detail/delete` | `ScheduledTweetApi::create/update/find/delete` |
| `ScheduleService::tweetPreview($p)` | `ScheduledTweetApi::preview($p)` |
| `MediaLibraryService::*` | `MediaLibraryApi::*` |
| `getAll($params)` (loop on `next_cursor`) | `allPages($params)` |
| `get($params)` (raw body) | `all($params)` |
| `create($data, isRawData: true)` | `create($data, true)` |
| refresh token + retry once on 401 | pass a `BearerTokenRefresher` |

Differences to check while migrating:

- **2xx = success.** A hand-written wrapper that returned `[]` for anything other than `200` (e.g. `201 Created`)
  now gets the entity.
- `ParamNormalizer` only drops `null` and `''`. A wrapper using `array_filter()` also dropped `'0'`, which is now sent.
- Results are always arrays, never `stdClass`.
- Logging is left to you. Wrap the Api call, or read `$client->getResponse()` afterwards.
