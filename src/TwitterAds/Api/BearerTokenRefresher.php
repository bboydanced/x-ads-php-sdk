<?php

namespace Hborras\TwitterAdsSDK\TwitterAds\Api;

/**
 * Supplies a fresh OAuth 2.0 access token when the Ads API rejects the current one with 401.
 * The Api layer calls it at most once per request, then retries that request once.
 */
interface BearerTokenRefresher
{
    /**
     * @param string $staleToken the Bearer token that was rejected
     * @return string the new Bearer token
     */
    public function refresh(string $staleToken): string;
}
