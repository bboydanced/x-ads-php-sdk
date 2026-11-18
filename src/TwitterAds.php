<?php

namespace Hborras\TwitterAdsSDK;

use Exception;
use Hborras\TwitterAdsSDK\TwitterAds\Cursor;
use Hborras\TwitterAdsSDK\TwitterAds\Account;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\NotFound;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\Forbidden;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\RateLimit;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\BadRequest;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\ServerError;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\NotAuthorized;
use Hborras\TwitterAdsSDK\TwitterAds\Errors\ServiceUnavailable;

/**
 * TwitterAds class for interacting with the Twitter API.
 *
 * @author Hector Borras <hborrasaleixandre@gmail.com>
 */
class TwitterAds extends Config
{
    const API_VERSION      = '12';
    const API_REST_VERSION = '1.1';
    const API_HOST = 'https://ads-api.x.com';
    const API_HOST_SANDBOX = 'https://ads-api-sandbox.twitter.com';
    const API_HOST_OAUTH = 'https://api.x.com';
    /** @deprecated v1.1 media upload is legacy on X; upload via the X API v2 media endpoints instead. */
    const UPLOAD_HOST = 'https://upload.twitter.com';
    const UPLOAD_PATH = 'media/upload.json';
    const UPLOAD_CHUNK = 40960; // 1024 * 40
    const UPLOAD_MAX_STATUS_CHECKS = 60;

    /** @var TwitterAds */
    protected static $instance;
    /** @var  string Method used for the request */
    private $method;
    /** @var  string Resource used for the request */
    private $resource;
    /** @var Response details about the result of the last request */
    private $response;
    /** @var string|null Application bearer token */
    private $bearer;
    /** @var Consumer Twitter application details */
    private $consumer;
    /** @var Token|null User access token details */
    private $token;
    /** @var HmacSha1 OAuth 1 signature type used by Twitter */
    private $signatureMethod;
    /** @var  bool Sandbox allows to make requests thought sandbox environment */
    private $sandbox;

    /** @var Account */
    private $account;

    /**
     * @return TwitterAds|null
     */
    public static function instance()
    {
        return static::$instance;
    }

    /**
     * @param TwitterAds $instance
     */
    public static function setInstance(TwitterAds $instance)
    {
        static::$instance = $instance;
    }

    /**
     * Constructor.
     *
     * @param string $consumerKey The Application Consumer Key
     * @param string $consumerSecret The Application Consumer Secret
     * @param string|null $oauthToken The Client Token
     * @param string|null $oauthTokenSecret The Client Token Secret
     * @param Account $account | null
     * @param bool $sandbox The Sandbox environment (optional)
     */
    public function __construct($consumerKey, $consumerSecret, $oauthToken = '', $oauthTokenSecret = '', $account = null, $sandbox = false)
    {
        $this->resetLastResponse();
        $this->signatureMethod = new HmacSha1();
        $this->consumer = new Consumer($consumerKey, $consumerSecret);
        if (!empty($oauthToken) && !empty($oauthTokenSecret)) {
            $this->token = new Token($oauthToken, $oauthTokenSecret);
        }
        if (empty($oauthToken) && !empty($oauthTokenSecret)) {
            $this->bearer = $oauthTokenSecret;
        }
        $this->sandbox = $sandbox;
        $this->account = $account;
    }

    /**
     * @param $consumerKey
     * @param $consumerSecret
     * @param $oauthToken
     * @param $oauthTokenSecret
     * @param Account $account | null
     * @param bool $sandbox
     * @return static
     */
    public static function init($consumerKey, $consumerSecret, $oauthToken = '', $oauthTokenSecret = '', $account = null, $sandbox = false)
    {
        $api = new static($consumerKey, $consumerSecret, $oauthToken, $oauthTokenSecret, $account, $sandbox);
        static::setInstance($api);

        return $api;
    }

    /**
     * @return Account|Cursor
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotAuthorized
     * @throws NotFound
     * @throws RateLimit
     * @throws ServerError
     * @throws ServiceUnavailable
     * @throws TwitterAdsException
     */
    public function getAccounts()
    {
        $accountClass = new Account();
        return $accountClass->all();
    }

    /**
     * @param string $oauthToken
     * @param string $oauthTokenSecret
     */
    public function setOauthToken($oauthToken, $oauthTokenSecret)
    {
        $this->token = new Token($oauthToken, $oauthTokenSecret);
    }

