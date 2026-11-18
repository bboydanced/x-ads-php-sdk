<?php

// Usage: X_CONSUMER_KEY=... X_CONSUMER_SECRET=... X_ACCESS_TOKEN=... X_ACCOUNT_ID=... X_MEDIA_KEY=... php api_media_library.php
// X_MEDIA_KEY comes from the X API v2 media upload (https://api.x.com/2/media/upload); see docs/media-library.md.

use Hborras\TwitterAdsSDK\TwitterAds;
use Hborras\TwitterAdsSDK\TwitterAds\Api\MediaLibraryApi;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\RateLimit;
use Hborras\TwitterAdsSDK\TwitterAdsException;

require __DIR__ . '/../autoload.php';

$client = TwitterAds::initWithBearer(getenv('X_CONSUMER_KEY'), getenv('X_CONSUMER_SECRET'), getenv('X_ACCESS_TOKEN'));
$library = new MediaLibraryApi($client, getenv('X_ACCOUNT_ID'));

try {
    $media = $library->create([
        'media_key' => getenv('X_MEDIA_KEY'),
        'name' => 'SDK example media',
        'file_name' => 'example.jpg',
    ]);
    echo "added {$media['media_key']} {$media['media_url']}\n";

    $media = $library->update($media['media_key'], ['name' => 'SDK example media (renamed)']);
    echo "renamed: {$media['name']}\n";

    foreach ($library->allPages(['count' => 50, 'media_type' => 'IMAGE']) as $item) {
        echo "{$item['media_key']}\t{$item['media_type']}\t{$item['name']}\n";
    }

    $deleted = $library->delete($media['media_key']);
    echo 'deleted: ' . var_export($deleted['deleted'] ?? null, true) . "\n";
} catch (RateLimit $e) {
    fwrite(STDERR, "rate limited, retry in {$e->getRetryAfter()}s\n");
    exit(1);
} catch (TwitterAdsException $e) {
    fwrite(STDERR, $e->getCode() . ' ' . json_encode($e->getErrors()) . "\n");
    exit(1);
}
