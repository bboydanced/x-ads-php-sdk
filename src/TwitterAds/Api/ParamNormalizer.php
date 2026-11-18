<?php

namespace Hborras\TwitterAdsSDK\TwitterAds\Api;

use DateTimeInterface;

/**
 * Converts PHP values to the string form the Ads API expects in query/form parameters.
 */
final class ParamNormalizer
{
    /**
     * null and '' are dropped; DateTime => ISO 8601; list => comma separated; bool => 'true'|'false'.
     */
    public static function normalize(array $data): array
    {
        $params = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            if ($value instanceof DateTimeInterface) {
                $value = $value->format('c');
            } elseif (is_array($value)) {
                $value = implode(',', array_map('strval', $value));
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } else {
                $value = (string)$value;
            }
            if ($value !== '') {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
