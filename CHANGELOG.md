# Changelog

## v12.0.0 (unreleased)

First release of the maintained fork. See [docs/upgrading-from-v11.md](docs/upgrading-from-v11.md).

### Added
- `TwitterAds\Api` layer: stateless, account-scoped clients `AccountApi`, `CardApi`, `ScheduledTweetApi`
  (with `preview()` for tweet previews) and `MediaLibraryApi`, plus `ParamNormalizer`.
- `BearerTokenRefresher`: refreshes the OAuth 2.0 token and retries once on 401.
- `TwitterAds::initWithBearer()`, `setBearerToken()`, `getBearerToken()`.
- `Response::getHeaders()`, `RateLimit::getLimit()`, `RateLimit::getRemaining()`.
- Offline unit tests (`composer test`); live tests moved to the `Integration` suite.
- Markdown docs (`README.md`, `docs/`) and `examples/api_*.php`.

### Changed
- Default host `https://ads-api.x.com` and API version `12`; OAuth host `https://api.x.com`.
- Host/version constants use late static binding (`static::`), so subclass overrides take effect.
- PHP `^8.1`; PHPUnit `^10.5 || ^11`.
- TLS verification uses the system CA store (`SSL_CERT_FILE` / `SSL_CERT_DIR` / OpenSSL defaults) instead of always
  forcing the bundled `src/cacert.pem` (Mozilla, July 2020), which is now only a fallback. New `setCaInfo()`.

### Fixed
- PHP 8.4 deprecations: implicitly nullable parameters, required parameter after optional in error classes.
- PHP 8.2 dynamic property deprecation in `Resource` (`#[\AllowDynamicProperties]`).
- `PUT` with a raw JSON body now sends `Content-Type: application/json`.
- Response header/body split uses `CURLINFO_HEADER_SIZE` (100 Continue, proxies, bodies containing blank lines).
- Header lines without a space after `:` no longer raise warnings.
- `RateLimit::getRetryAfter()` returned `x-rate-limit-limit`; it now returns seconds until reset (or `retry-after`).
- `RateLimit` no longer raises undefined index warnings when headers are missing.
- `ServiceUnavailable::getRetryAfter()` never found the `retry-after` header.
- Error classes dropped the `$previous` exception.
- `manageErrors()` handled only object bodies (broke with `setDecodeJsonAsArray(true)`).
- Chunked upload could loop forever when processing failed or `check_after_secs` was missing.
- `PromotedTweetPutNotAllowedException` passed message and code in the wrong order (TypeError on PHP 8).
- `curl_close()` is only called on PHP < 8 (no-op since 8.0, deprecated in 8.5).

### Removed
- `README.rst` (replaced by `README.md`), `.travis.yml`, unused media/list fixtures in `tests/` and `examples/`.

### Deprecated
- `Response::getsHeaders()`; use `getHeaders()`.
- `TwitterAds::upload()` / `UPLOAD_HOST` (legacy v1.1 media upload); use the X API v2 media upload.
