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
  \Mage::app();

  // $fileName = MAGENTO . '/var/import/importAttrib.csv';
  $fileName = 'importAttrib.csv';
  // $getCsv($fileName);

  $attributeValueExists = function ($argAttr, $argVal)
  {
    $attrModel     = \Mage::getModel('eav/entity_attribute');
    $attrOptsModel = \Mage::getModel('eav/entity_attribute_source_table') ;

    $attrCode      = $attrModel->getIdByCode('catalog_product', $argAttr);
    $attribute     = $attrModel->load($attrCode);

    $attrTable     = $attrOptsModel->setAttribute($attribute);
    $options       = $attrOptsModel->getAllOptions(false);

    foreach($options AS $option)
    {
      if ($option['label'] == $argVal) return $option['value'];
    }
    return false;
  };

  $addAttributeValue = function ($argAttr, $argVal)
  {
    $attrModel   = \Mage::getModel('eav/entity_attribute');

    $attrCode    = $attrModel->getIdByCode('catalog_product', $argAttr);
    $attribute   = $attrModel->load($attrCode);

    if(! $attributeValueExists($argAttr, $argVal))
    {
        $value['option'] = [
          $argVal, $argVal
        ];

        $result = [
          'value' => $value
        ];

        $attribute->setData('option',$result);
        $attribute->save();
    }

    $attrOptsModel = Mage::getModel('eav/entity_attribute_source_table') ;
    $attrTable     = $attrOptsModel->setAttribute($attribute);
    $options       = $attrOptsModel->getAllOptions(false);

    foreach($options as $option)
    {
      if ($option['label'] == $argVal) return $option['value'];
    }
    return false;
  };

  /**
   * Create an attribute.
   *
   * For reference, see Mage_Adminhtml_Catalog_Product_AttributeController::saveAction().
   *
   * @return int|false
   */
  $createAttribute = function createAttribute($labelText, $attributeCode, $values = -1, $productTypes = -1, $setInfo = -1, $options = -1)
  {

      $labelText = trim($labelText);
      $attributeCode = trim($attributeCode);

      if(! empty($labelText) || ! empty($attributeCode))
      {
          echo "Can't import the attribute with an empty label or code.  LABEL= [$labelText]  CODE= [$attributeCode]"."<br/>";
          return false;
      }

      $values = (-1 === $values) 
        ? $values
        : [];

      $productTypes = (-1 === $productTypes) 
        ? $productTypes 
        : [];

      if(-1 !== $setInfo && ! isset($setInfo['SetID']) || ! isset($setInfo['GroupID'])))
      {
          echo "Please provide both the set-ID and the group-ID of the attribute-set if you'd like to subscribe to one."."<br/>";
          return false;
      }

      echo "Creating attribute [$labelText] with code [$attributeCode]."."<br/>";

      /**
       * Build the data structure that will define the attribute. See
       * Mage_Adminhtml_Catalog_Product_AttributeController::saveAction().
       */

      $data = [
        'is_global'                     => '0',
        'frontend_input'                => 'text',
        'default_value_text'            => '',
        'default_value_yesno'           => '0',
        'default_value_date'            => '',
        'default_value_textarea'        => '',
        'is_unique'                     => '0',
        'is_required'                   => '0',
        'frontend_class'                => '',
        'is_searchable'                 => '1',
        'is_visible_in_advanced_search' => '1',
        'is_comparable'                 => '1',
        'is_used_for_promo_rules'       => '0',
        'is_html_allowed_on_front'      => '1',
        'is_visible_on_front'           => '0',
        'used_in_product_listing'       => '0',
        'used_for_sort_by'              => '0',
        'is_configurable'               => '0',
        'is_filterable'                 => '0',
        'is_filterable_in_search'       => '0',
        'backend_type'                  => 'varchar',
        'default_value'                 => '',
        'is_user_defined'               => '0',
        'is_visible'                    => '1',
        'is_used_for_price_rules'       => '0',
        'position'                      => '0',
        'is_wysiwyg_enabled'            => '0',
        'backend_model'                 => '',
        'attribute_model'               => '',
        'backend_table'                 => '',
        'frontend_model'                => '',
        'source_model'                  => '',
        'note'                          => '',
        'frontend_input_renderer'       => '',                      
      ];

      // Now, overlay the incoming values on to the defaults.
      foreach($values AS $key => $newValue)
          if(! isset($data[$key]))
          {
            echo "Attribute feature [$key] is not valid."."<br/>";
            return false;
          } else {
            $data[$key] = $newValue;
          }

      // Valid product types: simple, grouped, configurable, virtual, bundle, downloadable, giftcard
      $data['apply_to']       = $productTypes;
      $data['attribute_code'] = $attributeCode;
      $data['frontend_label'] = [
        0 => $labelText,
        1 => '',
        3 => '',
        2 => '',
        4 => '',
      ];

      // Build the model.
      $model = Mage::getModel('catalog/resource_eav_attribute');
      $model->addData($data);

      if(-1 !== $setInfo)
      {
          $model->setAttributeSetId($setInfo['SetID'])
                ->setAttributeGroupId($setInfo['GroupID']);
      }

      $entityTypeID = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();

      $model->setEntityTypeId($entityTypeID)
            ->setIsUserDefined(1);

      // Save.
      try
      {
          $model->save();
      }
      catch(Exception $ex)
      {
          echo "Attribute [$labelText] could not be saved: " . $ex->getMessage()."<br/>";
          return false;
      }

      if(is_array($options))
      {
          foreach($options AS $_opt) addAttributeValue($attributeCode, $_opt);
      }

      $id = $model->getId();

      echo "Attribute [$labelText] has been saved as ID ($id).<br/>";

      // return $id;
  };

  $getAttributeCsv = function getAttributeCsv() USE ($fileName)
  {
      // $csv = array_map("str_getcsv", file($fileName,FILE_SKIP_EMPTY_LINES));
      $file = fopen($fileName,"r");

      while(! feof($file)) $csv[] = fgetcsv($file, 0, '|');

      $keys = array_shift($csv);

      foreach ($csv AS $inc => $row) $csv[$inc] = array_combine($keys, $row);

      foreach($csv AS $row)
      {
          $labelText     = $row['frontend_label'];
          $attributeCode = $row['attribute_code'];

          // add this to createAttribute parameters and call "addAttributeValue" function.
          $options = (! empty($row['_options']))
            ? explode(";", $row['_options']) 
            : -1;

          $productTypes = (! empty($row['apply_to']))
            ? explode(",", $row['apply_to'])
            : -1;

          unset(
            $row['frontend_label'], 
            $row['attribute_code'], 
            $row['_options'], 
            $row['apply_to'], 
            $row['attribute_id'], 
            $row['entity_type_id'], 
            $row['search_weight']
          );

          $createAttribute($labelText, $attributeCode, $row, $productTypes, -1, $options);
      }
  }

  $getAttributeCsv();