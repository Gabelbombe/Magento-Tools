<?php

libxml_use_internal_errors(1);
ini_set("memory_limit", "-1");
set_time_limit(0);

//some memory cleanup for long-running scripts.
gc_enable();            // Enable Garbage Collector
var_dump(gc_enabled()); // true

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

define ('ZR_ADMIN_URL',   '');
define ('FS_USER',        '');
define ('FS_PASS',        '');

$cookieJar    = tempnam('/tmp','cookie');
$getAttribute = function ($obj, $attr)
{
    return (isset($obj[$attr]))
        ? (string) $obj[$attr]
        : false;
};

$getCodes = function ($html) USE ($getAttribute) //parent another closure
{
    $dom = New \DOMDocument();
    $dom->loadHTML($html);

    if (!$dom) Throw New \HttpException('Error while parsing the document');

    $sxe    = simplexml_import_dom($dom);
    $colors = [];

    $xpre = $sxe->xpath('//select[contains(@name, "MAIN_SKU.COLOR-")]/option');

        if (empty($xpre)) Throw New \Exception ('Missing data...');

    foreach($xpre AS $node)
    {
        if (2 <= count($node->attributes()))
        {
            $key = $getAttribute($node, 'value');
            $colors["{$key}"] = trim(preg_replace('/\(.*/', '', (string) $node));
        };
    }

    return (! empty($colors))
        ? $colors
        : false;
};

//
$testThrow = function ($caseError)
{
    $url = ZR_ADMIN_URL;
    if (preg_match('/cannot/i', exec ("ping=$(ping {$url} 2>&1); echo \$ping")))
        Throw New \Exception ("Unable to connect to {$url}...");
        Throw New \Exception ($caseError); //reachable
};



echo "Get new session ID via login form request\n";

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

echo "Login via POST request\n";

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

echo "Dumpfile exists? ";

if (! file_exists('/tmp/html.json'))
{
    echo "No...\n";
    echo "Obtaining links list\n";

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

    echo "Creating DOM trees\n";

    $response = curl_exec($ch);


        if (! $response) $testThrow('Response empty...');

    $dom = New \DOMDocument();
    $dom->loadHTML($response);

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
        }
    }

    echo "Endpoints empty? ";

    if (! empty($endpoints))
    {
        echo "No...\n";

        $data  = New \SplFixedArray(1024 * 1024); // array();
        $total = count($endpoints);

        $inc = 0;
        foreach ($endpoints AS $id => $endpoint)
        {
            $pad = str_pad($inc, strlen($total), 0, STR_PAD_LEFT);

            echo "Trying ({$pad}/{$total}): {$endpoint}\n";

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL             => $endpoint,
                CURLOPT_COOKIEFILE      => $cookieJar,
                CURLOPT_SSL_VERIFYPEER  => 0,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_HEADER          => 0,
                CURLOPT_FOLLOWLOCATION  => 1,
            ]);

            $result = curl_exec($ch);

            $data[$id] = $result;

            echo "Peak fake: ".(memory_get_peak_usage(false)/1024/1024)." MiB\n";
            echo "Peak real: ".(memory_get_peak_usage(true)/1024/1024) ." MiB\n\n";

            unset($result);

            curl_close($ch);
            $inc++;
        }

        $file = '/tmp/html.json';
        if (touch($file) && is_writable($file))
        {
            echo "Attempting to write file\n";

            if (! $handle = fopen($file, 'a'))
                Throw New \Exception("Could not open file {$file}");

            if (false === fwrite($handle, json_encode($data)))
                Throw New \Exception("Could not write to file {$file}");

            fclose($handle);
        }

        echo "Dumpfile saved!\n";
    }
} else {
    echo "Loader exists...\n";
}

if (file_exists('/tmp/html.json'))
{
    $object = json_decode(file_get_contents('/tmp/html.json'));

    echo "Attempting to decode...\n";
    echo "JSON said: " . $err = json_last_error_msg() . "\n";

    if (0 !== json_last_error()) die;

    $output = [];
    foreach ($object AS $result)
    {
        $dom = New \DOMDocument();
        $dom->loadHTML($result);

        if (!$dom) Throw New \HttpException('Error while parsing the document');

        $sxe  = simplexml_import_dom($dom);
        $item = $sxe->xpath('//td[contains(@class, "thirdTitle")]');
        $page = $sxe->xpath('//div[contains(@class, "prsetpagelinks")]//a');


        echo "Processing: " . (string) $item[0]->b . "\n";

        $endpoints = []; // clear
        if (! empty($page)) // we have several pagination going here....
        {
            foreach($page AS $attr)
            {
                $endpoints[] = ZR_ADMIN_URL . "?" . explode('?', $getAttribute($attr, 'href')) [1];
            }
        }

        $colors = [];
        $colors = $getCodes($result);

        echo "\n";

        if (! empty($endpoints))
        {
            echo "Found " . $count = count($endpoints) . " additional pages in this set...\n"; //casts to right for error

            require_once '../MageTools/MultiCurl.php';

            $r = New \MageTools\MultiCurl();

                $r->doRequests($endpoints, [   //swarm
                    CURLOPT_COOKIEFILE      => $cookieJar,
                    CURLOPT_SSL_VERIFYPEER  => 0,
                    CURLOPT_FOLLOWLOCATION  => 1,
                ]);

            if (false === ($dataSlice = $r->getResults()))

                Throw New \Exception ("Response returned empty, expected {$count}");

                foreach ($dataSlice AS $slice) $colors = ($getCodes($slice) + $colors);

            unset($dataSlice); //free


            print_r($colors); die;
        }

        $output[] = [
            'ProductName'      => (string) $item[0]->b,
            'ProductReference' => (string) $item[1]->b,
            'ProductSwatches'  => $colors,
        ];

        print_r($output);

        echo "\n";
    }

    print_r(json_encode($output, JSON_PRETTY_PRINT));
    gc_disable(); // Disable Garbage Collector
}

else
{
    Throw New \LogicException('Something broke in file writing sequence...');
}