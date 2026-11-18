<?php

namespace Hborras\TwitterAdsSDK\Tests\Unit\OAuth;

use Hborras\TwitterAdsSDK\HmacSha1;
use Hborras\TwitterAdsSDK\SignatureMethod;
use PHPUnit\Framework\Attributes\DataProvider;

class HmacSha1Test extends AbstractSignatureMethodTestCase
{
    protected $name = 'HMAC-SHA1';

    public function getClass(): SignatureMethod
    {
        return new HmacSha1();
    }

    /**
     * Mocks cannot be built in a static provider, so it yields constructor args and the test builds the mocks.
     * A null token argument list means "no token".
     */
    public static function signatureDataProvider(): array
    {
        return [
            ['5CoEcoq7XoKFjwYCieQvuzadeUA=', [], []],
            ['EBw0gHngam3BTx8kfPfNNSyKem4=', ['key', 'secret'], []],
            ['kDsHFZzws2a5M6cAQjfpdNBo+v8=', ['key', 'secret'], ['key', 'secret']],
            ['EBw0gHngam3BTx8kfPfNNSyKem4=', ['key', 'secret'], null],
        ];
    }

    #[DataProvider('signatureDataProvider')]
    public function testBuildSignature(string $expected, array $consumerArgs, ?array $tokenArgs): void
    {
        $token = $tokenArgs === null ? null : $this->getToken(...$tokenArgs);

        self::assertEquals(
            $expected,
            $this->getClass()->buildSignature($this->getRequest(), $this->getConsumer(...$consumerArgs), $token)
        );
    }
}
