<?php

    define ('APP_DIR', realpath(dirname(__DIR__)));

    $pass  = false;
    $limit = 5000;

    libxml_use_internal_errors(1);
    ini_set("memory_limit", "-1");
    set_time_limit(0);
    ignore_user_abort(0); // for sig-trapping


    //some memory cleanup for long-running scripts.
    gc_enable();            //Enable Garbage Collector
    var_dump(gc_enabled()); //true

    ini_set('display_startup_errors', 1);
    ini_set('display_errors', 1);
    error_reporting(-1);

    define ('ZR_ADMIN_URL',   'https://filsonadmin.zaneray.com:447/za/ZCADM');
    define ('FS_USER',        'jon.daniel@filson.com');
    define ('FS_PASS',        '');


    $productError = [];
    $cookieJar    = tempnam('/tmp','cookie');
    $inputFile    = APP_DIR . '/input/html.json';


    $getAttribute = function ($obj, $attr)
    {
        return (isset($obj[$attr]))
            ? (string) $obj[$attr]
            : false;
    };

    $getCodes = function ($html, $id, $data = []) USE ($getAttribute) //parent another closure
    {
        $dom = New \DOMDocument();
        $dom->loadHTML($html);

        if (! $dom) Throw New \HttpException('Error while parsing the document');

        $sxe    = simplexml_import_dom($dom);
        $colors = [];

        $xpre = $sxe->xpath('//select[contains(@name, "MAIN_SKU.COLOR-")]/option');

        if (empty($xpre))
        {
            echo (empty($data->ends))
                ? "--> '{$data->name} ({$data->sku})' returned an empty document...\n\n"
                : "--> {$data->ends[$id]}\n";
        }

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
            : [];
    };

    $getSizes = function ($html) //parent another closure
    {
        $dom = New \DOMDocument();
        $dom->loadHTML($html);

        if (! $dom) Throw New \HttpException('Error while parsing the document');

        $sxe  = simplexml_import_dom($dom);
        $xpre = $sxe->xpath('//select[contains(@name, "MAIN_SKU.SIZE-")]/option');

        foreach($xpre AS $node) if (2 <= count($node->attributes()))

            return (string) $node;

        return false;
    };

    //actual start...
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

    if (! file_exists($inputFile) || $pass)
    {
        echo "No...\n";
        echo "Obtaining links list\n";

        if ($pass) file_put_contents(APP_DIR . '/input/html.json', ''); //clean

        $ch = curl_init();
        $payload = http_build_query([
            'DOINGSEARCH'           => 'TRUE',
            'DOSORT'                => 'TRUE',
            'LEVEL'                 => 'PRODUCT',
            'NAME'                  => '',
            'PAGE'                  => 'ZP_PRODUCT_SEARCH',
            'PRODUCT_SET.ID'        => '*ALL',
            'PRODUCT_TYPE.ID'       => '*ALL',
            'RESULTS_PER_PAGE'      => $limit,
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

        if (! $response) Throw New \Exception('Response empty...');

        $dom = New \DOMDocument();
        $dom->loadHTML($response);

        if (! $dom) Throw New \Exception('Error while parsing the document');

        $endpoints = [];

        $sxe = simplexml_import_dom($dom);

        $manager = [];
        foreach ($sxe->xpath('//tr[@data-productid]') AS $id => $node)
        {
            $pid = $getAttribute($node, 'data-productid');
            $rid = $getAttribute($node, 'data-refid');

            // create a node farm...
            if (is_numeric($rid))
            {
                $manager[] = (object) [
                    'PID'       => $pid,
                    'RID'       => $rid,
                    'PURL'      => ZR_ADMIN_URL . '?' . parse_url($getAttribute($node->td[8]->a, 'href')) ['query'],
                    'SURL'      => ZR_ADMIN_URL . '?' . parse_url($getAttribute($node->td[9]->a, 'href')) ['query'],
                    'Name'      => (string) $node->td[2]->span,
                    'Created'   => (string) $node->td[6]->span,
                    'Active'    => $getAttribute($node->td[7]->div->input, 'value'),
                ];
            }
        }

        echo "Manager empty? ";

        if (! empty($manager))
        {
            echo "No...\n\n";

            file_put_contents($inputFile, "[", LOCK_EX);

            $data  = [];
            $total = count($manager);

            $inc = 0;
            foreach ($manager AS $id => &$endpoint)
            {
                $pad = str_pad($inc, strlen($total), 0, STR_PAD_LEFT);

                echo "Capturing: ({$pad}/{$total}): {$endpoint->Name}\n";

                // get edit skus page
                echo " + Fetching Products page\n";
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL             => $endpoint->PURL,
                    CURLOPT_COOKIEFILE      => $cookieJar,
                    CURLOPT_SSL_VERIFYPEER  => 0,
                    CURLOPT_RETURNTRANSFER  => 1,
                    CURLOPT_HEADER          => 0,
                    CURLOPT_FOLLOWLOCATION  => 1,
                ]);

                $endpoint->PDATA = curl_exec($ch);
                curl_close($ch);

                // get edit product page
                echo " + Fetching SKUS page\n";
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL             => $endpoint->SURL,
                    CURLOPT_COOKIEFILE      => $cookieJar,
                    CURLOPT_SSL_VERIFYPEER  => 0,
                    CURLOPT_RETURNTRANSFER  => 1,
                    CURLOPT_HEADER          => 0,
                    CURLOPT_FOLLOWLOCATION  => 1,
                ]);

                $endpoint->SDATA = curl_exec($ch);
                curl_close($ch);

                echo " + Writing....\n";
                file_put_contents(
                    $inputFile, json_encode($endpoint) .
                    (++$inc !== $total
                        ? "," //add comma
                        : "]" //closing bracket, no comma
                    ), LOCK_EX | FILE_APPEND
                );

                //unpad to hundredths
                echo "Peak fake: ".(sprintf('%0.2f', memory_get_peak_usage(0)/1024/1024))." MiB\n";
                echo "Peak real: ".(sprintf('%0.2f', memory_get_peak_usage(1)/1024/1024)) ." MiB\n\n";
            }
            echo "Dumpfile Complete!\n";
        }
    }

    else
    {
        echo "Loader exists...\n";
    }


    ///////////


    if (file_exists($inputFile))
    {
        $object = json_decode(file_get_contents($inputFile));

        echo "Attempting to decode...\n";
        echo "JSON said: " . $err = json_last_error_msg() . "\n";

        if (0 !== json_last_error()) die;

        $output = [];
        foreach ($object AS $id => &$parent)
        {
            if (! empty($parent->SDATA)) // just a separator, quickly shut off
            {
                // SDATA
                $dom = New \DOMDocument();
                $dom->loadHTML($parent->SDATA);

                if (! $dom) Throw New \HttpException('Error while parsing the document');

                $sxe = simplexml_import_dom($dom);

                if (! is_object($sxe)) die("SimpleXML Object is empty..\n");

                $item = $sxe->xpath('//td[contains(@class,    "thirdTitle")]');
                $page = $sxe->xpath('//div[contains(@class,   "prsetpagelinks")]//a');


                //uses same class sub-tables, no uniques....
                $cost = $sxe->xpath('//form/table/tr[4]//td/table/tr[2]/td//input');
                $parent->Cost = '0.00';

                if (! empty($cost)) foreach ($cost AS $key => $obj)
                {
                    if (preg_match('/ZP_PRODUCT_OFFER.PRICE/', $getAttribute($cost [$key], 'name')))
                    {
                        $parent->Cost = $getAttribute($cost [$key], 'value');

                        break;
                    }
                }

                else
                {
                    //log missing
                    file_put_contents(APP_DIR .'/error/NoPriceErrors.log', json_encode([
                        'Name'  => (string) $item[0]->b,
                        'Error' => 'No price mentioned',
                        'SURL'  => $parent->SURL,
                        'PURL'  => $parent->PURL,
                    ], FILE_APPEND), LOCK_EX | JSON_PRETTY_PRINT);
                }

                // get the size declared
                $parent->Size = $getSizes($parent->SDATA);

                echo "Processing: " . (string)$item[0]->b . "\n";

                $endpoints = [];    //clear buffer
                if (!empty($page)) //we have several pagination going here....
                {
                    foreach ($page AS $attr) {
                        $endpoints[] = ZR_ADMIN_URL . "?" . explode('?', $getAttribute($attr, 'href')) [1];
                    }
                }

                //for trapping
                $product = (object) [
                    'name' => (string) $item[0]->b,
                    'sku'  => (string) $item[1]->b,
                    'ends' => $endpoints,
                ];

                $colors = []; //clear buffer
                $colors = $getCodes($parent->SDATA, $id, $product);

                if (! empty($endpoints))
                {
                    echo "Found " . $count = count($endpoints) . " additional pages in this set...\n"; //casts to right for error

                    $total = count($endpoints);
                    $inc   = 1;

                    $resultList = [];
                    foreach ($endpoints AS $endpoint)
                    {
                        $pad = str_pad($inc, strlen($total), 0, STR_PAD_LEFT);

                        echo "Capturing: ({$pad}/{$total}): {$endpoint}\n";

                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL => $endpoint,
                            CURLOPT_COOKIEFILE => $cookieJar,
                            CURLOPT_SSL_VERIFYPEER => 0,
                            CURLOPT_RETURNTRANSFER => 1,
                            CURLOPT_HEADER => 0,
                            CURLOPT_FOLLOWLOCATION => 1,
                        ]);

                        $resultList[] = curl_exec($ch);

                        //unpad to hundredths
                        echo "Peak fake: " . (sprintf('%0.2f', memory_get_peak_usage(0) / 1024 / 1024)) . " MiB\n";
                        echo "Peak real: " . (sprintf('%0.2f', memory_get_peak_usage(1) / 1024 / 1024)) . " MiB\n\n";
                        unset($result);

                        curl_close($ch);
                        $inc++;
                    }

                    if (! empty($resultList)) foreach ($resultList AS $did => $slice) $colors = ($getCodes($slice, $did, $product) + $colors);
                }

                $parent->Swatches = $colors;
            }

            if (isset($parent->SDATA)) unset($parent->SDATA);

            if (! empty($parent->PDATA))
            {
                // PDATA
                $dom = New \DOMDocument();
                $dom->loadHTML($parent->PDATA);

                if (! $dom) Throw New \HttpException('Error while parsing the document');
                $sxe = simplexml_import_dom($dom);

                if (! is_object($sxe)) die("SimpleXML Object is empty..\n");

                $data = $sxe->xpath('//form/table[2]/tr');

                $output = [];
                foreach ($data AS $value) if (isset($value->td[1]->textarea))

                    //$key = preg_replace_callback('/[A-Z]/', function($match) { return "_" . strtolower($match[0]); }, (string) $value->td[0]);

                    $output[
                    str_replace(' ', '',
                        ucwords(
                            strtolower(
                                str_replace('_', ' ', (string) $value->td[0])
                            )
                        )
                    )
                    ] = (string) $value->td[1]->textarea;

                $output['DefaultColor'] = $getAttribute($data[4]->td[1]->input, 'value');
                $parent->Configuration  = (object) $output;
            }

            if (isset($parent->PDATA)) unset($parent->PDATA);

            gc_disable(); //Disable Garbage Collector
        }

        echo "Writing....\n";

        file_put_contents(APP_DIR .'/input/Crawler-products.json', json_encode($object), LOCK_EX | JSON_PRETTY_PRINT);

        echo "\n\nFinished!!!\n\n";

    }

    else
    {
        Throw New \LogicException('Something broke in file writing sequence...');
    }