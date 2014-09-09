<?php

  /** 
   * Magento Add Subcategories
   * CPR : ehime :: Jd Daniel
   * MOD : 2014-09-09 @ 15:22:17
   * VER : 1.0
   * 
   * DEP : Mage.php
   * Magento core action file
   */

  define('MAGENTO', realpath(dirname(__FILE__)));
  require_once MAGENTO . '/app/Mage.php';

  umask(0);
  Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
  $count = 0;

  /**
   * Format:
   * 
   * 3,subcat
   * 4,subcat2
   * 6,subcat3
   */

  $file = fopen('./var/import/importCats.csv', 'r');

  while (false !== ($line = fgetcsv($file))) 
  { 
    $count++; //$line is an array of the csv elements

    if (! empty($line[0]) && ! empty($line[1])) 
    {
      $data = [
        'general' => [
          'path'              => $line[0],
          'name'              => $line[1],
          'meta_title'        => '',
          'meta_description'  => '',
          'is_active'         => 1,
          'url_key'           => (! empty($line[2] ? $line[2] : ''),
          'display_mode'      => 'PRODUCTS',
          'is_anchor'         => 0,
        ],
        'category' => [
          'parent'            => $line[0], ## 3 top level
        ],
      ];

      $storeId = 0;
      
      createCategory($data, $storeId);
      sleep(0.5);
      unset($data);
    }
  }

  function createCategory($data,$storeId) 
  {
    echo "Starting {$data['general']['name']} [{$data['category']['parent']}] ...";
         
      $category = Mage::getModel('catalog/category');
      $category->setStoreId($storeId);
      
      # Fix must be applied to run script
      #http://www.magentocommerce.com/boards/appserv/main.php/viewreply/157328/
    
      if (is_array($data)) 
      {
          $category->addData($data['general']);
          
          if (! $category->getId()) 
          {
              $parentId = $data['category']['parent'];
              if (! $parentId) 
              {
                $parentId = ($storeId)
                  ? Mage::app()->getStore($storeId)->getRootCategoryId();
                  : Mage_Catalog_Model_Category::TREE_ROOT_ID;
              }

              $parentCategory = Mage::getModel('catalog/category')->load($parentId);
              $category->setPath($parentCategory->getPath());

          }
          
          /**
           * Check "Use Default Value" checkboxes values
           */
          if ($useDefaults = $data['use_default']) foreach ($useDefaults AS $attributeCode) $category->setData($attributeCode, null);
          
          $category->setAttributeSetId($category->getDefaultAttributeSetId());
    
          if (isset($data['category_products']) && ! $category->getProductsReadonly()) 
          {
              $products = [];
              parse_str($data['category_products'], $products);
              $category->setPostedProducts($products);
          }
              
          try 
          {
              $category->save();
              echo "Suceeded <br /> ";
          }
          catch (Exception $e)
          {
              echo "Failed <br />";

          }
      }
  }
