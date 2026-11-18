<?php

namespace Hborras\TwitterAdsSDK\Tests\Unit\Api;

use Hborras\TwitterAdsSDK\Response;
use Hborras\TwitterAdsSDK\TwitterAds;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class ApiTestCase extends TestCase
{
    protected const ACCOUNT_ID = '18ce54d4x5t';

    /** @return TwitterAds&MockObject */
    protected function client(): TwitterAds
    {
        return $this->createMock(TwitterAds::class);
    }

    protected static function response(array $body, int $httpCode = 200): Response
    {
        $response = new Response();
        $response->setHttpCode($httpCode);
        $response->setBody(json_decode(json_encode($body)));

        return $response;
    }
}
