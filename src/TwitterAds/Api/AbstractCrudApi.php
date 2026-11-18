<?php

namespace Hborras\TwitterAdsSDK\TwitterAds\Api;

use Hborras\TwitterAdsSDK\TwitterAdsException;

/**
 * CRUD over an account-scoped collection: accounts/{account_id}/{collection}[/{id}].
 */
abstract class AbstractCrudApi extends AbstractApi
{
    /** Hard stop for allPages() so a misbehaving cursor can never loop forever. */
    const MAX_PAGES = 1000;

    /**
     * Collection path relative to accounts/{account_id}, e.g. 'cards' or 'scheduled_tweets'.
     */
    abstract protected function collection(): string;

    protected function collectionPath(): string
    {
        return $this->accountPath($this->collection());
    }

    protected function itemPath(string $id): string
    {
        return $this->collectionPath() . '/' . rawurlencode($id);
    }

    /**
     * One page. Returns the whole body: ['data' => [...], 'next_cursor' => ?string, 'request' => ..., ...].
     *
     * @throws TwitterAdsException
     */
    public function all(array $params = []): array
    {
        return $this->send('get', $this->collectionPath(), ParamNormalizer::normalize($params));
    }

    /**
     * Follows next_cursor and returns the merged `data` of every page.
     *
     * @throws TwitterAdsException
     */
    public function allPages(array $params = [], int $maxPages = self::MAX_PAGES): array
    {
        $items = [];
        $page = 0;
        do {
            $body = $this->all($params);
            $items = array_merge($items, self::data($body));
            $params['cursor'] = isset($body['next_cursor']) ? $body['next_cursor'] : null;
        } while (!empty($params['cursor']) && ++$page < $maxPages);

        return $items;
    }

    /**
     * @return array the entity (`data` of the response)
     * @throws TwitterAdsException
     */
    public function find(string $id, array $params = []): array
    {
        return self::data($this->send('get', $this->itemPath($id), ParamNormalizer::normalize($params)));
    }

    /**
     * @param bool $asJson send $data as a JSON body (required by endpoints such as cards) instead of form params
     * @return array the created entity
     * @throws TwitterAdsException
     */
    public function create(array $data, bool $asJson = false): array
    {
        return self::data($this->send('post', $this->collectionPath(), $this->payload($data, $asJson)));
    }

    /**
     * @return array the updated entity
     * @throws TwitterAdsException
     */
    public function update(string $id, array $data, bool $asJson = false): array
    {
        return self::data($this->send('put', $this->itemPath($id), $this->payload($data, $asJson)));
    }

    /**
     * @return array the deleted entity (most endpoints return it with deleted=true)
     * @throws TwitterAdsException
     */
    public function delete(string $id): array
    {
        return self::data($this->send('delete', $this->itemPath($id)));
    }

    private function payload(array $data, bool $asJson): array
    {
        return $asJson ? self::jsonBody($data) : ParamNormalizer::normalize($data);
    }
}
