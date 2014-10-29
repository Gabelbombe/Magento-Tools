<?php

define ('APP_DIR', realpath(dirname(__DIR__)));

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

$products = $createFromCSV(APP_DIR . '/php/boots.csv');

$mage = [];
foreach ($products AS &$array)
{
    ksort($array); $mage[(! empty($array['_type']) ? $array['_type'] : 'option')][] = $array;
}


foreach ($mage AS $name => $array)
{
    echo "Name: {$name} = " .count($array) . "\n";
}


foreach ($mage['configurable'] AS $array)
{
    print_r($array);
}

die;
echo "From Program\n";

ksort($apps);
print_r(array_keys($apps));

    function compare_id($a, $b)
    {
        if ($a['_super_products_sku'] == $b['_super_products_sku'])
        {
            return 0;
        }

        return ($a['_super_products_sku'] < $b['_super_products_sku']) ? -1 : 1;
    }

    usort($apps['option'], 'compare_id');
    usort($mage['option'], 'compare_id');


foreach ($apps AS $name => $array) echo ucwords($name) . " Count: " . count($array)."\n";

echo "\n\n\n";

foreach ($mage AS $name => $set)
{
    echo "Type: {$name}\n";

    foreach ($set AS $id => $part)
    {

        echo "\tID: {$id} ------------------------------\n\t|\n";
        foreach ($part AS $k => $v) //if (!empty($v))
        {
            echo "\t|\t{$k}: {$v}::{$apps[$name][$id][$k]}\n";
        }

        echo "\n\n";
    }
}











    /*
    $new=$old=[];
    foreach ($testers AS $id => $array)
    {
        ksort($array); $old[(! empty($array['_type']) ? $array['_type'] : 'option')] = $array;
    }

    foreach ($products AS $id => $array)
    {
        ksort($array); $new[(! empty($array['_type']) ? $array['_type'] : 'option')] = $array;

    }

    foreach ($old AS $type => $subArray)
    {
        foreach ($subArray AS $key => $value)
        {
            if ($key == 'fit') continue;

            if ($new[$type][$key] != $value)
            {
                echo "{$type}\nKey: {$key}\nNew: {$new[$type][$key]}\nOld: {$value}\n\n";
            }
        }
    }
    */



//0 :: sku -> 1010101010101
//0 :: _attribute_set -> Alaska Fit Jackets, Coats, and Vests
//0 :: _type -> configurable
//0 :: _product_websites -> base
//0 :: amconf_simple_price -> No
//0 :: created_at -> 2014-10-14 08:04:33
//0 :: description -> Descr
//0 :: has_options -> 1
//0 :: is_returnable -> Use config
//0 :: msrp_display_actual_price_type -> Use config
//0 :: msrp_enabled -> Use config
//0 :: name -> Name
//0 :: options_container -> Product Info Column
//0 :: price -> 100.0000
//0 :: required_options -> 1
//0 :: short_description -> Short Descr
//0 :: status -> 1
//0 :: tax_class_id -> 2
//0 :: updated_at -> 2014-10-14 08:06:04
//0 :: url_key -> name
//0 :: visibility -> 4
//0 :: qty -> 0.0000
//0 :: min_qty -> 0.0000
//0 :: use_config_min_qty -> 1
//0 :: use_config_backorders -> 1
//0 :: min_sale_qty -> 1.0000
//0 :: use_config_min_sale_qty -> 1
//0 :: max_sale_qty -> 0.0000
//0 :: use_config_max_sale_qty -> 1
//0 :: is_in_stock -> 1
//0 :: use_config_notify_stock_qty -> 1
//0 :: use_config_manage_stock -> 1
//0 :: use_config_qty_increments -> 1
//0 :: qty_increments -> 0.0000
//0 :: use_config_enable_qty_inc -> 1
//0 :: _super_products_sku -> 2020202020202
//0 :: _super_attribute_code -> color
//0 :: _super_attribute_option -> Alabaster

//1 :: _super_products_sku -> 2020202020202
//1 :: _super_attribute_code -> fit
//1 :: _super_attribute_option -> Seattle Fit

//2 :: _super_products_sku -> 2020202020202
//2 :: _super_attribute_code -> alaska_fit_jackets_vests_sizes
//2 :: _super_attribute_option -> 36

//3 :: sku -> 2020202020202
//3 :: _attribute_set -> Alaska Fit Jackets, Coats, and Vests
//3 :: _type -> simple
//3 :: _product_websites -> base
//3 :: alaska_fit_jackets_vests_sizes -> 36
//3 :: color -> Alabaster
//3 :: created_at -> 2014-10-14 08:05:51
//3 :: description -> Descr
//3 :: fit -> Seattle Fit
//3 :: is_returnable -> Use config
//3 :: msrp_display_actual_price_type -> Use config
//3 :: msrp_enabled -> Use config
//3 :: name -> Name [Simple]
//3 :: options_container -> Product Info Column
//3 :: price -> 100.0000
//3 :: refid -> 1010101010101
//3 :: short_description -> Short Descr
//3 :: status -> 1
//3 :: tax_class_id -> 2
//3 :: updated_at -> 2014-10-14 08:05:51
//3 :: url_key -> name-simple
//3 :: visibility -> 4
//3 :: weight -> 1.0000
//3 :: qty -> 99.0000
//3 :: min_qty -> 0.0000
//3 :: use_config_min_qty -> 1
//3 :: use_config_backorders -> 1
//3 :: min_sale_qty -> 1.0000
//3 :: use_config_min_sale_qty -> 1
//3 :: max_sale_qty -> 0.0000
//3 :: use_config_max_sale_qty -> 1
//3 :: is_in_stock -> 1
//3 :: use_config_notify_stock_qty -> 1
//3 :: use_config_manage_stock -> 1
//3 :: use_config_qty_increments -> 1
//3 :: qty_increments -> 0.0000
//3 :: use_config_enable_qty_inc -> 1
