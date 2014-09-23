<?php

// add new option in manufacturer attribut by coding 'adidas' in manufacturer attribute
$attrCode = Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', "manufacturer");
$attrInfo = Mage::getModel('eav/entity_attribute')->load($attrCode);
$attrTable = Mage::getModel('eav/entity_attribute_source_table')->setAttribute($attrInfo);

$options = $attrTable->getAllOptions(false);
//$options = $attrInfo->getSource()->getAllOptions(false);
$_optionArr = [
    'value'  => [], 
    'order'  => [], 
    'delete' => []
];

foreach ($options AS $option)
{
    $_optionArr['value'][$option['value']] = [ 
        $option['label']
    ];

    $chkArray[] = $option['label'];
}
if (! in_array('adidas', $chkArray))
{
    $_optionArr['value']['option_1'] = [
        'adidas'
    ];

    $attrInfo->setOption($_optionArr);
    $attrInfo->save();
}

//Delete any option from manufacturer like 'adidas'

$attrCode=Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', "manufacturer");
$attrInfo = Mage::getModel('eav/entity_attribute')->load($attrCode);
$attrTable = Mage::getModel('eav/entity_attribute_source_table')->setAttribute($attrInfo);
$options = $attrTable->getAllOptions(false);
//$options = $attrInfo->getSource()->getAllOptions(false);
$_optionArr = array('value'=>array(), 'order'=>array(), 'delete'=>array());
foreach ($options as $option){
    $_optionArr['value'][$option['value']] = array($option['label']);
    if('adidas' == $option['label']){
        $_optionArr['delete'][$option['value']] = true;
    }
}
$attrInfo->setOption($_optionArr);