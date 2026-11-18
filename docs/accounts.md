# Accounts — `AccountApi`

```php
use Hborras\TwitterAdsSDK\TwitterAds\Api\AccountApi;

$account = (new AccountApi($client, $accountId))->get();

$account['name'];
$account['timezone'];        // e.g. "Asia/Tokyo"
$account['approval_status']; // e.g. "ACCEPTED"
```

`get(array $params = [])` calls `GET accounts/{account_id}` and returns the account (`data`).

To list every account the token can access, use the resource API: `$client->getAccounts()`.
Note that it relies on the global `TwitterAds::init()` instance.
