<?php

/** 
 * Magento Update/Set Attributes (MUSA)
 * CPR : ehime :: Jd Daniel
 * MOD : 2012-08-09 @ 18:31:77
 * VER : 1b
 * 
 * REQ : $csvFilename
 * CSV file to be parsed
 * 
 * REQ : $skuLocation
 * SKU field location in file
 * 
 * REQS: $attribLocation
 * Attribute field location in file
 * 
 * DEP : Mage.php
 * Magento core action file
 * 
 * RUN: $ php -f musa.php
 */

//hijack ini prefs
ini_set("display_errors", 1);
ini_set("memory_limit","1024M");

$csvFilename 	= '*.csv';			//csv filename
$skuLocation 	= 0; 				//csv location of sku/upc
$attribLocation	= 4;				//csv location of attribute
$attribID		= 1691;				//attribute id to grep for

$update			= FALSE;			//update or list


/* * * * * * * DO NOT EDIT PAST THIS LINE * * * * * * */

//stage and initialize Magento
require_once 'app/Mage.php';
Mage::init();

//set file mode creation mask
umask(0);

//get core resource
$coreResource = Mage::getSingleton('core/resource') ;

//prep file for ingestion
$readfile = prepCSV($csvFilename,$skuLocation,$attribLocation);

//begin write operation on connection
$write = $coreResource->getConnection('core_write');

//stage write markers
for ($i = 1; $i < count($readfile)-1; $i++ ) 
{
	if (isset($update) && TRUE === $update) 
	{
		//modify db attribs
		print_r( updateAttrib ($write, $readfile[$i][0], $readfile[$i][1], $attribID) );
	} 

	else 
	{
		//list attribs in collection
		print_r( listAttrib ($write, $readfile[$i][0], $readfile[$i][1], $attribID) );
	}

}

echo "\r\nProduct attribute modification complete\r\n";
//end update session

	/**
	 * FUNCTION: updateAttrib
	 * 
	 * REQS: $db_magento
	 * Session resource
	 * 
	 * REQS: sku
	 * Product upc/sku value
	 * 
	 * REQS: $attrib
	 * Attribute to alter
	 * 
	 * REQS: $attribID
	 * Attribute ID we're grepping for
	 */
	function updateAttrib ($db_magento, $sku, $attrib, $attribID) 
	{

		//EAV update query string
		$db_magento->query("UPDATE 	cataloginventory_stock_item AS csi
							      	JOIN catalog_product_entity AS cpe 
							      		ON cpe.entity_id = csi.product_id

							      	JOIN catalog_product_entity_varchar AS cpev 
							      		ON cpev.entity_id = cpe.entity_id

						    SET 	cpev.value = '$attrib'

							WHERE 	attribute_id = '$attribID' 
						    AND 	sku = '$sku'"
					);

		return "Update: $sku \r\nAttrib: $attrib\r\n\r\n";
	}

	/**
	 * FUNCTION: listAttrib
	 * 
	 * REQS: $db_magento
	 * Session resource
	 * 
	 * REQS: $sku
	 * Product entity value
	 * 
	 * REQS: $attrib
	 * Attribute to alter
	 * 
	 * REQS: $attribID
	 * Attribute ID we're grepping for
	 */
	function listAttrib ($db_magento, $sku, $attrib, $attribID) 
	{
		//EAV query string
		return $db_magento->fetchall("SELECT 	sku, 
												product_id 	AS entity, 
												value 		AS origional, 
												'$attrib' 	AS substitute

									  FROM 		cataloginventory_stock_item AS csi
										  		JOIN catalog_product_entity AS cpe 
										  			ON cpe.entity_id = csi.product_id

												JOIN catalog_product_entity_varchar AS cpev 
													ON cpev.entity_id = cpe.entity_id

										WHERE 	attribute_id = '$attribID' 
										AND 	sku = '$sku'"
									);
	}

	/**
	 * FUNCTION: prepCSV
	 * 
	 * REQS: $file
	 * Sourcefile to digest
	 * 
	 * REQS: $sku
	 * False UPC/SKU to operate on
	 * 
	 * REQS: $attrib
	 * Quantity to alter
	 */
	function prepCSV($file, $sku, $attrib) 
	{
		//instantiate file object handler for iterator abilities
		$csv = new SplFileObject($file, 'r');

		//set operation flag
		$csv->setFlags(SplFileObject::READ_CSV);
		
		//set delimiter and enclosures
		$csv->setCsvControl(',', '"', '\\');

		//instantiate $prep and incrementor
		$prep = array(); $i=0;
		
			//use limit iterator to skip first line
			foreach (new LimitIterator($csv, 1) as $line)
			{
				$prep[$i][0] = $line[$sku]; //assign skus
				$prep[$i][1] = $line[$attrib]; //assign qtys
				$i++; //increment for next array
			}

	 	//return prepped array
		return $prep; 
	}

?>
