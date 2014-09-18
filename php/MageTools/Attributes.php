<?php

Namespace // Assigning MAGE to a global namespace
{
	define('MAGENTO', realpath(dirname(dirname(__FILE__))));
	require_once MAGENTO . '/app/Mage.php';

	\Mage::app();
}

Namespace MageTools
{
	ini_set('display_startup_errors',1);
	ini_set('display_errors',1);
	error_reporting(-1);

	Class Attributes
	{
		protected $groupName 	= null;

		private   $errs			= [],
				  $info			= [];

		private	  $levels		= [				// >> push to custom err-trapper later
			1 		=> 'E_ERROR',				// Fatal run-time errors.
			2		=> 'E_WARNING',				// Non-fatal run-time errors.
			4		=> 'E_PARSE',				// Compile-time parse errors.
			8		=> 'E_NOTICE',				// Run-time notices.
			// ..	// ..						// ..
			2048	=> 'E_STRICT',				// Run-time notices.
			4096	=> 'E_RECOVERABLE_ERROR',	// Catchable fatal error.
			6143	=> 'E_ALL',					// All errors/warnings, sans of level E_STRICT
		];

		public function __construct()
		{
			umask(0);
        }

		/**
		 * Create an attribute-set.
		 * For reference, see Mage_Adminhtml_Catalog_Product_SetController::saveAction().
		 *
		 * @param $setName
		 * @param int $copyGroupsFromID
		 * @return array|bool
		 */
        public function createAttributeSet($setName, $copyGroupsFromID = -1)
        {
            $setName = trim($setName);
     
            $this->logInfo("Creating attribute-set with name [$setName].");
     
            if(empty($setName))
            {
                $this->logError("Could not create attribute set with an empty name.");

	                return false;
            }
     
            // Create an incomplete version of the desired set.
            $model = \Mage::getModel('eav/entity_attribute_set');

            // Set the entity type.
            $entityTypeID = \Mage::getModel('catalog/product')
								 ->getResource()
								 ->getTypeId();


            $this->logInfo("Using entity-type-ID ($entityTypeID).");
     
            $model->setEntityTypeId($entityTypeID);
     
            // We don't currently support groups, or more than one level. See
            // Mage_Adminhtml_Catalog_Product_SetController::saveAction().
            $this->logInfo("Creating vanilla attribute-set with name [$setName].");


			// We suspect that this isn't really necessary since we're just
			// initializing new sets with a name and nothing else, but we do
			// this for the purpose of completeness, and of prevention if we
			// should expand in the future.
            $model->setAttributeSetName($setName)
			      ->validate();
     
            try // Create the record.
            {
                $model->save();
            }
            catch(\Exception $ex)
            {
                $this->logError("Initial attribute-set with name [$setName] could not be saved: " . $ex->getMessage());

	                return false;
            }
     
            if(false === ($id = $model->getId()))
            {
                $this->logError("Could not get ID from new vanilla attribute-set with name [$setName].");

	                return false;
            }
     
            $this->logInfo("Set ($id) created.");

            // Load the new set with groups (mandatory).
            // Attach the same groups from the given set-ID to the new set.
            if(-1 === $copyGroupsFromID)
            {
                $this->logInfo("Cloning group configuration from existing set with ID ($copyGroupsFromID).");
               
                $model->initFromSkeleton($copyGroupsFromID);
            }
     
            else // Just add a default group.
            {
                $this->logInfo("Creating default group [{$this->groupName}] for set.");
     
                $modelGroup = \Mage::getModel('eav/entity_attribute_group');
                $modelGroup->setAttributeGroupName($this->groupName)
                		   ->setAttributeSetId($id);
     
                // This is optional, and just a sorting index in the case of
                // multiple groups.
                // $modelGroup->setSortOrder(1);
                $model->setGroups([$modelGroup]);
            }
     
            try //Save the final version of our set.
            {
                $model->save();
            }

            catch(\Exception $ex)
            {
                $this->logError("Final attribute-set with name [$setName] could not be saved: " . $ex->getMessage());

	                return false;
            }
     
			// As $modelGroup may not have been created
            if (! isset($modelGroup) || false === ($groupID = $modelGroup->getId()))
            {
                $this->logError("Could not get ID from new group [$this->groupName].");

	                return false;
            }
     
            $this->logInfo("Created attribute-set with ID ($id) and default-group with ID ($groupID).");
     
            return [
				'SetID'     => $id,
				'GroupID'   => $groupID,
			];
        }


		/**
		 * Create an attribute.
		 * For reference, see Mage_Adminhtml_Catalog_Product_AttributeController::saveAction().
		 *
		 * @param $labelText
		 * @param $attributeCode
		 * @param int $values
		 * @param int $productTypes
		 * @param int $setInfo
		 * @return bool
		 */
		public function createAttribute($labelText, $attributeCode, $values = -1, $productTypes = -1, $setInfo = -1)
		{

			$labelText 		= trim($labelText);
			$attributeCode	= trim($attributeCode);

			if (empty($labelText) || empty($attributeCode))
			{
				$this->logError("Can't import the attribute with an empty label or code.  LABEL= [$labelText]  CODE= [$attributeCode]");

					return false;
			}

			if (-1 === $values)
				$values = [];

			if (-1 === $productTypes)
				$productTypes = [];

			if (-1 !== $setInfo && (! isset($setInfo['SetID']) || ! isset($setInfo['GroupID'])))
			{
				$this->logError("Please provide both the set-ID and the group-ID of the attribute-set if you'd like to subscribe to one.");

					return false;
			}

			$this->logInfo("Creating attribute [$labelText] with code [$attributeCode].");

			// Build the data structure that will define the attribute. See
			// Mage_Adminhtml_Catalog_Product_AttributeController::saveAction().
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
			foreach ($values AS $key => $newValue)
			{
				if (! isset($data[$key]))
				{
					$this->logError("Attribute feature [$key] is not valid.");

						return false;
				}

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
			$model = \Mage::getModel('catalog/resource_eav_attribute');
			$model->addData($data);

			if(-1 === $setInfo)
			{
				$model->setAttributeSetId($setInfo['SetID'])
					  ->setAttributeGroupId($setInfo['GroupID']);
			}

			$entityTypeID = \Mage::getModel('eav/entity')
								 ->setType('catalog_product')
								 ->getTypeId();

			$model->setEntityTypeId($entityTypeID)
				  ->setIsUserDefined(1);

			try // Save
			{
				$model->save();
			}
			catch(\Exception $ex)
			{
				$this->logError("Attribute [$labelText] could not be saved: " . $ex->getMessage());

					return false;
			}

			$id = $model->getId();
			$this->logInfo("Attribute [$labelText] has been saved as ID ($id).");

				return $id;
		}


		/**
		 * Test if attribute code/value exists in Magentog
		 *
		 * @param $attrCode
		 * @param $attrValue
		 * @return bool
		 */
		protected function attributeValueExists($attrCode, $attrValue)
		{
			/**
			 * @var $attribute Mage_Eav_Model_Entity_Attribute_Abstract
			 */
			$attribute = \Mage::getModel('catalog/resource_eav_attribute')
							  ->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attrCode);

			foreach ($attribute->getSource()->getAllOptions() AS $option)
			{
				if ($option['label'] == $attrValue) return $option['value'];
			}

			return false;
		}

		/**
		 * @param $argumentAttribute
		 * @param $argumentValue
		 * @return bool
		 */
		protected function addAttributeValue($argumentAttribute, $argumentValue)
		{
			$attributeModel = \Mage::getModel('eav/entity_attribute');
	
			$attributeCode  = $attributeModel->getIdByCode('catalog_product', $argumentAttribute);
			$attribute   	= $attributeModel->load($attributeCode);
	
			if(! $this->attributeValueExists($argumentAttribute, $argumentValue))
			{
				$value['option'] = [
					$argumentValue,
					$argumentValue,
				];
	
				$result = [
					'value' => $value
				];
	
				$attribute->setData('option', $result)
						  ->save();
			}
	
			$attributeOptionModel = \Mage::getModel('eav/entity_attribute_source_table');

				$attributeOptionModel->setAttribute($attribute);
			
			foreach($attributeOptionModel->getAllOptions(false) as $option)
			{
				if ($option['label'] == $argumentValue) return $option['value'];
			}

			return false;
		}
		
		
		/**
		 * Generic notifications like the inbuilt FLASH method
		 * for Magento / Laravel / Slim
		 *
		 * @param $info
		 * @return $this
		 */
		protected function logInfo($info)
		{
			$this->info[] = $info;

				return $this;
		}

		/**
		 * Semi-structured error logging based on ZEND leveling
		 * constants.
		 *
		 * @param $error
		 * @param int $level
		 * @return $this
		 */
		protected function logError($error, $level = 8) // E_NOTICE
		{
			// set baseline for notice level
			$const = (isset($this->levels[$level]))
				? $this->levels[$level]
				: $this->levels[8];

			$this->errs[$const][] = $error;	// error baseline

			if (2048 !== $level) // we don't log E_STRICT's
			{
				$this->errs[$this->levels[6143]][] = $error;
			}
				return $this;
		}
    }
}