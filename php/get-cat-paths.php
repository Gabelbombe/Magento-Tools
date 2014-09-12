<?php

  /** 
   * Magento Get CAT by Path Map
   * CPR : ehime :: Jd Daniel
   * MOD : 2014-09-09 @ 15:56:45
   * VER : 1.2
   * 
   * DEP : Mage.php
   * Magento core action file
   */

  define('MAGENTO', realpath(dirname(__FILE__)));
  require_once MAGENTO . '/app/Mage.php';
  mage::init();

  umask(0);

  $s = function($a) { return strtolower($a); };

  // load all category paths
  $allCatPaths = [];
  $categories = Mage::getResourceModel('catalog/category_collection')
                ->addAttributeToSelect('name')
                ->getItems();

  foreach( $categories AS $_category)
  {
      $verbose = array_slice(explode('/', $_category->getPath()), 2); //remove array_slice if you want to include root category in path

        foreach($verbose AS $_k => $_v) $verbose[$_k] = str_replace('/','\/', $categories[$_v]->getName());
        $catSel = Mage::getModel('catalog/category')->load($_category->getId()); 

      $allCatPaths[$_category->getId()] = [ 
        'name'      => $_category['name'],
        'parent'    => $_category['parent_id'],
        'verbose'   => '/' . $s(implode('/', $verbose)),
        'numeric'   => '/' . $_category->getPath(),
        'descr' => [
            'id'      => $_category->getId(),
            'desc'    => $catSel->getDescription(),
            'url-key' => $catSel->getUrl_key(),
            'title'   => $catSel->getMetaTitle()
        ],

      ];
  }

  print_r($allCatPaths);