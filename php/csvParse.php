<?php
date_default_timezone_set('America/Los_Angeles');
ini_set('max_execution_time',0);
ini_set('memory_limit', '-1');
set_time_limit(0);

define ('APP_DIR',  realpath(dirname(__DIR__)));
define ('BASE',     realpath(dirname(dirname(__DIR__))) . '/Magento');

file_put_contents(APP_DIR . '/logs/zr_errors.log',           ''); // clear
file_put_contents(APP_DIR . '/php/error/compilation.json',   '');
file_put_contents(APP_DIR . '/php/output/abstract.json',     '');


require APP_DIR . '/php/input/AttrTemplate.php';
require APP_DIR . '/php/input/AttrSizes.php';
require APP_DIR . '/php/input/AttrMap.php';

$isSimple = false;

$fabrics = [];
$recurKsort = function(&$array) USE (&$recurKsort)
{
    foreach ($array AS &$value) if (is_array($value)) $recurKsort($value);

    return ksort($array);
};

$createFromCSV = function ($file)
{
    $array  = array_map('str_getcsv', file($file));
    $header = array_shift($array);

    array_walk($array, function (&$row, $null, $header)
    {
        if (count($header) !== count($row))
        {
            $tmp = [];
            foreach ($header AS $inc => $key) $tmp[$key] = (isset($row[$inc])) ? $row[$inc] : false;
            file_put_contents(APP_DIR . '/logs/zr_errors.log', json_encode($tmp, JSON_PRETTY_PRINT), LOCK_EX | FILE_APPEND);
            $row = $tmp;
        } else $row = array_combine($header, $row);

    }, $header);

    return array_filter($array);
};

$createWhitelist = function (array $input)
{
    $output = [];
    foreach($input AS $array) if (5 < strlen($array['sku'])) $output[] = $array['sku'];
    return array_filter($output);
};

$objToArr = function ($obj) USE (&$objToArr)
{
    if(is_object($obj)) $obj = (array) $obj;
    if(is_array($obj))
    {
        $new = [];
        foreach($obj AS $key => $val) $new[$key] = $objToArr($val);
    } else $new = $obj;

    return $new;
};

/**
 * Builds a Cartesian array based of sub array contents
 * then merges those with a blank template to create a
 * multi-layered product(s)
 *
 * @param array $blank
 * @param array $input
 * @return array
 */
$build = function ($blank, $input)
{
    $result = [];
    while (list($key, $values) = each($input))
    {
        if (empty($values)) continue;
        if (empty($result))

            foreach($values AS $value) $result[] = [$key => $value];

        else
        {
            $append = [];
            foreach($result as &$product)
            {
                $product[$key] = array_shift($values);
                $copy = $product;

                foreach($values AS $item)
                {
                    $copy[$key] = $item;
                    $append[] = $copy;
                }
                array_unshift($values, $product[$key]);
            }

            // Out of the foreach, we can add to $results now
            $result = array_merge($result, $append);
        }
    }

    $products = [];
    foreach ($result AS $k => $v) $products[] = ($v + $blank);
    return $products;
};


// .................


$products = $createFromCSV(APP_DIR . '/php/input/Mage-products.csv'); // was products-config
$coalesce = $createFromCSV(APP_DIR . '/php/input/ZR-dumpfile.csv');

$complexArray = [];
$whitelist = [];


$json = json_decode(file_get_contents(APP_DIR . '/php/input/Crawler-products.json'));

//clean up old inputs
if (isset($json[0]->PDATA))
{
    echo "File needs refreshing...\n";
    foreach($json AS $objProducts)
    {
        $objProducts->Configuration = $objProducts->PDATA;
        $objProducts->Swatches      = $objProducts->SDATA;

        unset($objProducts->PDATA, $objProducts->SDATA);
    }

    // refresh
    file_put_contents(APP_DIR . '/php/input/Crawler-products.json', json_encode($json, JSON_PRETTY_PRINT), LOCK_EX);
}

