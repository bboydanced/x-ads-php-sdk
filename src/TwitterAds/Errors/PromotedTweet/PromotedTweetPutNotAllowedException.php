<?php


namespace Hborras\TwitterAdsSDK\TwitterAds\Errors\PromotedTweet;

use Exception;

class PromotedTweetPutNotAllowedException extends Exception
{
    public function __construct()
    {
        parent::__construct('PromotedTweetPutNotAllowedException', 500);
    }
}
