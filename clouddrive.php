<?php

$memcache = new Memcache;
$clientId = $memcache->get("clouddrive::client-id");
$clientSecret = $memcache->get("clouddrive::client-secret");

if (!$clientId || !$clientSecret) {
    $credentials = json_decode(file_get_contents('credentials.json'), true);
    $clientId = trim($credentials['client-id']);
    $clientSecret = trim($credentials['client-secret']);

    $memcache->set('clouddrive::client-id', $clientId);
    $memcache->set('clouddrive::client-secret', $clientSecret);
}

$redirectUrl = "https://{$_SERVER[HTTP_HOST]}{$_SERVER[REQUEST_URI]}";

$url = parse_url($redirectUrl);

if (!isset($url['query'])) {
    header("Location: https://www.amazon.com/ap/oa?client_id=$clientId&scope=clouddrive%3Aread+clouddrive%3Awrite&response_type=code&redirect_uri=https%3A%2F%2Fdata-mind-687.appspot.com%2Fclouddrive");
    exit;
}

parse_str($url['query'], $params);

if (isset($params['refresh_token'])) {
    $post_fields = array(
        'grant_type'    => 'refresh_token',
        'refresh_token' => $params['refresh_token'],
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => 'https://data-mind-687.appspot.com/clouddrive',
    );

    $data = http_build_query($post_fields);
    $context = [
        'http' => [
            'method' => 'POST',
            // 'header' => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36\r\n",
            'content' => $data
        ]
    ];
    $context = stream_context_create($context);
    $result = file_get_contents('https://api.amazon.com/auth/o2/token', false, $context);

    echo $result;
} else {
    if (!isset($params['code'])) {
        echo "No code set in redirect.";
        exit;
    }

    $post_fields = array(
        'grant_type'    => 'authorization_code',
        'code'          => $params['code'],
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => 'https://data-mind-687.appspot.com/clouddrive',
    );

    $data = http_build_query($post_fields);
    $context = [
        'http' => [
            'method' => 'POST',
            // 'header' => "custom-header: custom-value\r\n" .
            //             "custom-header-two: custom-value-2\r\n",
            'content' => $data
        ]
    ];
    $context = stream_context_create($context);
    $result = file_get_contents('https://api.amazon.com/auth/o2/token', false, $context);

    echo $result;
}