// this is literally all category mapping stuff.....
foreach($json AS $id => &$objProducts)
{
    $objProducts->AttributeSet = '';

    if ($objProducts->Active == 'No') // skip no's
    {
        unset($objProducts);
        continue;
    }

    if (preg_match('/135/', $objProducts->Size))
    {
        $objProducts->AttributeSet = 'One Size Fits All';
        continue;
    }

    //oddballs...
    if (preg_match('/(heavy|mid|light)weight/i', $objProducts->Name))
    {
        if (! preg_match('/case/i', $objProducts->Name))
        {
            $objProducts->AttributeSet = 'Baselayers';
            continue;
        }
    }
    if (preg_match('/antique tin cloth 5-pocket pants/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'Fixed Pants';
        continue;
    }




    ///////////
    if (preg_match('/pouch/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'One Size Fits All';
        continue;
    }

    if (preg_match('/(dog|puppy)/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = (! preg_match('/collar/i', $objProducts->Name))
            ? 'Dog Accessories'
            : 'Dog Collars';
        continue;
    }

    if (preg_match('/(chap|bibs)/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'Chaps';
        continue;
    }

    if (preg_match('/(shirt|top)/i', $objProducts->Name) || 'shirt' == strtolower($objProducts->Configuration->MerchType))
    {


        $objProducts->AttributeSet = 'Shirts';
        continue;
    }

    if (preg_match('/(sweater|pullover)/i', $objProducts->Name) || 'sweater' == strtolower($objProducts->Configuration->MerchType))
    {
        $objProducts->AttributeSet = 'Sweaters';
        continue;
    }

    if (preg_match('/(hat|cap|beanie)/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'Hats and Caps';
        continue;
    }

    if (preg_match('/belt/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'Belts';
        continue;
    }

    if (preg_match('/strap/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'Shoulder Straps';
        continue;
    }

    if (preg_match('/(pant|trouser|jeans)/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'Pants';
        continue;
    }

    if (preg_match('/wader/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'Waders';
        continue;
    }

    if (preg_match('/suspender/i', $objProducts->Name)
    || 'suspenders' == strtolower($objProducts->Configuration->MerchType))
    {
        $objProducts->AttributeSet = 'Suspenders';
        continue;
    }

    if (preg_match('/(boot|shoe)/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'Shoes';
        continue;
    }

    if (preg_match('/scabbard/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'Gun Scabbards';
        continue;
    }

    if (preg_match('/(jacket|coat|vest|cruiser|shell|bomber|blazer)/i', $objProducts->Name)
    || 'jacket' == strtolower($objProducts->Configuration->MerchType))
    {
        $objProducts->AttributeSet = 'Unknown';

        if (preg_match('/seattle/i', $objProducts->Name)
        || preg_match('/seattle fit/i', $objProducts->Configuration->Conditions) // yeah i don't get it either =/
        || preg_match('/seattle fit/i', $objProducts->Configuration->Fit))
        {
            $objProducts->AttributeSet = 'Seattle Fit Jackets, Coats and Vests';
        }

        if (preg_match('/alaska/i', $objProducts->Name)
        || preg_match('/alaska fit/i', $objProducts->Configuration->Conditions) // yeah i don't get it either =/
        || preg_match('/alaska fit/i', $objProducts->Configuration->Fit))
        {
            if (preg_match('/(jacket|coat|shell|bomber)/i', $objProducts->Name))
            {
                $objProducts->AttributeSet = 'Alaska Fit Jackets and Coats';
            }

            if (preg_match('/(vest|cruiser|blazer)/i', $objProducts->Name))
            {
                $objProducts->AttributeSet = 'Alaska Fit Vests and Cruisers';
            }

            if (empty($objProducts->AttributeSet))
            {
                print_r($objProducts); die('Set Empty...');
            }
        }
        continue;
    }

    if (preg_match('/(sack|satchel|case|bag|pack|roll|cooler|tote|kit|suit cover|pullman|carry|case|duffle|sleeve)/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'One Size Fits All';
        continue;
    }

    if (preg_match('/(glove|mittens)/i', $objProducts->Name) || 'foot' == strtolower($objProducts->Configuration->MerchType))
    {

        $objProducts->AttributeSet = 'Gloves';
        continue;
    }

    if (preg_match('/sock/i', $objProducts->Name))
    {
        $objProducts->AttributeSet = 'Socks';
        continue;
    }

    if (empty($objProducts->AttributeSet))
    {
        $objProducts->AttributeSet = 'One Size Fits All';
        continue;
    }
}

foreach($json AS &$objProducts)
{
    // set everything needed now so we have a complete object
    $objProducts->Attributes = (isset($attrMap[$objProducts->AttributeSet]))
        ? $attrMap[$objProducts->AttributeSet]
        : ['Unknown'];

    // set prices unless broken
    $objProducts->Cost = (isset($objProducts->Cost) && ! empty($objProducts->Cost))
        ? $objProducts->Cost
        : '0.00';

    if (isset($attrSizes[$objProducts->Size]))
    {
        $objProducts->Size = str_replace('½', '1/2', $objProducts->Size);

        foreach (preg_replace('/(.*) \((.*)\)/', '$2$$$1', array_keys($attrSizes, $attrSizes[$objProducts->Size], 1)) AS $key)
        {
            $values = explode('$$', $key); // bleh...

            $objProducts->SkuOrder[$values[0]] = $values[1];
        }
    }
}

echo "All products have been assigned\n";

    file_put_contents(APP_DIR . '/php/output/dump.json', json_encode($json, JSON_PRETTY_PRINT));
    file_put_contents(APP_DIR . '/php/error/compilation.json', ''); //clean logfile


$skuWhitelist    = [];
$simpleProducts  = [];
$complexProducts = [];
$i = 0;

//$json = [$json[0]];

foreach ($json AS &$objProducts)
{
    $objProducts->Swatches = (array) $objProducts->Swatches;

    if ('Yes' != trim($objProducts->Active)) continue;

    $fabrics[preg_replace([
        '/[0-9]-oz\./i',
        '/\./',
        '/([0-9]|%|-oz|)/',
        '/\s+/',
        '/^\s/',
        ],

        [
            '',
            '',
            '',
            ' ',
            '',
        ],

    //storage
    $objProducts->Configuration->Fabric)] = $objProducts->Configuration->Fabric;

    if (! isset($objProducts->Attributes['options']) || ! isset($objProducts->SkuOrder))
    {
        $objProducts->Reason =
        $reason = (! isset($objProducts->Attributes['options']) ? "Missing Options" : "Missing SKU Order");

        file_put_contents(APP_DIR . '/php/error/compilation.json', json_encode($objProducts, JSON_PRETTY_PRINT), LOCK_EX | FILE_APPEND);

            echo "Skipped: {$objProducts->Name}\n";
            echo "Created: {$objProducts->Created}\n";
            echo "SReason: $reason\n";
            echo "Line No: " . ++$i . '/' .count($json) ."\n\n";

        continue;
    }

    if (! array_key_exists(preg_replace('/(.*) \((.*)\)/', '$2', $objProducts->Size), $objProducts->SkuOrder))
    {
        $objProducts->Reason = 'Identifier not in SkuOrder';

        file_put_contents(APP_DIR . '/php/error/compilation.json', json_encode($objProducts, JSON_PRETTY_PRINT), LOCK_EX | FILE_APPEND);

        echo "Skipped: {$objProducts->Name}\n";
        echo "Created: {$objProducts->Created}\n";
        echo "SReason: $objProducts->Reason\n";
        echo "Line No: " . ++$i . '/' .count($json) ."\n\n";

        continue;
    }

    // options are a non-iterable item so save then drop...
    $opts = $objProducts->Attributes['options'];
    unset($objProducts->Attributes['options']);


    $attributes = array_keys($objProducts->Attributes);

    if (! preg_match('/one size/i', $objProducts->Size))
    {
        $attrShift = $objProducts->Attributes;
        $sizeCode = @array_flip ($objProducts->SkuOrder) [array_shift ($attrShift) [0]];

        if (! $sizeCode)
        {
            $objProducts->Reason = 'Attributes do no match';

            file_put_contents(APP_DIR . '/php/error/compilation.json', json_encode($objProducts, JSON_PRETTY_PRINT), LOCK_EX | FILE_APPEND);

            echo "Skipped: {$objProducts->Name}\n";
            echo "Created: {$objProducts->Created}\n";
            echo "SReason: $objProducts->Reason\n";
            echo "Line No: " . ++$i . '/' .count($json) ."\n\n";

            continue;        }

    } else {
        $sizeCode = 135;
    }

    $colorCode  = array_keys($objProducts->Swatches) [0];
    $baseline   = $objProducts->RID . $colorCode . $sizeCode;

    $cTemp                          = $attrTemplate; //seeder
    $cTemp['_type']                 = 'configurable';
    $cTemp['name']                  = $objProducts->Name;
    $cTemp['sku']                   = $objProducts->RID;
    $cTemp['price']                 = number_format((float)$objProducts->Cost, 4, '.', '');
    $cTemp['created_at']            = date("Y-m-d H:i:s", strtotime($objProducts->Created));
    $cTemp['updated_at']            = date("Y-m-d H:i:s", strtotime('NOW'));
    $cTemp['url_key']               = preg_replace(
        [
            '/-/',
            '/\s+/',
            '/"|\'/',
            '/\s+|\/|\./',
        ],
        [
            ' ',
            ' ',
            '',
            '-',],
        strtolower($objProducts->Name)
    ) . "-{$objProducts->RID}";

    // we don't really know
    $cTemp['description']            = $objProducts->Configuration->Description;
    $cTemp['short_description']      = $objProducts->Configuration->ShortDescr;
    $cTemp['amconf_simple_price']    = 'No';

    $cTemp['required_options']       = '1';
    $cTemp['has_options']            = '1';

    $cTemp['_attribute_set']          = $objProducts->AttributeSet;
    $cTemp['_super_products_sku']     = $baseline;
    $cTemp['_super_attribute_code']   = 'color';
    $cTemp['_super_attribute_option'] = array_values($objProducts->Swatches) [0];

    $cTemp['is_in_stock']             = ('Yes' == trim($objProducts->Active) ? 1 : 0);

    $complexSizes = [];
    $complexTypes = [];

    // creates simple children
    foreach ($objProducts->Swatches AS $swatchId => $swatchName) // run swatches as leading
    {
        if (! is_array(current($objProducts->Attributes)))
        {
            $singleProducts[] = $objProducts;
            continue;
        }

        $primary  = key($objProducts->Attributes);
        $sProduct = strtolower("{$objProducts->Name}-{$swatchName}"); //easier than or
        $sProduct = trim(preg_replace('/\-/', '-', preg_replace(
            [
                '/-/',
                '/\s+/',       // remove extra whitespace
                '/"|\'/',      // single/double quotes to inches
                '/\s+|\/|\./', // slashes or spaces to dashes
            ],
            [
                ' ',
                ' ',
                '',            // in/ft
                '-',
            ],
            $sProduct
        )));

        $sTemp                                  = $attrTemplate; //seeder
        $sTemp['_type']                         = 'simple';

        $sTemp['price']                         = number_format((float)$objProducts->Cost, 4, '.', '');
        $sTemp['color']                         = trim($swatchName);

        $sTemp['created_at']                    = date("Y-m-d H:i:s", strtotime($objProducts->Created));
        $sTemp['updated_at']                    = date("Y-m-d H:i:s", strtotime('NOW'));

        // we don't really know
        $sTemp['description']                   = $objProducts->Configuration->Description;
        $sTemp['short_description']             = $objProducts->Configuration->ShortDescr;

        $sTemp['_attribute_set']                = $objProducts->AttributeSet;
        $sTemp['is_in_stock']                   = ('Yes' == trim($objProducts->Active) ? 1 : 0);


        /////////////////////////////////////////////////////////////////////
        $simpleArray = $build($sTemp, array_reverse($objProducts->Attributes));
        $lookup      = array_flip ((array) $objProducts->SkuOrder);

        // assemble all simples
        $default = $objProducts->RID . $swatchId . $sizeCode;
        $skuMap  = [];


        foreach ($simpleArray AS $inc => $products)
        {
            $urlKey = ('Shoes' == $objProducts->AttributeSet)
                ? "{$products[$primary]}-{$products['shoe_width']}"
                : $products[$primary];


            if ($swatchName == array_values($objProducts->Swatches) [0])
            {
                $simpleProducts[] = ([
                    'name'      => "{$objProducts->Name}: {$swatchName}, {$urlKey}",
                    'sku'       => $default,
                    'url_key'   => strtolower("{$sProduct}-" . preg_replace(['/\s+/', '/\s+|\/|\./'], [' ', '-'], $urlKey))  . "-{$default}",
                ] + $products);

            } else {

                $complexProducts[] = ([
                    'name'      => "{$objProducts->Name}: {$swatchName}, {$products[$primary]}",
                    'sku'       => $default,
                    'url_key'   => strtolower("{$sProduct}-" . preg_replace(['/\s+/', '/\s+|\/|\./'], [' ', '-'], $urlKey))  . "-{$objProducts->RID}",
                ] + $products);
            }

            $skuMap[] = $default++;
        }

        if (135 != key($objProducts->SkuOrder))
        {
            $simpleColors = $simpleArray;

            // first complex has no main attribute
            if ($swatchName == array_values($objProducts->Swatches) [0])
                unset($simpleColors [0]);

            if (isset($skuWhitelist[$skuMap[$inc]])) continue;

                $skuWhitelist[$skuMap[$inc]] = false;

            // colors is primary so skip first
            foreach ($simpleColors AS $inc => $products)
            {
                $complexColors[] = ([
                    '_super_products_sku'       => $skuMap[$inc],
                    '_super_attribute_code'     => 'color',
                    '_super_attribute_option'   => $products['color'],
                ] + array_map(function() {}, $attrTemplate));
            }

            $sizeValues = array_combine($skuMap, array_values($objProducts->Attributes) [0]);

            if (empty($sizeValues))
            {
                $objProducts->Reason = 'Identifier not in SkuOrder';

                file_put_contents(APP_DIR . '/php/error/compilation.json', json_encode([
                    'Object'            => $objProducts,
                    'Skus Mapped'       => $skuMap,
                    'Values attemped'   => array_values($objProducts->Attributes),
                ], JSON_PRETTY_PRINT), LOCK_EX | FILE_APPEND);

                echo "Skipped: {$objProducts->Name}\n";
                echo "Created: {$objProducts->Created}\n";
                echo "SReason: $objProducts->Reason\n";
                echo "Line No: " . ++$i . '/' .count($json) ."\n\n";

                continue;
            }

            foreach ($sizeValues AS $sku => $option)
            {
                $complexSizes[] = ([
                    '_super_products_sku'       => $sku,
                    '_super_attribute_code'     => array_keys($objProducts->Attributes) [0],
                    '_super_attribute_option'   => $option,
                ] + array_map(function() {}, $attrTemplate));
            }

            $attrLeftovers = array_slice($objProducts->Attributes, 1);

            if (! empty($attrLeftovers))
            {
                foreach (array_slice($objProducts->Attributes, 1) AS $attr => $attrArray)
                {
                    foreach ($attrArray  AS $option)
                    foreach ($sizeValues AS $sku => $null)
                    {
                        $complexTypes[$attr][] = ([
                            '_super_products_sku'       => $sku,
                            '_super_attribute_code'     => $attr,
                            '_super_attribute_option'   => $option,
                        ] + array_map(function() {}, $attrTemplate));
                    }
                }
            }
        }

        else
        {
            $key = key($objProducts->SkuOrder);

            $simpleProducts[] =  ([
                'name'    => "{$objProducts->Name}: {$swatchName}",
                'sku'     => "{$objProducts->RID}{$swatchId}{$key}",
                'url_key' => strtolower($sProduct) . "-{$objProducts->RID}",
            ] + $sTemp);
        }
    }

    if (! $isSimple)
    {
        if (!empty($complexProducts) && isset($cTemp['_attribute_set']) && ! preg_match('/one size/i', $objProducts->AttributeSet))
        {
            // complex bind must after all simples, but before all other options
            $complexArray[] = $cTemp;

            if (!empty($complexColors))
                foreach ($complexColors AS $products)
                    $complexArray[] = $products;

            if (!empty($complexSizes))
                foreach ($complexSizes AS $products)
                    $complexArray[] = $products;

            if (!empty($complexTypes))
                foreach ($complexTypes AS $type)
                    foreach ($type AS $products)
                        $complexArray[] = $products;
        }

        $complexColors = $complexSizes = $complexTypes = [];
    }
}

if ('all' === $isSimple)
{
    $input = array_merge($simpleProducts, $complexProducts);

}else if($isSimple){
    $input = $simpleProducts;
}else{
    $input = $complexArray;
}

$simpleSorted = [];
$simpleTemp   = [];
$abstract     = [];
$i=1;

echo "Found: " . count($input) . "\n";
echo "Remapping contents\n";
foreach($input AS $key => $products)
{
    foreach ($attrTemplate AS $aKey => $null)
    {
        $simpleTemp[$aKey] = $products[$aKey];
    }

    $abstract[] = ['line' => $i++, $simpleTemp];

    $whitelist[$products['url_key']] = null;

    $simpleSorted[$key] = $simpleTemp;
}

    echo "Writing Abstract\n";
    file_put_contents(APP_DIR . '/php/output/abstract.json', json_encode($abstract, JSON_PRETTY_PRINT), LOCK_EX);

    $type = (! $isSimple)
        ? 'complex'
        : 'simple';


echo "$type\n";
echo "Writing contents to: {$type}-products.csv\n";
$csvFile = New SplFileObject(APP_DIR . "/php/output/{$type}-products.csv", 'w');   // Debug: new SPLTempFileObject();
$csvFile->fputcsv(array_keys($attrTemplate));
foreach ($simpleSorted AS $export)
{
    echo "."; $csvFile->fputcsv((array) $export);
}

echo "\n\nDone!!!\n";