    /**
     * Authenticate with an OAuth 2.0 Bearer token (user access token or app-only token) instead of OAuth 1.0a.
     *
     * @param string|null $bearerToken null switches back to OAuth 1.0a signing
     */
    public function setBearerToken($bearerToken)
    {
        $this->bearer = ($bearerToken === null || $bearerToken === '') ? null : (string)$bearerToken;
    }

    /**
     * @return string|null
     */
    public function getBearerToken()
    {
        return $this->bearer;
    }

    /**
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $bearerToken
     * @param bool $sandbox
     * @return static
     */
    public static function initWithBearer($consumerKey, $consumerSecret, $bearerToken, $sandbox = false)
    {
        return static::init($consumerKey, $consumerSecret, '', $bearerToken, null, $sandbox);
    }

    /**
     * @return string|null
     */
    public function getLastApiPath()
    {
        return $this->response->getApiPath();
    }

    /**
     * @return int
     */
    public function getLastHttpCode()
    {
        return $this->response->getHttpCode();
    }

    /**
     * @return array
     */
    public function getLastXHeaders()
    {
        return $this->response->getXHeaders();
    }

    /**
     * @return array|object|null
     */
    public function getLastBody()
    {
        return $this->response->getBody();
    }

    /**
     * Resets the last response cache.
     */
    public function resetLastResponse()
    {
        $this->response = new Response();
    }

    /**
     * Make URLs for user browser navigation.
     *
     * @param string $path
     * @param array $parameters
     *
     * @return string
     */
    public function url($path, array $parameters)
    {
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $query = http_build_query($parameters);

        return sprintf('%s/%s?%s', static::API_HOST_OAUTH, $path, $query);
    }

    /**
     * Make /oauth/* requests to the API.
     *
     * @param string $path
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    public function oauth($path, array $parameters = [])
    {
        $response = [];
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $url = sprintf('%s/%s', static::API_HOST_OAUTH, $path);
        $result = $this->oAuthRequest($url, 'POST', $parameters);

        if ($this->getLastHttpCode() != 200) {
            throw new TwitterAdsException($result, 500, null, $result);
        }

        parse_str($result, $response);
        $this->response->setBody($response);

        return $response;
    }

    /**
     * Make /oauth2/* requests to the API.
     *
     * @param string $path
     * @param array $parameters
     *
     * @return array|object
     * @throws TwitterAdsException
     */
    public function oauth2($path, array $parameters = [])
    {
        $method = 'POST';
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $url = sprintf('%s/%s', static::API_HOST_OAUTH, $path);
        $request = Request::fromConsumerAndToken($this->consumer, $this->token, $method, $url, $parameters);
        $authorization = 'Authorization: Basic ' . $this->encodeAppAuthorization($this->consumer);
        $result = $this->request($request->getNormalizedHttpUrl(), $method, $authorization, $parameters);
        $response = json_decode($result, $this->decodeJsonAsArray);
        $this->response->setBody($response);

        return $response;
    }

    public function verifyCredentials($parameters = [])
    {
        return $this->http('GET', static::API_HOST_OAUTH, 'account/verify_credentials', $parameters);
    }

    /**
     * Make GET requests to the API.
     *
     * @param string $path
     * @param array $parameters
     *
     * @return Response
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotAuthorized
     * @throws NotFound
     * @throws RateLimit
     * @throws ServerError
     * @throws ServiceUnavailable
     * @throws TwitterAdsException
     */
    public function get($path, array $parameters = [])
    {
        return $this->http('GET', !$this->sandbox ? static::API_HOST : static::API_HOST_SANDBOX, $path, $parameters);
    }

    /**
     * Make POST requests to the API.
     *
     * @param string $path
     * @param array $parameters
     *
     * @param array $headers
     * @return Response
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotAuthorized
     * @throws NotFound
     * @throws RateLimit
     * @throws ServerError
     * @throws ServiceUnavailable
     * @throws TwitterAdsException
     */
    public function post($path, array $parameters = [], array $headers = [])
    {
        return $this->http('POST', !$this->sandbox ? static::API_HOST : static::API_HOST_SANDBOX, $path, $parameters, $headers);
    }

