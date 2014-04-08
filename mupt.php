<?php

/** 
 * Magento Update Production Totals (MUPT)
 * CPR : ehime :: Jd Daniel
 * MOD : 2012-08-09 @ 18:31:77
 * VER : 1.1
 * 
 * REQ : $csvFilename
 * CSV file to be parsed
 * 
 * REQ : $skuLocation
 * SKU field location in file
 * 
 * REQ : $qtyLocation
 * Quantity field location in file
 * 
 * DEP : Mage.php
 * Magento core action file
 */

//hijack ini prefs
ini_set("display_errors", 1);
ini_set("memory_limit","1024M");

$csvFilename = '*.csv';		//csv filename
$skuLocation = 0; 			//csv location of sku/upc
$qtyLocation = 0;			//csv location of quantity


//stage and initialize Magento
require_once 'app/Mage.php';
Mage::init();

//set file mode creation mask
umask(0);

//get core resource
$coreResource = Mage::getSingleton('core/resource') ;

//prep file for ingestion
$readfile = prepCSV($csvFilename,$skuLocation,$qtyLocation);

//begin write operation on connection
$write = $coreResource->getConnection('core_write');

//stage write markers
for ($i = 1; $i < count($readfile); $i++ ) 
{
	//get entity_id from false upc/sku 
	$entity_id = getEntityID_bySKU($write, $readfile[$i][0]);

	//modify db totals
	updateQTY ($write, $entity_id, $readfile[$i][1]);
}

echo 'Product totals modification complete';
//end update session

	/**
	* FUNCTION: updateQTY
	* 
	* REQ : $db_magento
	* Session resource
	* 
	* REQ : entity_id
	* Product entity value
	* 
	* REQ : $qty
	* Quantity to alter
	*/
 	function updateQTY ($db_magento, $entity_id, $qty) 
	{
		//EAV update query string
		$db_magento->query("UPDATE 	cataloginventory_stock_item s_i, 
									cataloginventory_stock_status s_s 

		   					SET		s_i.qty = '$qty', s_i.is_in_stock = IF('$qty'>0, 1,0), 
									s_s.qty = '$qty', s_s.stock_status = IF('$qty'>0, 1,0)

		   					WHERE 	s_i.product_id = '$entity_id' 
		   					AND 	s_i.product_id = s_s.product_id "
	   					);
	}

	/**
	* FUNCTION: getEntityID_bySKU
	* 
	* REQ : $db_magento
	* Session resource
	* 
	* REQ : $sku
	* False UPC/SKU to operate on
	*/
	function getEntityID_bySKU($db_magento, $sku) 
	{
		//obtain entity_id object from upc/sku chunk
		$entity_row = $db_magento->query("SELECT 	entity_id 
										  FROM 		catalog_product_entity p_e 
										  WHERE 	p_e.sku = '$sku'"
									  )->fetchObject();

		//cast entity object to string
		$entity_id  = $entity_row->entity_id;

		//return id
		return $entity_id;
	}

	/**
	* FUNCTION: getEntityID_bySKU
	* 
	* REQ : $file
	* Sourcefile to digest
	* 
	* REQ : $sku
	* False UPC/SKU to operate on
	* 
	* REQ : $qty
	* Quantity to alter
	*/
	function prepCSV($file, $sku, $qty) 
	{
		//instantiate file object handler for iterator abilities
		$csv = New SplFileObject($file, 'r');

		//set operation flag
		$csv->setFlags(SplFileObject::READ_CSV);

		//set delimiter and enclosures
		$csv->setCsvControl(',', '"', '\\');

		//instantiate $prep and incrementor
		$prep = array(); $i=0;

			//use limit iterator to skip first line
			foreach(New LimitIterator($csv, 1) AS $line)
			{
				$prep[$i][0] = $line[$sku]; //assign skus
				$prep[$i][1] = $line[$qty]; //assign qtys
				$i++; //increment for next array
			}

		 //return prepped array
		return $prep; 
	}