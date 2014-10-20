<?php

// There's some more advanced logic above the foreach loop which determines how to define $configurable_attribute,
// which is beyond the scope of this article. For reference purposes, I'm hard coding a value for
// $configurable_attribute here, and it's associated numerical attribute ID...
$configurable_attribute = "size";
$attr_id = 134;
$simpleProducts = array();
$lowestPrice = 999999;

// Loop through a pre-populated array of data gathered from the CSV files (or database) of old system..
foreach ($main_product_data['simple_products'] as $simple_product_data)
{
    // Again, I have more logic to determine these fields, but for clarity, I'm still including the variables here hardcoded..
    $attr_value = $simple_product_data['size'];
    $attr_id = 134;

    // We need the actual option ID of the attribute value ("XXL", "Large", etc..) so we can assign it to the product model later..
    // The code for getAttributeOptionValue and addAttributeOption is part of another article (linked below this code snippet)
    $configurableAttributeOptionId = getAttributeOptionValue($configurable_attribute, $attr_value);
    if (!$configurableAttributeOptionId) {
        $configurableAttributeOptionId = addAttributeOption($configurable_attribute, $attr_value);
    }

    // Create the Magento product model
    $sProduct = Mage::getModel('catalog/product');
    $sProduct
        ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
        ->setWebsiteIds(array(1))
        ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
        ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
        ->setTaxClassId(5)
        ->setAttributeSetId($_attributeSetMap[$data['product_type']]['attribute_set'])
        ->setCategoryIds($magento_categories) // Populated further up the script
        ->setSku($simple_product_data['sku'])
        // $main_product_data is an array created as part of a wider foreach loop, which this code is inside of
        ->setName($main_product_data['product_name'] . " - " . $attr_value)
        ->setShortDescription($main_product_data['short_description'])
        ->setDescription($main_product_data['long_description'])
        ->setPrice(sprintf("%0.2f", $simple_product_data['price']))
        ->setData($configurable_attribute, $configurableAttributeOptionId)
    ;

    // Set the stock data. Let Magento handle this as opposed to manually creating a cataloginventory/stock_item model..
    $sProduct->setStockData(array(
        'is_in_stock' => 1,
        'qty' => 99999
    ));

    $sProduct->save();

    // Store some data for later once we've created the configurable product, so we can
    // associate this simple product to it later..
    array_push(
        $simpleProducts,
        array(
            "id" => $sProduct->getId(),
            "price" => $sProduct->getPrice(),
            "attr_code" => $configurable_attribute,
            "attr_id" => $attr_id,
            "value" => $configurableAttributeOptionId,
            "label" => $attr_value
        )
    );

    if ($simple_product_data['price'] < $lowestPrice) {
        $lowestPrice = $simple_product_data['price'];
    }
}


// configurable
$cProduct = Mage::getModel('catalog/product');
$cProduct
    ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
    ->setTaxClassId(5)
    ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
    ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
    ->setWebsiteIds(array(1))
    ->setCategoryIds($magento_categories)
    ->setAttributeSetId(1) // You can determine this another way if you need to.
    ->setSku("C" . $main_product_data['product_ref_code'])
    ->setName($main_product_data['product_name'])
    ->setShortDescription($main_product_data['short_description'])
    ->setDescription($main_product_data['long_description'])
    ->setPrice(sprintf("%0.2f", $lowestPrice))
    ->setUrlKey(getProductUrlKey($main_product_data['product_name']))
;

$cProduct->setCanSaveConfigurableAttributes(true);
$cProduct->setCanSaveCustomOptions(true);

$cProductTypeInstance = $cProduct->getTypeInstance();
// This array is is an array of attribute ID's which the configurable product swings around (i.e; where you say when you
// create a configurable product in the admin area what attributes to use as options)
// $_attributeIds is an array which maps the attribute(s) used for configuration so their numerical counterparts.
// (there's probably a better way of doing this, but i was lazy, and it saved extra db calls);
// $_attributeIds = array("size" => 999, "color", => 1000, "material" => 1001); // etc..

$cProductTypeInstance->setUsedProductAttributeIds(array($_attributeIds[$configurable_attribute]));

// Now we need to get the information back in Magento's own format, and add bits of data to what it gives us..
$attributes_array = $cProductTypeInstance->getConfigurableAttributesAsArray();
foreach($attributes_array as $key => $attribute_array) {
    $attributes_array[$key]['use_default'] = 1;
    $attributes_array[$key]['position'] = 0;

    if (isset($attribute_array['frontend_label'])) {
        $attributes_array[$key]['label'] = $attribute_array['frontend_label'];
    }
    else {
        $attributes_array[$key]['label'] = $attribute_array['attribute_code'];
    }
}

// Add it back to the configurable product..
$cProduct->setConfigurableAttributesData($attributes_array);

// Remember that $simpleProducts array we created earlier? Now we need that data..
$dataArray = array();
foreach ($simpleProducts as $simpleArray) {
    $dataArray[$simpleArray['id']] = array();
    foreach ($attributes_array as $attrArray) {
        array_push(
            $dataArray[$simpleArray['id']],
            array(
                "attribute_id" => $simpleArray['attr_id'],
                "label" => $simpleArray['label'],
                "is_percent" => false,
                "pricing_value" => $simpleArray['price']
            )
        );
    }
}

// This tells Magento to associate the given simple products to this configurable product..
$cProduct->setConfigurableProductsData($dataArray);

