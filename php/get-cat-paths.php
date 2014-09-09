<?php

  /** 
   * Magento Get CAT by Path
   * CPR : ehime :: Jd Daniel
   * MOD : 2014-09-09 @ 15:56:45
   * VER : 1.0
   * 
   * DEP : Mage.php
   * Magento core action file
   */

  define('MAGENTO', realpath(dirname(__FILE__)));
  require_once MAGENTO . '/app/Mage.php';
  mage::init();

  umask(0);

  // load all category paths
  $allCatPaths = [];
  $categories = Mage::getResourceModel('catalog/category_collection')->addAttributeToSelect('name')->getItems();

  foreach( $categories AS $_category)
  {
      $path = array_slice(explode('/', $_category->getPath()),2);//remove array_slice if you want to include root category in path

        foreach($path AS $_k => $_v) $path[$_k]=str_replace('/','\/', $categories[$_v]->getName());

      $allCatPaths[$_category->getId()]= strtolower(join('/',$path));
  }

  print_r($allCatPaths);