    /**
     * Make DELETE requests to the API.
     *
     * @param string $path
     * @param array $parameters
     *
     * @return Response
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotAuthorized
     * @throws NotFound
     * @throws RateLimit
     * @throws ServerError
     * @throws ServiceUnavailable
     * @throws TwitterAdsException
     */
    public function delete($path, array $parameters = [])
    {
        return $this->http('DELETE', !$this->sandbox ? static::API_HOST : static::API_HOST_SANDBOX, $path, $parameters);
    }

    /**
     * Make PUT requests to the API.
     *
     * @param string $path
     * @param array $parameters
     *
     * @param array $headers
     * @return Response
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotAuthorized
     * @throws NotFound
     * @throws RateLimit
     * @throws ServerError
     * @throws ServiceUnavailable
     * @throws TwitterAdsException
     */
    public function put($path, array $parameters = [], array $headers = [])
    {
        return $this->http('PUT', !$this->sandbox ? static::API_HOST : static::API_HOST_SANDBOX, $path, $parameters, $headers);
    }

    /**
     * Upload media to upload.twitter.com.
     *
     * @param array $parameters
     * @param bool $chunked
     *
     * @return array|object
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotAuthorized
     * @throws NotFound
     * @throws RateLimit
     * @throws ServerError
     * @throws ServiceUnavailable
     * @throws TwitterAdsException
     */
    public function upload(array $parameters = [], $chunked = false)
    {
        if ($chunked) {
            return $this->uploadMediaChunked(static::UPLOAD_PATH, $parameters);
        }

        return $this->uploadMediaNotChunked(static::UPLOAD_PATH, $parameters)->getBody();
    }

    /**
     * Private method to upload media (not chunked) to upload.twitter.com.
     *
     * @param string $path
     * @param array $parameters
     *
     * @return array|object
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotAuthorized
     * @throws NotFound
     * @throws RateLimit
     * @throws ServerError
     * @throws ServiceUnavailable
     * @throws TwitterAdsException
     */
    private function uploadMediaNotChunked($path, $parameters)
    {
        $file = file_get_contents($parameters['media']);
        $base = base64_encode($file);
        $parameters['media'] = $base;

        return $this->http('POST', static::UPLOAD_HOST, $path, $parameters);
    }

    /**
     * Private method to upload media (chunked) to upload.twitter.com.
     *
     * @param string $path
     * @param array $parameters
     *
     * @return array|object
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotAuthorized
     * @throws NotFound
     * @throws RateLimit
     * @throws ServerError
     * @throws ServiceUnavailable
     * @throws TwitterAdsException
     */
    private function uploadMediaChunked($path, $parameters)
    {
        if ($parameters['media_type'] == 'video/mp4') {
            $parameters['media_category'] = 'amplify_video';
        } elseif ($parameters['media_type'] == 'image/gif') {
            $parameters['media_category'] = 'tweet_gif';
        } else {
            $parameters['media_category'] = 'tweet_image';
        }

        // Init
        $initParameters = [
            'command' => 'INIT',
            'media_type' => $parameters['media_type'],
            'media_category' => $parameters['media_category'],
            'total_bytes' => filesize($parameters['media'])
        ];

        if (isset($parameters['additional_owners']) && is_array($parameters['additional_owners'])) {
            $initParameters['additional_owners'] = implode(',', $parameters['additional_owners']);
        }

        $init = $this->http(
            'POST',
            static::UPLOAD_HOST,
            $path,
            $initParameters
        )->getBody();

        // Append
        $segment_index = 0;
        $media = fopen($parameters['media'], 'rb');
        while (!feof($media)) {
            $this->http(
                'POST',
                static::UPLOAD_HOST,
                $path,
                [
                    'command' => 'APPEND',
                    'media_id' => $init->media_id_string,
                    'segment_index' => $segment_index++,
                    'media_data' => base64_encode(fread($media, static::UPLOAD_CHUNK)),
                ]
            )->getBody();
        }
        fclose($media);
        // Finalize
        $finalize = $this->http(
            'POST',
            static::UPLOAD_HOST,
            $path,
            [
                'command' => 'FINALIZE',
                'media_id' => $init->media_id_string,
            ]
        )->getBody();

        $statusChecks = 0;
        while (isset($finalize->processing_info)) {
            $state = isset($finalize->processing_info->state) ? $finalize->processing_info->state : null;
            if ($state === 'succeeded') {
                break;
            }
            if ($state === 'failed') {
                throw new TwitterAdsException('Media processing failed', 500, null, $finalize->processing_info);
            }
            if (!isset($finalize->processing_info->check_after_secs) || ++$statusChecks > static::UPLOAD_MAX_STATUS_CHECKS) {
                throw new TwitterAdsException('Media processing did not finish', 500, null, $finalize->processing_info);
            }
            sleep((int)$finalize->processing_info->check_after_secs);
            $finalize = $this->http('GET', static::UPLOAD_HOST, $path, [
                'command' => 'STATUS',
                'media_id' => $finalize->media_id_string ?? $finalize->media_id,
            ])->getBody();
        }

        return $finalize;
    }

