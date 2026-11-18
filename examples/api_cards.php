<?php

// Usage: X_CONSUMER_KEY=... X_CONSUMER_SECRET=... X_ACCESS_TOKEN=... X_ACCOUNT_ID=... X_MEDIA_KEY=... php api_cards.php

use Hborras\TwitterAdsSDK\TwitterAds;
use Hborras\TwitterAdsSDK\TwitterAds\Api\CardApi;
use Hborras\TwitterAdsSDK\TwitterAdsException;

require __DIR__ . '/../autoload.php';

$client = TwitterAds::initWithBearer(getenv('X_CONSUMER_KEY'), getenv('X_CONSUMER_SECRET'), getenv('X_ACCESS_TOKEN'));
$cards = new CardApi($client, getenv('X_ACCOUNT_ID'));

try {
    // Components-based website card: nested payload => JSON body
    $card = $cards->create([
        'name' => 'SDK example card',
        'components' => [
            ['type' => 'MEDIA', 'media_key' => getenv('X_MEDIA_KEY')],
            [
                'type' => 'BUTTON',
                'label' => ['type' => 'ENUM', 'value' => 'VISIT_SITE'],
                'destination' => ['type' => 'WEBSITE', 'url' => 'https://example.com'],
            ],
        ],
    ], true);
    echo "created {$card['id']} {$card['card_uri']}\n";

    $card = $cards->update($card['id'], ['name' => 'SDK example card (renamed)'], true);
    echo "renamed: {$card['name']}\n";

    foreach ($cards->allPages(['count' => 200]) as $item) {
        echo "{$item['id']}\t{$item['card_type']}\t{$item['name']}\n";
    }

    $cards->delete($card['id']);
    echo "deleted\n";
} catch (TwitterAdsException $e) {
    fwrite(STDERR, $e->getCode() . ' ' . json_encode($e->getErrors()) . "\n");
    exit(1);
}
