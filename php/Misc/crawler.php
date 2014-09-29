<?php

libxml_use_internal_errors(1);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

define ('ZR_ADMIN_URL',   'https://filsonadmin.zaneray.com:447/za/ZCADM');
define ('FS_USER',        '');
define ('FS_PASS',        '');

$cookieJar    = tempnam('/tmp','cookie');
$getAttribute = function ($obj, $attr)
{
    return (isset($obj[$attr]))
        ? (string) $obj[$attr]
        : false;
};

// Get new session ID via login form request
$ch = curl_init();
$payload    = http_build_query([
    'PAGE'      => 'LOGON_USER',
]);

curl_setopt_array($ch, [
    CURLOPT_URL             => ZR_ADMIN_URL . "?{$payload}",
    CURLOPT_COOKIEJAR       => $cookieJar,
    CURLOPT_SSL_VERIFYPEER  => 0,
    CURLOPT_RETURNTRANSFER  => 1,
    CURLOPT_HEADER          => 1,
]);
curl_exec($ch);
curl_close($ch);


// Login via POST request
$ch = curl_init();
$payload = http_build_query([
    'WDS_USER.USERID'       => FS_USER,
    'WDS_USER.PASSWORD'     => FS_PASS,
    'OPTION'                => 'LOGONUSER',
    'PAGE'                  => 'LOGON_PAGE',
    'HOMEPAGE'              => ZR_ADMIN_URL,
    'WDS_USER.COMPANY_ID'   => 1,
]);

curl_setopt_array($ch, [
    CURLOPT_URL             => ZR_ADMIN_URL,
    CURLOPT_POSTFIELDS      => $payload,
    CURLOPT_COOKIEJAR       => $cookieJar,
    CURLOPT_POST            => 1,
    CURLOPT_SSL_VERIFYPEER  => 0,
    CURLOPT_RETURNTRANSFER  => 1,
    CURLOPT_HEADER          => 0,
    CURLOPT_FOLLOWLOCATION  => 1,
]);

curl_exec($ch);
curl_close($ch);

if (! file_exists('/tmp/html.json'))
{
    // get links list
    $ch = curl_init();
    $payload = http_build_query([
        'ACTIVE'                => 0,
        'DOINGSEARCH'           => 'TRUE',
        'DOSORT'                => 'TRUE',
        'LEVEL'                 => 'PRODUCT',
        'NAME'                  => '',
        'PAGE'                  => 'ZP_PRODUCT_SEARCH',
        'PRODUCT_SET.ID'        => '*ALL',
        'PRODUCT_TYPE.ID'       => '*ALL',
        'RESULTS_PER_PAGE'      => 5000,
        'REFERENCEID'           => '',
        'SEARCH_FORM_REDRAW'    => 'FALSE'
    ]);

    curl_setopt_array($ch, [
        CURLOPT_URL             => ZR_ADMIN_URL,
        CURLOPT_POSTFIELDS      => $payload,
        CURLOPT_COOKIEFILE      => $cookieJar,
        CURLOPT_POST            => 1,
        CURLOPT_SSL_VERIFYPEER  => 0,
        CURLOPT_RETURNTRANSFER  => 1,
        CURLOPT_HEADER          => 0,
        CURLOPT_FOLLOWLOCATION  => 1,
    ]);

    $dom = New \DOMDocument();
    $dom->loadHTML(curl_exec($ch));

        if (! $dom) Throw New \HttpException('Error while parsing the document');

    $endpoints = [];

    $sxe = simplexml_import_dom($dom);
    foreach($sxe->xpath('//td[contains(@class, "iconlinks")]//a') AS $id => $attr)
    {
        // test and set if this is a sku link otherwise skip...
        if (! preg_match('/sku/i', $uri = $getAttribute($attr, 'href'))) continue;

        if($payload = parse_url($uri) ['query']) // skip bs
        {

            $endpoints[] = ZR_ADMIN_URL . "?{$payload}";

            /**
             * Was not a POST
             *
             * parse_str($payload, $components);
             *
             * $endpoints[$id] = [
             *  'url'   => ZR_ADMIN_URL,
             *  'post'  => $components,
             * ];
             *
             */
        }
    }

    if (! empty($endpoints))
    {
        require_once '../Magetools/MultiCurl.php';

        $r = New MageTools\MultiCurl(); // multihandle to cut down on time

        $r->doRequests($endpoints, [
            CURLOPT_COOKIEFILE      => $cookieJar,
            CURLOPT_SSL_VERIFYPEER  => 0,
            CURLOPT_FOLLOWLOCATION  => 1,
        ]);
    }

    file_put_contents('/tmp/html.json', json_encode($r->getResults(), JSON_PRETTY_PRINT));
}

$output = [];
foreach($r->getResults() AS $result)
{
    $dom = New \DOMDocument();
    $dom->loadHTML(curl_exec($ch));

        if (! $dom) Throw New \HttpException('Error while parsing the document');

    $sxe = simplexml_import_dom($dom);

    print_r($sxe->xpath('//td[contains(@class, "thirdTitle")]'));

    $output[] = [
        'name'  => ''
    ];
    die;
}