    /**
     * @param string $method
     * @param string $host
     * @param string $path
     * @param array $parameters
     * @param array $headers
     * @return Response
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotAuthorized
     * @throws NotFound
     * @throws RateLimit
     * @throws ServerError
     * @throws ServiceUnavailable
     * @throws TwitterAdsException
     */
    private function http($method, $host, $path, array $parameters, $headers = [])
    {
        $this->method = $method;
        $this->resource = $path;
        $this->resetLastResponse();
        if (strpos($path, TONUpload::DEFAULT_DOMAIN) === 0) {
            $url = $path;
        } elseif ($host == static::UPLOAD_HOST) {
            $url = sprintf('%s/%s/%s', $host, static::API_REST_VERSION, $path);
        } else {
            $url = sprintf('%s/%s/%s', $host, static::API_VERSION, $path);
        }

        $this->response->setApiPath($path);
        $result = $this->oAuthRequest($url, $method, $parameters, $headers);
        $response = json_decode($result, $this->decodeJsonAsArray);
        $this->response->setBody($response);
        if ($this->getLastHttpCode() > 399) {
            $this->manageErrors($response);
        }
        return $this->response;
    }

    /**
     * @param $response
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotAuthorized
     * @throws NotFound
     * @throws RateLimit
     * @throws ServerError
     * @throws ServiceUnavailable
     */
    public function manageErrors($response)
    {
        $errors = [];
        $body = is_array($response) ? (object)$response : $response;
        if (isset($body->errors)) {
            $errors = $body->errors;
        } elseif (isset($body->operation_errors)) {
            $errors = $body->operation_errors;
        }

        switch ($this->getLastHttpCode()) {
            case 400:
                throw new BadRequest(TwitterAdsException::BAD_REQUEST, 400, null, $errors);
            case 401:
                throw new NotAuthorized(TwitterAdsException::NOT_AUTHORIZED, 401, null, $errors);
            case 403:
                throw new Forbidden(TwitterAdsException::FORBIDDEN, 403, null, $errors);
            case 404:
                throw new NotFound(TwitterAdsException::NOT_FOUND, 404, null, $errors);
            case 429:
                throw new RateLimit(TwitterAdsException::RATE_LIMIT, 429, null, $errors, $this->response->getHeaders());
            case 500:
                throw new ServerError(TwitterAdsException::SERVER_ERROR, 500, null, $errors);
            case 503:
                throw new ServiceUnavailable(TwitterAdsException::SERVICE_UNAVAILABLE, 503, null, $errors, $this->response->getHeaders());
            default:
                throw new ServerError(TwitterAdsException::SERVER_ERROR, 500, null, $errors);
        }
    }

    /**
     * Format and sign an OAuth / API request.
     *
     * @param string $url
     * @param string $method
     * @param array $parameters
     *
     * @param array $headers
     * @return string
     * @throws TwitterAdsException
     */
    private function oAuthRequest($url, $method, array $parameters, $headers = [])
    {
        $request = Request::fromConsumerAndToken($this->consumer, $this->token, $method, $url, $parameters);
        if (array_key_exists('oauth_callback', $parameters)) {
            // Twitter doesn't like oauth_callback as a parameter.
            unset($parameters['oauth_callback']);
        }
        if ($this->bearer === null) {
            $request->signRequest($this->signatureMethod, $this->consumer, $this->token);
            $authorization = $request->toHeader();
        } else {
            $authorization = 'Authorization: Bearer ' . $this->bearer;
        }
        if (strpos($url, TONUpload::DEFAULT_DOMAIN) === 0) {
            return $this->request($url, $method, $authorization, $parameters, $headers);
        }

        return $this->request($request->getNormalizedHttpUrl(), $method, $authorization, $parameters, $headers);
    }

