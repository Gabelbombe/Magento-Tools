<?php

/**
 * Filson Test Attribute Similarities
 * CPR : ehime :: Jd Daniel
 * MOD : 2014-09-26 @ 15:07:50
 * VER : 1.0
 */

ini_set('max_execution_time',0);
ini_set('memory_limit', '-1');
set_time_limit(0);

define ('TOOL_PATH',  realpath(dirname(dirname(__DIR__))) . '/Magento-Tools');
define ('SWATCH_URL', 'http://www.filson.com/images/products/swatches/detail/');

require_once TOOL_PATH . '/php/MageTools/Connection.php';

$conn = New \MageTools\PDOConfig();
$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    $res = $conn->prepare(trim('
      SELECT
        descr,
        value
      FROM
        zp_attribute_value_set_value
      WHERE
        attribute_value_set_id = 1
    '));

    $res->execute();

$attrColors = $res->fetchAll(\PDO::FETCH_CLASS, 'ArrayObject');

$whitelist =
$blacklist = [];

if (! empty($attrColors))
{
    foreach ($attrColors AS $attributeCode => $attributeObject)
    {
        if (! array_key_exists(strtolower($attributeObject->descr), $whitelist))
        {
            $whitelist[strtolower($attributeObject->descr)] = $attributeObject->value;
        } else {
            if (! array_key_exists(strtolower($attributeObject->descr), $blacklist))
            {
                $blacklist[strtolower($attributeObject->descr)] = [
                    $whitelist[strtolower($attributeObject->descr)],
                    $attributeObject->value,
                ];
            } else {
                $blacklist[strtolower($attributeObject->descr)] = (
                    $whitelist[strtolower($attributeObject->descr)] + // merge
                    $blacklist[strtolower($attributeObject->descr)]
                );
            }
        }
    }
}

foreach ($blacklist AS $color => $arrayValues)
{
    echo "{$color}\n";

    if (is_array($arrayValues))
    {
        $test = [];
        foreach ($arrayValues AS $colorCode)
        {
            $imageArray = getimagesize(SWATCH_URL . "{$colorCode}.jpg");

            if(! $imageArray [0])
            {
                echo "- {$colorCode} :: " . SWATCH_URL . "{$colorCode}.jpg\n";
            } else {
                echo "+ {$colorCode} :: " . SWATCH_URL . "{$colorCode}.jpg\n";

                $test[] = sha1(file_get_contents(SWATCH_URL . "{$colorCode}.jpg"));

                if (2 <= count($test))
                {
                    echo ($test[0] !== $test[1]) // test by file SHA
                        ? "> Different\n"
                        : "> Same\n";
                }
            }
        }
    }
    echo "\n\n";
}