<?php

namespace Hborras\TwitterAdsSDK\TwitterAds\Api;

use Hborras\TwitterAdsSDK\TwitterAdsException;

/**
 * GET accounts/{account_id}
 */
class AccountApi extends AbstractApi
{
    /**
     * @return array the account (`data` of the response)
     * @throws TwitterAdsException
     */
    public function get(array $params = []): array
    {
        return self::data($this->send('get', $this->accountPath(), ParamNormalizer::normalize($params)));
    }
}
