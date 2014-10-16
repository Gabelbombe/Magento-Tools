<?php
define('MAGENTO', realpath(dirname(dirname(__DIR__))) . '/Magento');
require_once MAGENTO . '/app/Mage.php';

\Mage::app('admin')->setUseSessionInUrl(0);
\Mage::getModel('catalog/product')->getCollection()->delete();

echo "All products have been removed...\n";