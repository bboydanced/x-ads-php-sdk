# Cards — `CardApi`

`CardApi` targets `accounts/{account_id}/cards` by default. `forType()` returns a **copy** bound to
`accounts/{account_id}/cards/{type}`. The original object is not changed.

```php
use Hborras\TwitterAdsSDK\TwitterAds\Api\CardApi;

$cards = new CardApi($client, $accountId);

$video = $cards->forType(CardApi::TYPE_VIDEO_CONVERSATION); // cards/video_conversation
$image = $cards->forType(CardApi::TYPE_IMAGE_CONVERSATION); // cards/image_conversation
$poll  = $cards->forType(CardApi::TYPE_POLL);               // cards/poll
```

Types must match `[a-z0-9_]+`. Anything else throws `InvalidArgumentException`.

## Cards built from components (JSON body)

`POST accounts/{id}/cards` takes nested `components`, so send it as JSON (`$asJson = true`):

```php
$card = $cards->create([
    'name' => 'Spring campaign',
    'components' => [
        ['type' => 'MEDIA', 'media_key' => '3_1234567890'],
        [
            'type' => 'BUTTON',
            'label' => ['type' => 'ENUM', 'value' => 'VISIT_SITE'],
            'destination' => ['type' => 'WEBSITE', 'url' => 'https://example.com'],
        ],
    ],
], true);

$card['card_uri']; // "card://..." — use it in a Tweet / scheduled Tweet
```

## Typed cards (form parameters)

```php
$card = $video->create([
    'name' => 'Video conversation',
    'media_key' => '13_1234567890',
    'title' => 'Join the conversation',
    'first_cta' => '#Spring',
    'first_cta_tweet' => 'I love spring',
    'thank_you_text' => 'Thanks!',
]);
```

## Read / update / delete

```php
$all  = $cards->allPages(['count' => 200]);            // every card
$one  = $cards->find($cardId);
$card = $cards->update($cardId, ['name' => 'Renamed'], true);
$cards->delete($cardId);
```

The fields available depend on the card type. See the
[X Ads Cards docs](https://docs.x.com/x-ads-api/creatives#cards).
