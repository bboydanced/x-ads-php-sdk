<?php

namespace Hborras\TwitterAdsSDK\Tests\Unit\Api;

use DateTimeImmutable;
use DateTimeZone;
use Hborras\TwitterAdsSDK\TwitterAds\Api\ParamNormalizer;
use PHPUnit\Framework\TestCase;

class ParamNormalizerTest extends TestCase
{
    public function testNormalizesValues(): void
    {
        $at = new DateTimeImmutable('2026-01-02 03:04:05', new DateTimeZone('UTC'));

        self::assertSame([
            'scheduled_at' => '2026-01-02T03:04:05+00:00',
            'media_keys' => '3_1,13_2',
            'nullcast' => 'true',
            'promoted' => 'false',
            'count' => '0',
            'text' => 'hello',
        ], ParamNormalizer::normalize([
            'scheduled_at' => $at,
            'media_keys' => ['3_1', '13_2'],
            'nullcast' => true,
            'promoted' => false,
            'count' => 0,
            'text' => 'hello',
            'skip_null' => null,
            'skip_empty' => '',
        ]));
    }
}
