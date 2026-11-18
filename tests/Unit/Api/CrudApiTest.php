<?php

namespace Hborras\TwitterAdsSDK\Tests\Unit\Api;

use Hborras\TwitterAdsSDK\TwitterAds\Api\AccountApi;
use Hborras\TwitterAdsSDK\TwitterAds\Api\BearerTokenRefresher;
use Hborras\TwitterAdsSDK\TwitterAds\Api\CardApi;
use Hborras\TwitterAdsSDK\TwitterAds\Api\MediaLibraryApi;
use Hborras\TwitterAdsSDK\TwitterAds\Api\ScheduledTweetApi;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\NotAuthorized;
use Hborras\TwitterAdsSDK\TwitterAdsException;
use InvalidArgumentException;

class CrudApiTest extends ApiTestCase
{
    public function testAccountGet(): void
    {
        $client = $this->client();
        $client->expects(self::once())->method('get')
            ->with('accounts/' . self::ACCOUNT_ID, [])
            ->willReturn(self::response(['data' => ['id' => self::ACCOUNT_ID]]));

        self::assertSame(['id' => self::ACCOUNT_ID], (new AccountApi($client, self::ACCOUNT_ID))->get());
    }

    public function testCardForTypeIsImmutableAndSendsJson(): void
    {
        $client = $this->client();
        $client->expects(self::once())->method('post')
            ->with(
                'accounts/' . self::ACCOUNT_ID . '/cards/video_conversation',
                ['raw' => '{"name":"Ｘカード","media_key":"13_1"}']
            )
            ->willReturn(self::response(['data' => ['id' => 'c1']]));

        $cards = new CardApi($client, self::ACCOUNT_ID);
        $video = $cards->forType(CardApi::TYPE_VIDEO_CONVERSATION);

        self::assertSame('', $cards->getType());
        self::assertSame(['id' => 'c1'], $video->create(['name' => 'Ｘカード', 'media_key' => '13_1'], true));
    }

    public function testCardRejectsUnsafeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CardApi($this->client(), self::ACCOUNT_ID))->forType('../accounts');
    }

    public function testScheduledTweetUpdateAndPreview(): void
    {
        $client = $this->client();
        $client->expects(self::once())->method('put')
            ->with('accounts/' . self::ACCOUNT_ID . '/scheduled_tweets/123', ['text' => 'hi', 'nullcast' => 'true'])
            ->willReturn(self::response(['data' => ['id' => '123']]));
        $client->expects(self::once())->method('get')
            ->with('accounts/' . self::ACCOUNT_ID . '/tweet_previews', ['tweet_ids' => '123', 'tweet_type' => 'SCHEDULED'])
            ->willReturn(self::response(['data' => [['tweet_id' => '123']]]));

        $api = new ScheduledTweetApi($client, self::ACCOUNT_ID);

        self::assertSame(['id' => '123'], $api->update('123', ['text' => 'hi', 'nullcast' => true, 'card_uri' => null]));
        self::assertSame([['tweet_id' => '123']], $api->preview(['tweet_ids' => ['123'], 'tweet_type' => 'SCHEDULED']));
    }

    public function testAllPagesFollowsCursor(): void
    {
        $client = $this->client();
        $path = 'accounts/' . self::ACCOUNT_ID . '/media_library';
        $client->expects(self::exactly(2))->method('get')
            ->willReturnCallback(function (string $p, array $params) use ($path) {
                self::assertSame($path, $p);

                return isset($params['cursor'])
                    ? self::response(['data' => [['media_key' => 'b']], 'next_cursor' => null])
                    : self::response(['data' => [['media_key' => 'a']], 'next_cursor' => 'c2']);
            });

        $items = (new MediaLibraryApi($client, self::ACCOUNT_ID))->allPages(['count' => 1000]);

        self::assertSame([['media_key' => 'a'], ['media_key' => 'b']], $items);
    }

    public function testDeleteEncodesId(): void
    {
        $client = $this->client();
        $client->expects(self::once())->method('delete')
            ->with('accounts/' . self::ACCOUNT_ID . '/media_library/3_1%2F2', [])
            ->willReturn(self::response(['data' => ['deleted' => true]]));

        self::assertSame(['deleted' => true], (new MediaLibraryApi($client, self::ACCOUNT_ID))->delete('3_1/2'));
    }

    public function testRefreshesBearerOnceOn401(): void
    {
        $client = $this->client();
        $client->method('getBearerToken')->willReturn('stale');
        $client->expects(self::once())->method('setBearerToken')->with('fresh');
        $calls = 0;
        $client->expects(self::exactly(2))->method('get')->willReturnCallback(function () use (&$calls) {
            if ($calls++ === 0) {
                throw new NotAuthorized(TwitterAdsException::NOT_AUTHORIZED, 401);
            }

            return self::response(['data' => ['id' => self::ACCOUNT_ID]]);
        });

        $refresher = $this->createMock(BearerTokenRefresher::class);
        $refresher->expects(self::once())->method('refresh')->with('stale')->willReturn('fresh');

        self::assertSame(['id' => self::ACCOUNT_ID], (new AccountApi($client, self::ACCOUNT_ID, $refresher))->get());
    }

    public function test401WithoutRefresherIsRethrown(): void
    {
        $client = $this->client();
        $client->method('getBearerToken')->willReturn('stale');
        $client->method('get')->willThrowException(new NotAuthorized(TwitterAdsException::NOT_AUTHORIZED, 401));

        $this->expectException(NotAuthorized::class);
        (new AccountApi($client, self::ACCOUNT_ID))->get();
    }
}
