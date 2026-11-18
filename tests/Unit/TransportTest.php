<?php

namespace Hborras\TwitterAdsSDK\Tests\Unit;

use Hborras\TwitterAdsSDK\TwitterAds;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\RateLimit;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\ServiceUnavailable;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CustomHostTwitterAds extends TwitterAds
{
    const API_VERSION = '99';
    const API_HOST = 'https://example.test';
    const API_HOST_OAUTH = 'https://oauth.example.test';
}

class TransportTest extends TestCase
{
    public function testDefaultsTargetXAdsApiV12(): void
    {
        self::assertSame('12', TwitterAds::API_VERSION);
        self::assertSame('https://ads-api.x.com', TwitterAds::API_HOST);
    }

    public function testBearerInit(): void
    {
        $api = TwitterAds::initWithBearer('key', 'secret', 'token-1');
        self::assertSame('token-1', $api->getBearerToken());

        $api->setBearerToken('token-2');
        self::assertSame('token-2', $api->getBearerToken());

        $api->setBearerToken(null);
        self::assertNull($api->getBearerToken());

        // Legacy form: empty oauth token + bearer in the secret slot.
        self::assertSame('legacy', TwitterAds::init('key', 'secret', '', 'legacy')->getBearerToken());
    }

    public function testSubclassConstantsAreHonoured(): void
    {
        // url() builds from static::API_HOST_OAUTH, so a subclass override must win (it did not with self::).
        self::assertSame(
            'https://oauth.example.test/oauth/authorize?a=1',
            (new CustomHostTwitterAds('key', 'secret'))->url('oauth/authorize', ['a' => 1])
        );
        self::assertSame('https://api.x.com/oauth/authorize?a=1', (new TwitterAds('key', 'secret'))->url('oauth/authorize', ['a' => 1]));
    }

    public function testParseHeadersSplitsOnFirstColonOnly(): void
    {
        $parse = new ReflectionMethod(TwitterAds::class, 'parseHeaders');

        $headers = $parse->invoke(new TwitterAds('k', 's'), "HTTP/2 429\r\nX-Rate-Limit-Reset: 1700000000\r\nLocation: https://a.test:8443/x\r\nretry-after:30");

        self::assertSame([
            'x_rate_limit_reset' => '1700000000',
            'location' => 'https://a.test:8443/x',
            'retry_after' => '30',
        ], $headers);
    }

    public function testRateLimitHeaders(): void
    {
        $e = new RateLimit('RATE_LIMIT', 429, null, [], [
            'x_rate_limit_limit' => '450',
            'x_rate_limit_remaining' => '0',
            'x_rate_limit_reset' => (string)(time() + 60),
        ]);

        self::assertSame('450', $e->getLimit());
        self::assertSame('0', $e->getRemaining());
        self::assertGreaterThan(0, (int)$e->getRetryAfter());
        self::assertLessThanOrEqual(60, (int)$e->getRetryAfter());

        self::assertSame('', (new RateLimit('RATE_LIMIT', 429))->getRetryAfter());
    }

    public function testServiceUnavailableRetryAfter(): void
    {
        self::assertSame('15', (new ServiceUnavailable('X', 503, null, [], ['retry_after' => '15']))->getRetryAfter());
    }

    public function testCaOptionsPreferExplicitPath(): void
    {
        $api = new TwitterAds('k', 's');
        $caOptions = new ReflectionMethod(TwitterAds::class, 'caOptions');

        $file = tempnam(sys_get_temp_dir(), 'ca_');
        try {
            $api->setCaInfo($file);
            self::assertSame([CURLOPT_CAINFO => $file], $caOptions->invoke($api));

            $api->setCaInfo(sys_get_temp_dir());
            self::assertSame([CURLOPT_CAPATH => sys_get_temp_dir()], $caOptions->invoke($api));
        } finally {
            unlink($file);
        }
    }

    public function testCaOptionsUseSslCertFileEnv(): void
    {
        $api = new TwitterAds('k', 's');
        $caOptions = new ReflectionMethod(TwitterAds::class, 'caOptions');
        $file = tempnam(sys_get_temp_dir(), 'ca_');
        $previous = getenv('SSL_CERT_FILE');
        putenv('SSL_CERT_FILE=' . $file);
        try {
            if (ini_get('curl.cainfo')) {
                self::assertSame([], $caOptions->invoke($api));
            } else {
                self::assertSame([CURLOPT_CAINFO => $file], $caOptions->invoke($api));
            }
        } finally {
            putenv($previous === false ? 'SSL_CERT_FILE' : 'SSL_CERT_FILE=' . $previous);
            unlink($file);
        }
    }
}
