<?php

namespace Hborras\TwitterAdsSDK\TwitterAds\Errors;

use Exception;
use Hborras\TwitterAdsSDK\TwitterAdsException;

/**
 * Class RateLimit
 * @package Hborras\TwitterAdsSDK\TwitterAds\Errors
 */
class RateLimit extends TwitterAdsException
{
    /**
     * @var mixed
     */
    private $retryAfter;
    /**
     * @var mixed
     */
    private $resetAt;
    private $limit;
    private $remaining;

    /**
     * RateLimit constructor.
     * @param $message
     * @param $code
     * @param Exception|null $previous
     * @param $errors
     * @param $headers
     */
    public function __construct($message, $code, ?Exception $previous = null, $errors = null, $headers = [])
    {
        parent::__construct($message, $code, $previous, $errors);
        $headers = is_array($headers) ? $headers : [];
        $this->resetAt = isset($headers['x_rate_limit_reset']) ? $headers['x_rate_limit_reset'] : '';
        $this->limit = isset($headers['x_rate_limit_limit']) ? $headers['x_rate_limit_limit'] : '';
        $this->remaining = isset($headers['x_rate_limit_remaining']) ? $headers['x_rate_limit_remaining'] : '';
        if (isset($headers['retry_after'])) {
            $this->retryAfter = $headers['retry_after'];
        } elseif ($this->resetAt !== '') {
            $this->retryAfter = (string)max(0, (int)$this->resetAt - time());
        } else {
            $this->retryAfter = '';
        }
    }

    /**
     * @return mixed
     */
    public function getRetryAfter()
    {
        return $this->retryAfter;
    }

    /**
     * @return mixed
     */
    public function getResetAt()
    {
        return $this->resetAt;
    }

    /**
     * @return string Request quota of the current window (x-rate-limit-limit)
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return string Requests left in the current window (x-rate-limit-remaining)
     */
    public function getRemaining()
    {
        return $this->remaining;
    }
}
