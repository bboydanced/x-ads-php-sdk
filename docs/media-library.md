# Media Library — `MediaLibraryApi`

Endpoint: `accounts/{account_id}/media_library`. Items are addressed by **`media_key`**.

```php
use Hborras\TwitterAdsSDK\TwitterAds\Api\MediaLibraryApi;

$library = new MediaLibraryApi($client, $accountId);
```

## 1. Upload the file (outside this SDK)

Adding a file to the Media Library takes two steps:

1. Upload the binary with the **X API v2 media upload** endpoint (`POST https://api.x.com/2/media/upload`,
   chunked for video/GIF). This returns a `media_key`. For video use `media_category=amplify_video`, and pass
   `additional_owners` if another user must own the media.
2. Register the `media_key` in the account's Media Library (below).

`TwitterAds::upload()` targets the legacy v1.1 `upload.twitter.com` endpoint and is **deprecated**.
Use your own v2 upload client for step 1.

## 2. Add to the library

```php
$media = $library->create([
    'media_key'   => $mediaKey,       // from the upload step
    'name'        => 'Spring hero',
    'title'       => 'Spring hero',   // video only
    'description' => 'Hero video',    // video only
    'file_name'   => 'spring.mp4',
]);

$media['media_url'];
$media['media_status']; // video may be "PROCESSING" first: poll with find()
```

## Read, update, delete

```php
$one   = $library->find($mediaKey);
$all   = $library->allPages(['count' => 50, 'media_type' => 'VIDEO', 'q' => 'spring']);
$media = $library->update($mediaKey, ['name' => 'Renamed', 'description' => 'New']);
$gone  = $library->delete($mediaKey);

$gone['deleted'] === true;
```
