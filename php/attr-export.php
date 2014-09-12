<?php

  /** 
   * Magento Export Attributes
   * CPR : ehime :: Jd Daniel
   * MOD : 2014-09-12 @ 13:07:55
   * VER : 1.0
   * 
   * DEP : Mage.php
   * Magento core action file
   */

  define('MAGENTO', realpath(dirname(__FILE__)));
  require_once MAGENTO . '/app/Mage.php';

  umask(0);
  Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
  $entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();

  $prepareCollection = function ($entityTypeId)
  {
    $resc = Mage::getSingleton('core/resource');
    $conn = $resc->getConnection('core_read');

    $selectAttribs = $conn
      ->select()
      ->from([
        'ea'    =>  $resc->getTableName('eav/attribute')
        ])

      ->join([
        'c_ea'  => $resc->getTableName('catalog/eav_attribute')
        ], 'ea.attribute_id = c_ea.attribute_id');

     // ->join([
     //  'e_ao'    => $resc->getTableName('eav/attribute_option'), [
     //    'option_id'
     //  ], 'c_ea.attribute_id = e_ao.attribute_id')

      // ->join([
      //   'e_aov' => $resc->getTableName('eav/attribute_option_value'), [
      //     'value'
      //     ]
      //   ], 'e_ao.option_id = e_aov.option_id and store_id = 0');

    $selectProdAttribs = $selectAttribs
      ->where('ea.entity_type_id = ' . $entityTypeId)
      ->order('ea.attribute_id ASC');

    $prodAttribs = $conn->fetchAll($selectProdAttribs);

    $select_attrib_option = $selectAttribs
      ->join([
        'e_ao'  => $resc->getTableName('eav/attribute_option'), [
          'option_id'
          ]
        ], 'c_ea.attribute_id = e_ao.attribute_id')

      ->join([
        'e_aov' => $resc->getTableName('eav/attribute_option_value'), [ 
          'value'
          ]
        ], 'e_ao.option_id = e_aov.option_id and store_id = 0')

      ->order('e_ao.attribute_id ASC');

    $prodAttribOpts = $conn->fetchAll($select_attrib_option);

        $mergeCollections = function () USE ($prodAttribs, $prodAttribOpts)
        {
          foreach ($prodAttribs AS $key => $_attribute)
          {
            $vals = [];
            $atid = $_attribute['attribute_id'];
            foreach ($prodAttribOpts AS $pao) if ((int) $attribId === (int) $pao['attribute_id']) $vals[] = $pao['value'];
            $prodAttribs[$key]['_options'] = (0 < count($vals))
              ? implode(';', $vals);
              : '';
            $prodAttribs[$key]['attribute_code'] = $prodAttribs[$key]['attribute_code'];  
          }
          return $prodAttribs;
        };

        $prepareCsv = function ($filename = "importAttrib.csv", $delimiter = '|', $enclosure = '"') USE ($mergeCollections()) 
        {
            $f = fopen('php://memory', 'w');
            $first = true;
            foreach ($attributesCollection AS $line) 
            {
              if($first)
              {
                  $titles = array();
                  foreach($line as $field => $val){
                      $titles[] = $field;
                  }
                  fputcsv($f, $titles, $delimiter, $enclosure);
                  $first = false;
              }
              fputcsv($f, $line, $delimiter, $enclosure); 
            }
            fseek($f, 0);

              header('Content-Type: application/csv');
              header('Content-Disposition: attachement; filename="'.$filename.'"');

            fpassthru($f);

            // exit();
        }

    $prepareCsv($attributesCollection);
  }



  $prepareCollection($entityTypeId); // gogogo
