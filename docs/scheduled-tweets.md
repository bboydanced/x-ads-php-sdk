# Scheduled Tweets — `ScheduledTweetApi`

Endpoint: `accounts/{account_id}/scheduled_tweets`

```php
use Hborras\TwitterAdsSDK\TwitterAds\Api\ScheduledTweetApi;

$scheduled = new ScheduledTweetApi($client, $accountId);
```

## Create

```php
$tweet = $scheduled->create([
    'as_user_id'   => $userId,                                  // required: the @handle that will post
    'scheduled_at' => new DateTimeImmutable('2026-12-01 09:00', new DateTimeZone('Asia/Tokyo')),
    'text'         => 'New season is here',
    'media_keys'   => ['3_1234567890', '3_1234567891'],         // sent as "3_...,3_..."
    'card_uri'     => 'card://1234567890',
    'nullcast'     => true,                                     // promoted-only Tweet
    'name'         => 'internal name',
]);

$tweet['id'];               // scheduled Tweet id
$tweet['scheduled_status']; // "SCHEDULED"
```

`scheduled_at` must be in the future. `null` values are dropped, so optional fields can be passed as `null`.

## Read, update, delete

```php
$one     = $scheduled->find($scheduledTweetId);
$all     = $scheduled->allPages(['count' => 200, 'q' => 'season']);
$updated = $scheduled->update($scheduledTweetId, ['text' => 'Edited text']);
$deleted = $scheduled->delete($scheduledTweetId);

$deleted['scheduled_status'] === 'DELETED';
```

After X publishes it, `scheduled_status` becomes `SUCCESS` and `tweet_id` is set.

## Preview

`GET accounts/{account_id}/tweet_previews`:

```php
$previews = $scheduled->preview([
    'tweet_ids'  => [$scheduledTweetId],
    'tweet_type' => 'SCHEDULED', // DRAFT | PUBLISHED | SCHEDULED
]);

$previews[0]['preview']; // HTML preview
```
