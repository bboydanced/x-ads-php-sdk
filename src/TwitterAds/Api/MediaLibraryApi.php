<?php

namespace Hborras\TwitterAdsSDK\TwitterAds\Api;

/**
 * accounts/{account_id}/media_library; items are addressed by media_key.
 */
class MediaLibraryApi extends AbstractCrudApi
{
    protected function collection(): string
    {
        return 'media_library';
    }
}