    /**
     * Make an HTTP request.
     *
     * @param string $url
     * @param string $method
     * @param string $authorization
     * @param array $postfields
     *
     * @param array $headers
     * @return string
     * @throws TwitterAdsException
     */
    private function request($url, $method, $authorization, $postfields, $headers = [])
    {
        /* Curl settings */
        $options = [
            CURLOPT_VERBOSE => false,
            CURLOPT_CONNECTTIMEOUT => $this->connectionTimeout,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array_merge(['Accept: */*', $authorization, 'Expect:'], $headers, ['Connection: close']),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_ENCODING => 'gzip;q=1.0,deflate;q=0.6,identity;q=0.3',
        ] + $this->caOptions();

        if (!empty($this->proxy)) {
            $options[CURLOPT_PROXY] = $this->proxy['CURLOPT_PROXY'];
            $options[CURLOPT_PROXYUSERPWD] = $this->proxy['CURLOPT_PROXYUSERPWD'];
            $options[CURLOPT_PROXYPORT] = $this->proxy['CURLOPT_PROXYPORT'];
            $options[CURLOPT_PROXYAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
        }

        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                if (isset($postfields['raw'])) {
                    $options[CURLOPT_POSTFIELDS] = $postfields['raw'];
                    $options[CURLOPT_HTTPHEADER] = array_merge($options[CURLOPT_HTTPHEADER], [
                        'Content-Type: application/json',
                        'Content-Length: '. strlen($postfields['raw'])
                    ]);
                } else {
                    $options[CURLOPT_POSTFIELDS] = Util::buildHttpQuery($postfields);
                }

                break;
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                if (isset($postfields['raw'])) {
                    $options[CURLOPT_POSTFIELDS] = $postfields['raw'];
                    $options[CURLOPT_HTTPHEADER] = array_merge($options[CURLOPT_HTTPHEADER], [
                        'Content-Type: application/json',
                        'Content-Length: '. strlen($postfields['raw'])
                    ]);
                }
                break;
        }

        if (in_array($method, ['GET', 'PUT', 'DELETE']) && !empty($postfields) && !isset($postfields['raw'])) {
            $options[CURLOPT_URL] .= '?' . Util::buildHttpQuery($postfields);
        }

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $options);
        $response = curl_exec($curlHandle);
        // Throw exceptions on cURL errors.
        if (curl_errno($curlHandle) > 0) {
            throw new TwitterAdsException(curl_error($curlHandle), curl_errno($curlHandle), null, null);
        }
        $this->response->setHttpCode(curl_getinfo($curlHandle, CURLINFO_HTTP_CODE));
        // Header block size covers every block (100 Continue, proxy CONNECT, redirects); keep only the last one.
        $headerSize = (int)curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
        $headerBlocks = explode("\r\n\r\n", trim(substr($response, 0, $headerSize)));
        $responseBody = (string)substr($response, $headerSize);
        $this->response->setHeaders($this->parseHeaders((string)end($headerBlocks)));
        if (PHP_VERSION_ID < 80000) {
            curl_close($curlHandle);
        }
        return $responseBody;
    }

    /**
     * cURL CA options. Order: setCaInfo() > php.ini curl.cainfo > system store > bundled cacert.pem.
     * The system store is set explicitly because static PHP/cURL builds may not know the OS default path.
     *
     * @return array
     */
    protected function caOptions()
    {
        if ($this->caInfo !== null) {
            return [is_dir($this->caInfo) ? CURLOPT_CAPATH : CURLOPT_CAINFO => $this->caInfo];
        }
        if (ini_get('curl.cainfo')) {
            return [];
        }

        $locations = function_exists('openssl_get_cert_locations') ? openssl_get_cert_locations() : [];
        $file = getenv('SSL_CERT_FILE') ?: (isset($locations['default_cert_file']) ? $locations['default_cert_file'] : '');
        if ($file !== '' && is_file($file) && is_readable($file)) {
            return [CURLOPT_CAINFO => $file];
        }
        $dir = getenv('SSL_CERT_DIR') ?: (isset($locations['default_cert_dir']) ? $locations['default_cert_dir'] : '');
        if ($dir !== '' && is_dir($dir) && is_readable($dir)) {
            return [CURLOPT_CAPATH => $dir];
        }

        return [CURLOPT_CAINFO => __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem'];
    }

    /**
     * Get the header info to store.
     *
     * @param string $header
     *
     * @return array
     */
    private function parseHeaders($header)
    {
        $headers = [];
        foreach (explode("\r\n", $header) as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = str_replace('-', '_', strtolower(trim($key)));
                $headers[$key] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * Set API URLS.
     */
    public function accessTokenURL()
    {
        return 'https://api.twitter.com/oauth/access_token';
    }

    public function authenticateURL()
    {
        return 'https://api.twitter.com/oauth/authenticate';
    }

    public function authorizeURL()
    {
        return 'https://api.twitter.com/oauth/authorize';
    }

    public function requestTokenURL()
    {
        return 'https://api.twitter.com/oauth/request_token';
    }

    /**
     * Encode application authorization header with base64.
     *
     * @param Consumer $consumer
     *
     * @return string
     */
    private function encodeAppAuthorization($consumer)
    {
        // TODO: key and secret should be rfc 1738 encoded
        $key = $consumer->key;
        $secret = $consumer->secret;

        return base64_encode($key . ':' . $secret);
    }

    /**
     * Return current response. Allows inheritance.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function getAccountId()
    {
        if(!$this->account instanceof Account){
            return '';
        }
        return $this->account->getId();
    }

    /**
     * @return string
     */
    public function getAccountTimezone()
    {
        if(!$this->account instanceof Account){
            return 'UTC';
        }
        return $this->account->getTimezone();
    }

    /**
     * @param Account $account
     */
    public function setAccount($account)
    {
        $this->account = $account;
    }

    public function getRequestToken($oauth_callback)
    {
        $parameters = array();
        $parameters['oauth_callback'] = $oauth_callback;
        $request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
        return self::parse_parameters($request);
    }

    /**
     * Exchange request token and secret for an access token and
     * secret, to sign API calls.
     *
     * @param $oauth_verifier
     * @return array ("oauth_token" => "the-access-token",
     *                "oauth_token_secret" => "the-access-secret",
     * "user_id" => "9436992",
     * "screen_name" => "abraham")
     * @throws TwitterAdsException
     */
    public function getAccessToken($oauth_verifier)
    {
        $parameters = array();
        $parameters['oauth_verifier'] = $oauth_verifier;
        $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
        return self::parse_parameters($request);
    }

    // This function takes a input like a=b&a=c&d=e and returns the parsed
    // parameters like this
    // array('a' => array('b','c'), 'd' => 'e')
    public static function parse_parameters($input)
    {
        if (!isset($input) || !$input) {
            return array();
        }

        $pairs = explode('&', $input);

        $parsed_parameters = array();
        foreach ($pairs as $pair) {
            $split = explode('=', $pair, 2);
            $parameter = self::urldecode_rfc3986($split[0]);
            $value = isset($split[1]) ? self::urldecode_rfc3986($split[1]) : '';

            if (isset($parsed_parameters[$parameter])) {
                // We have already recieved parameter(s) with this name, so add to the list
                // of parameters with this name

                if (is_scalar($parsed_parameters[$parameter])) {
                    // This is the first duplicate, so transform scalar (string) into an array
                    // so we can add the duplicates
                    $parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
                }

                $parsed_parameters[$parameter][] = $value;
            } else {
                $parsed_parameters[$parameter] = $value;
            }
        }

        return $parsed_parameters;
    }

    public static function urlencode_rfc3986($input)
    {
        if (is_array($input)) {
            return array_map(array(__CLASS__, 'urlencode_rfc3986'), $input);
        }

        if (is_scalar($input)) {
            return str_replace(
                '',
                ' ',
                str_replace('%7E', '~', rawurlencode($input))
            );
        }

        return '';
    }

    // This decode function isn't taking into consideration the above
    // modifications to the encoding process. However, this method doesn't
    // seem to be used anywhere so leaving it as is.
    public static function urldecode_rfc3986($string)
    {
        return urldecode($string);
    }

    /**
     * Get the authorize URL.
     *
     * @param $token
     * @param bool $sign_in_with_twitter
     * @return string
     */
    public function getAuthorizeURL($token, $sign_in_with_twitter = true)
    {
        if (is_array($token)) {
            $token = $token['oauth_token'];
        }
        if (empty($sign_in_with_twitter)) {
            return $this->authorizeURL() . "?oauth_token={$token}";
        }

        return $this->authenticateURL() . "?oauth_token={$token}";
    }
}
