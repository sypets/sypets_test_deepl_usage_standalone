<?php
/**
 * Script for reproducing problem with Translator::getUsage() POST
 * request with deeplcom/deepl PHP Composer package.
 *
 * The script fails on some servers.
 *
 * The script does not fail if either
 * 1. Method is changed to GET (second command line argument)
 * 2. or the commented out lines for setting Content-Type and CURLOPT_POSTFIELDS are uncommented
 *
 * 2023-03-02 Sybille Peters
 */


if ($argc < 2) {
    printf("Missing argument: <authkey> [method, default=POST]\n");
    exit(1);
}

$authkey = $argv[1];
if ($argc > 2) {
    $method = $argv[2];
} else {
    $method = 'POST';
}
$url = 'https://api.deepl.com/v2/usage';
$timeout = 10;
$headers = [
    'Authorization' => 'DeepL-Auth-Key ' . $authkey,
    'User-Agent' => 'deepl-php/1.3.0',
    // NEW
    //'Content-Type' => 'application/json'
];


$curlOptions = [];
$curlOptions[\CURLOPT_HEADER] = false;

switch ($method) {
    case "POST":
        $curlOptions[\CURLOPT_POST] = true;
        break;
    case "GET":
        $curlOptions[\CURLOPT_HTTPGET] = true;
        break;
    default:
        $curlOptions[\CURLOPT_CUSTOMREQUEST] = $method;
        break;
}

// NEW
//$payload = json_encode( [] );
//$curlOptions[\CURLOPT_POSTFIELDS] = $payload;

$curlOptions[\CURLOPT_URL] = $url;
$curlOptions[\CURLOPT_CONNECTTIMEOUT] = $timeout;
$curlOptions[\CURLOPT_TIMEOUT_MS] = $timeout * 1000;

// Convert headers from an associative array to an array of "key: value" elements
$curlOptions[\CURLOPT_HTTPHEADER] = \array_map(function (string $key, string $value): string {
    return "$key: $value";
}, array_keys($headers), array_values($headers));

// Return response content as function result
$curlOptions[\CURLOPT_RETURNTRANSFER] = true;

$curlHandle = \curl_init();
//\curl_reset($curlHandle);
\curl_setopt_array($curlHandle, $curlOptions);

$result = \curl_exec($curlHandle);
if ($result !== false) {
    printf("\nCurl Info:\n");
    printf("------------------------\n");
    $curlInfo = $statusCode = \curl_getinfo($curlHandle);
    var_dump($curlInfo);

    printf("\nHTTP request header:\n");
    printf("------------------------\n");
    var_dump($headers);

    printf("\nCURL version:\n");
    printf("------------------------\n");
    var_dump(curl_version());

    printf("\nStatus code:\n");
    printf("------------------------\n");
    $statusCode = \curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
    printf($statusCode . "\n");
} else {
    $errorMessage = \curl_error($curlHandle);
    $errorCode = \curl_errno($curlHandle);
    switch ($errorCode) {
        case \CURLE_UNSUPPORTED_PROTOCOL:
        case \CURLE_URL_MALFORMAT:
        case \CURLE_URL_MALFORMAT_USER:
            $shouldRetry = false;
            $errorMessage = "Invalid server URL. $errorMessage";
            break;
        case \CURLE_OPERATION_TIMEOUTED:
        case \CURLE_COULDNT_CONNECT:
        case \CURLE_GOT_NOTHING:
            $shouldRetry = true;
            break;
        default:
            $shouldRetry = false;
            break;
    }
    throw new \Exception('Connection with status code=' . $errorCode);
}

