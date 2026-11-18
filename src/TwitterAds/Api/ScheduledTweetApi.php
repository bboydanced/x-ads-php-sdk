<?php

namespace Hborras\TwitterAdsSDK\TwitterAds\Api;

use Hborras\TwitterAdsSDK\TwitterAdsException;

/**
 * accounts/{account_id}/scheduled_tweets and accounts/{account_id}/tweet_previews
 */
class ScheduledTweetApi extends AbstractCrudApi
{
    protected function collection(): string
    {
        return 'scheduled_tweets';
    }

    /**
     * GET accounts/{account_id}/tweet_previews, e.g. ['tweet_ids' => [...], 'tweet_type' => 'SCHEDULED'].
     *
     * @return array `data` of the response
     * @throws TwitterAdsException
     */
    public function preview(array $params): array
    {
        return self::data($this->send('get', $this->accountPath('tweet_previews'), ParamNormalizer::normalize($params)));
    }
}
