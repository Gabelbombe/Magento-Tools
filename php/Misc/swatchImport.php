<?php

define ('URL', 'http://www.filson.com/images/products/swatches/detail/');
require dirname(__DIR__) . '/MageTools/Connection.php';

$log  = '/tmp/not-found.txt';
$conn = New \MageTools\PDOConfig();
$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$res = $conn->prepare('SELECT descr, value FROM zp_attribute_value_set_value WHERE attribute_value_set_id = 1');
$res->execute();

file_put_contents($log, '');            // flush or create log
if (! file_exists('/tmp/swatches/'))    // create if not exist

    mkdir('/tmp/swatches/', 0777);

$missing=0;
foreach($total = $res->fetchAll(\PDO::FETCH_CLASS, 'ArrayObject') AS $id => $ao)
{
    $imageArray = getimagesize(URL . "{$ao->value}.jpg");
    if(! $imageArray [0])
    {
        // didn't exist, log
        file_put_contents($log, "\n{$ao->descr}\n" . URL . "{$ao->value}.jpg\n\n", LOCK_EX | FILE_APPEND);
        echo "- Missing: {$ao->descr}\n";

        $missing++;

            continue;
    }

    $f = fopen(URL . "{$ao->value}.jpg", 'r');
    fseek($f, 0);

    $im = New \Imagick();
    $im->readimagefile($f);
    fclose($f);

    $im->setImageFormat('png');
    $im->setImageColorspace(\Imagick::COLORSPACE_RGB);
    $im->setImageCompression(\Imagick::COMPRESSION_UNDEFINED);
    $im->setImageCompressionQuality(0);
    $im->stripImage();

    $im->writeImage("/tmp/swatches/{$ao->value}.png");

    $im->clear();
    $im->destroy();

    echo "+ Created: {$ao->descr}\n";
}

echo "\n {$missing} out of " . count($total) . " not found.\n\n";