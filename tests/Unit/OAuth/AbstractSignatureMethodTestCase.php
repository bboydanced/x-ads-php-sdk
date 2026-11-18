<?php

namespace Hborras\TwitterAdsSDK\Tests\Unit\OAuth;

use Hborras\TwitterAdsSDK\Consumer;
use Hborras\TwitterAdsSDK\Request;
use Hborras\TwitterAdsSDK\SignatureMethod;
use Hborras\TwitterAdsSDK\Token;
use PHPUnit\Framework\TestCase;

abstract class AbstractSignatureMethodTestCase extends TestCase
{
    protected $name;

    abstract public function getClass(): SignatureMethod;

    public function testGetName(): void
    {
        self::assertEquals($this->name, $this->getClass()->getName());
    }

    protected function getRequest(): Request
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->method('getSignatureBaseString')->willReturn('');

        return $request;
    }

    protected function getConsumer($key = null, $secret = null, $callbackUrl = null): Consumer
    {
        return $this->getMockBuilder(Consumer::class)
            ->setConstructorArgs([$key, $secret, $callbackUrl])
            ->getMock();
    }

    protected function getToken($key = null, $secret = null): Token
    {
        return $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$key, $secret])
            ->getMock();
    }
}
