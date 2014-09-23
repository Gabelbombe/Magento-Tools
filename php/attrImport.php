<?php

  /** 
   * Magento Import Attribute Options
   * CPR : ehime :: Jd Daniel
   * MOD : 2014-09-12 @ 13:07:55
   * VER : 1.0
   *
   * REF: http://www.webspeaks.in/2012/05/addupdate-attribute-option-values.html
   * REF: http://blog.goods-pro.com/1642/magento-code-snippet/
   * REF: http://www.magentotricks.com/magento-how-to-get-attribute-id-by-attribute-code/
   * 
   * DEP : Mage.php
   * Magento core action file
   *
   * DUMP: //92|4|color|||int|||select|Color|||0|1||0|||1|1|1|1|1|0|0|0|0|0|0|1|simple|1|0|0|0|1|Red;Green;Blue
   * 
   */

    ini_set('max_execution_time',0);
    ini_set('memory_limit', '-1');
    set_time_limit(0);

    define('MAGENTO', realpath(dirname(dirname(__DIR__))) . '/Magento');
    require_once MAGENTO . '/app/Mage.php';

    \Mage::app();

    // list of colors
    require 'sort.php';
    if (! empty($attrColors))
    {
        $attributeModel = \Mage::getModel('eav/entity_attribute')->getCollection()->addFieldToFilter('frontend_label', 'Color');
        $attributeCode  = $attributeModel->getData('attribute_code') [0]['attribute_code'];

        $attributeModel = \Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $attributeCode);

        foreach ($attrColors AS $attributeCode => $attributeOption)
        {
            $data = [
                'option' => [
                    'value' => [
                        0 => [                          // where 0 is optionId? storeId? can't remember
                            0 => $attributeOption,      // Admin
                            1 => 'default',             // Default store view
                            // how to modify position   ???
                            // how to modify is_default ???
                        ],
                    ],
                ],
            ];

            $attributeModel->addData($data)->save();

                print_r($attributeModel->getData());
        }
    }

    echo "\n\nDONE!!!\n";