<?php

namespace Hborras\TwitterAdsSDK\Tests\Unit\OAuth;

use Hborras\TwitterAdsSDK\Token;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    public static function tokenProvider(): array
    {
        return [
            ['oauth_token=key&oauth_token_secret=secret', 'key', 'secret'],
            ['oauth_token=key%2Bkey&oauth_token_secret=secret', 'key+key', 'secret'],
            ['oauth_token=key~key&oauth_token_secret=secret', 'key~key', 'secret'],
        ];
    }

    #[DataProvider('tokenProvider')]
    public function testToString(string $expected, string $key, string $secret): void
    {
        self::assertEquals($expected, (new Token($key, $secret))->__toString());
    }
}