// Set stock data. Yes, it needs stock data. No qty, but we need to tell it to manage stock, and that it's actually
// in stock, else we'll end up with problems later..
$cProduct->setStockData(array(
    'use_config_manage_stock' => 1,
    'is_in_stock' => 1,
    'is_salable' => 1
));

// Finally...!
$cProduct->save();




$count = 0;
foreach ($listOfImages as $imagePath) {
    $mode = array();
    if ($count == 0) {
        $mode = array("thumbnail", "small_image", "image");
    }
    $cProduct->addImageToMediaGallery($imagePath, $mode, false, false);
}

// For good measure;
$cProduct->save();




//
//    $data = $this->getRequest()->getPost();
//
//    if($data)
//    {
//        // $sProduct is the object used for product creation
//        $sProduct = Mage::getModel('catalog/product');
//
//        $productData = [
//            'name'              => $data['name'],
//            'sku'               => $data['sku'],
//            'description'       => $data['description'],
//            'short_description' => $data['short_description'],
//            'weight'            => 1,               // whatever your product weighs
//            'status'            => $data['status'], // 1 => enabled, 0 => disabled
//            'visibility'        => '4',             // 1 => Not Visible Individually, 2 => Catalog, 3 => Search, 4 => Catalog, Search
//            'attribute_set_id'  => 4,               // default
//            'type_id'           => 'simple',
//            'price'             => $data['price'],
//            'tax_class_id'      => 0,               // None
//
//        ];
//
//        // traversing through each index of productData
//        foreach ($productData AS $key => $value)
//        {
//            $sProduct->setData($key, $value);
//        }
//
//        $sProduct->setData('color', $this->getOptionId('color', $data['color']));
//        $sProduct->setWebsiteIds([1]);
//        $sProduct->setStockData([
//            'manage_stock'              => 1,
//            'is_in_stock'               => 1,
//            'qty'                       => 10,
//            'use_config_manage_stock'   => 0,
//        ]);
//
//        $categoryIds = [2, 3]; // Use category ids according to your store
//        $sProduct->setCategoryIds($categoryIds);
//
//        // use the directory path to images you want to save for the product
//        try
//        {
//            // and finally you can call the save method to create the product
//            $sProduct->save();
//
//            // we are creating an array with some information which will be used to bind the simple products with the configurable
//            array_push($simpleProducts, [
//                "id"        => $sProduct->getId(),
//                "price"     => $sProduct->getPrice(),
//                "attr_code" => 'color',
//                "attr_id"   => 92, // i have used the hardcoded attribute id of attribute color, you must change according to your store
//                "value"     => $this->getOptionId('color', $data['color']),
//                "label"     => $sProduct['color'],
//            ]);
//
//            $cProduct = Mage::getModel('catalog/product');
//
//            $productData = [
//                'name'              => 'Main configurable Tshirt',
//                'sku'               => 'tshirt_sku',
//                'description'       => 'Clear description about your Tshirt that explains its features',
//                'short_description' => 'One liner',
//                'weight'            => 1,
//                'status'            => '1',
//                'visibility'        => '4',
//                'attribute_set_id'  => 4,
//                'type_id'           => 'configurable',
//                'price'             => 1200,
//                'tax_class_id'      => 0,
//            ];
//
//            foreach ($productData AS $key => $value)
//            {
//                $cProduct->setData($key, $value);
//            }
//
//            $cProduct->setWebsiteIds([1]);
//            $cProduct->setStockData([
//                'manage_stock'              => 1,
//                'is_in_stock'               => 1,
//                'qty'                       => 0,
//                'use_config_manage_stock'   => 0,
//            ]);
//
//            $cProduct->setCategoryIds([2, 3]);
//            $cProduct->setCanSaveConfigurableAttributes(true);
//            $cProduct->setCanSaveCustomOptions(true);
//
//            $cProductTypeInstance = $cProduct->getTypeInstance();
//
//            $attribute_ids = [92];
//            $cProductTypeInstance->setUsedProductAttributeIds($attribute_ids);
//
//            $attributesArray = $cProductTypeInstance->getConfigurableAttributesAsArray();
//
//            foreach ($attributesArray AS $key => $attributeArray)
//            {
//                $attributesArray[$key]['use_default'] = 1;
//                $attributesArray[$key]['position']    = 0;
//
//                $attributesArray[$key]['label'] = (isset($attributeArray['frontend_label']))
//                    ? $attributeArray['frontend_label']
//                    : $attributeArray['attribute_code'];
//            }
//
//            // Add it back to the configurable product..
//            $cProduct->setConfigurableAttributesData($attributesArray);
//
//            $dataArray = [];
//            foreach ($simpleProducts AS $simpleArray)
//            {
//                $dataArray[$simpleArray['id']] = [];
//
//                foreach ($attributesArray AS $key => $attrArray)
//                {
//                    array_push($dataArray[$simpleArray['id']], [
//                        "attribute_id"  => $simpleArray['attr_id'][$key],
//                        "label"         => $simpleArray['label'][$key],
//                        "is_percent"    => 0,
//                        "pricing_value" => $simpleArray['pricing_value'][$key]
//                    ]);
//                }
//            }
//
//            $cProduct->setConfigurableProductsData($dataArray);
//            $cProduct->save();
//
//        } catch (\Exception $e)
//        {
//            print_r($e);
//        }
//    }