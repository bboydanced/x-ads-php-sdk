<?php

// Usage: X_CONSUMER_KEY=... X_CONSUMER_SECRET=... X_ACCESS_TOKEN=... X_ACCOUNT_ID=... X_USER_ID=... php api_scheduled_tweets.php

use Hborras\TwitterAdsSDK\TwitterAds;
use Hborras\TwitterAdsSDK\TwitterAds\Api\ScheduledTweetApi;
use Hborras\TwitterAdsSDK\TwitterAdsException;

require __DIR__ . '/../autoload.php';

$client = TwitterAds::initWithBearer(getenv('X_CONSUMER_KEY'), getenv('X_CONSUMER_SECRET'), getenv('X_ACCESS_TOKEN'));
$scheduled = new ScheduledTweetApi($client, getenv('X_ACCOUNT_ID'));

try {
    $tweet = $scheduled->create([
        'as_user_id' => getenv('X_USER_ID'),
        'text' => 'Scheduled from x-ads-php-sdk',
        'scheduled_at' => new DateTimeImmutable('+1 day'),
        'nullcast' => true,
    ]);
    echo "created {$tweet['id']} ({$tweet['scheduled_status']})\n";

    $tweet = $scheduled->update($tweet['id'], ['text' => 'Scheduled from x-ads-php-sdk (edited)']);
    echo "updated: {$tweet['text']}\n";

    $preview = $scheduled->preview(['tweet_ids' => [$tweet['id']], 'tweet_type' => 'SCHEDULED']);
    echo 'preview items: ' . count($preview) . "\n";

    foreach ($scheduled->allPages(['count' => 200]) as $item) {
        echo "{$item['id']}\t{$item['scheduled_status']}\t{$item['scheduled_at']}\n";
    }

    $deleted = $scheduled->delete($tweet['id']);
    echo "deleted: {$deleted['scheduled_status']}\n";
} catch (TwitterAdsException $e) {
    fwrite(STDERR, $e->getCode() . ' ' . json_encode($e->getErrors()) . "\n");
    exit(1);
}
