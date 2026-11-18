<?php

namespace Hborras\TwitterAdsSDK\TwitterAds\Api;

use Hborras\TwitterAdsSDK\TwitterAds;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\NotAuthorized;
use Hborras\TwitterAdsSDK\TwitterAdsException;
use InvalidArgumentException;

/**
 * Stateless, account-scoped Ads API client. Unlike the Resource classes it never touches TwitterAds::instance(),
 * so one process (e.g. a queue worker) can safely serve many accounts/tokens.
 */
abstract class AbstractApi
{
    /** @var TwitterAds */
    protected $client;
    /** @var string */
    protected $accountId;
    /** @var BearerTokenRefresher|null */
    protected $refresher;

    public function __construct(TwitterAds $client, string $accountId, ?BearerTokenRefresher $refresher = null)
    {
        if ($accountId === '') {
            throw new InvalidArgumentException('accountId must not be empty');
        }
        $this->client = $client;
        $this->accountId = $accountId;
        $this->refresher = $refresher;
    }

    public function getClient(): TwitterAds
    {
        return $this->client;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    protected function accountPath(string $suffix = ''): string
    {
        $path = 'accounts/' . rawurlencode($this->accountId);

        return $suffix === '' ? $path : $path . '/' . ltrim($suffix, '/');
    }

    /**
     * Sends the request and returns the whole decoded body as an array ([] for an empty body).
     * On 401 with Bearer auth and a refresher, the token is refreshed and the request retried once
     * (a 401 is rejected before X acts on it, so the retry cannot double-write).
     *
     * @param string $method get|post|put|delete
     * @throws TwitterAdsException
     */
    protected function send(string $method, string $path, array $params = []): array
    {
        try {
            $response = $this->client->$method($path, $params);
        } catch (NotAuthorized $e) {
            $staleToken = $this->client->getBearerToken();
            if ($this->refresher === null || $staleToken === null) {
                throw $e;
            }
            $this->client->setBearerToken($this->refresher->refresh($staleToken));
            $response = $this->client->$method($path, $params);
        }

        return self::toArray($response->getBody());
    }

    protected static function jsonBody(array $data): array
    {
        return ['raw' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
    }

    protected static function data(array $body): array
    {
        return isset($body['data']) && is_array($body['data']) ? $body['data'] : [];
    }

    /**
     * @param mixed $body stdClass / array / null as returned by Response::getBody()
     */
    protected static function toArray($body): array
    {
        if ($body === null || $body === '') {
            return [];
        }
        if (is_array($body) || is_object($body)) {
            return json_decode(json_encode($body), true) ?: [];
        }

        return [];
    }
}
