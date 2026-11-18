<?php

namespace Hborras\TwitterAdsSDK\TwitterAds\Errors;

use Exception;
use Hborras\TwitterAdsSDK\TwitterAdsException;

/**
 * Class ServiceUnavailable
 * @package Hborras\TwitterAdsSDK\TwitterAds\Errors
 */
class ServiceUnavailable extends TwitterAdsException
{
    /**
     * @var mixed
     */
    private $retryAfter;

    /**
     * ServiceUnavailable constructor.
     * @param $message
     * @param $code
     * @param Exception|null $previous
     * @param $errors
     * @param $headers
     */
    public function __construct($message, $code, ?Exception $previous = null, $errors = null, $headers = [])
    {
        parent::__construct($message, $code, $previous, $errors);
        $this->retryAfter = is_array($headers) && isset($headers['retry_after']) ? $headers['retry_after'] : '';
    }

    /**
     * @return mixed
     */
    public function getRetryAfter()
    {
        return $this->retryAfter;
    }
}
