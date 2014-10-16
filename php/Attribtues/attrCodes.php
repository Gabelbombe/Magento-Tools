<?php

    /**
     * Magento Display all attributes
     * CPR : ehime :: Jd Daniel
     * MOD : 2014-10-16 @ 13:05:57
     * VER : 1.0
     *
     * DEP : Mage.php
     * Magento core action file
     */

    ini_set('max_execution_time',0);
    ini_set('memory_limit', '-1');
    set_time_limit(0);

    define('BASE', realpath(dirname(dirname(dirname(__DIR__)))) . '/Magento');
    require_once BASE . '/app/Mage.php';

    \Mage::app();


    $attrSetCollection = \Mage::getResourceModel('eav/entity_attribute_set_collection')
                         ->load();

    foreach ($attrSetCollection AS $id => $attributeSet)
    {
        echo "ASID: {$attributeSet->getAttributeSetName()} -> {$id}\n";
    }

    echo "\n\n";

    // get individual attributes
    $attrCollection = \Mage::getResourceModel('catalog/product_attribute_collection')
                      ->getItems();

    foreach ($attrCollection AS $attr)
    {
        echo "ATID:  {$attr->getId()}\n";
        echo "CODE:  {$attr->getAttributecode()}\n";
        echo "LABEL: {$attr->getFrontendLabel()}\n\n";
    }

    echo "\n\nDONE!!!\n";