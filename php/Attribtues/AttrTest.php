<?php

/**
 * REF: http://m.blog.csdn.net/blog/terry_water/17450231
 * REF: http://www.webguys.de/magento/eav-attribute-setup/
 */

Namespace // Assigning MAGE to a global namespace
{
    define('MAGENTO', realpath(dirname(dirname(__DIR__))) . '/Magento');
    require_once MAGENTO . '/app/Mage.php';

    \Mage::app();
}

Namespace MageTools
{
    Class AttributeGenerator
    {
        private $tablePrefix    = '',
                $storeId        = 0;

        public function bootstrap()
        {
            /**
             * Error reporting
             */
            error_reporting(E_ALL | E_STRICT);

            $mageFilename    = MAGENTO . '/app/Mage.php';
            $maintenanceFile = 'maintenance.flag';

            #Varien_Profiler::enable();
            \Mage::setIsDeveloperMode(true);

            ini_set('display_errors', 1);
            umask(0);

            /* Store or website code */
            $mageRunCode = isset($_SERVER['MAGE_RUN_CODE']) ? $_SERVER['MAGE_RUN_CODE'] : '';
         
            /* Run store or run website */
            $mageRunType = isset($_SERVER['MAGE_RUN_TYPE']) ? $_SERVER['MAGE_RUN_TYPE'] : 'store';
         
            \Mage::init($mageRunCode, $mageRunType);

            $this->tablePrefix = \Mage::getConfig()
                                      ->getTablePrefix();

            /*
             * Wrong ?
             *
             * $this->storeId     = \Mage::app()
             *                          ->getStore()
             *                          ->getStoreId();
             */

            return $mageFilename;
        }

        protected function getOptionArrayForAttribute($attribute)
        {
            $read   = \Mage::getModel('core/resource')->getConnection('core_read');
            $select = $read->select()
                           ->from("{$this->tablePrefix}eav_attribute_option")
                           ->join("{$this->tablePrefix}eav_attribute_option_value","{$this->tablePrefix}eav_attribute_option.option_id={$this->tablePrefix}eav_attribute_option_value.option_id")
                           ->where('attribute_id=?',$attribute->getId())
                           ->where("store_id={$this->storeId}")
                           ->order("{$this->tablePrefix}eav_attribute_option_value.option_id");
         
            $query = $select->query();

            $values = [];

            foreach($query->fetchAll() AS $rows) $values[] = $rows['value'];

            return [
                'values' => $values,
            ];
        }
 
        public function getKeyLegend()
        {
            return [
                //catalog
                'frontend_input_renderer'       => 'input_renderer',
                'is_global'                     => 'global',
                'is_visible'                    => 'visible',
                'is_searchable'                 => 'searchable',
                'is_filterable'                 => 'filterable',
                'is_comparable'                 => 'comparable',
                'is_visible_on_front'           => 'visible_on_front',
                'is_wysiwyg_enabled'            => 'wysiwyg_enabled',
                'is_visible_in_advanced_search' => 'visible_in_advanced_search',
                'is_filterable_in_search'       => 'filterable_in_search',
                'is_used_for_promo_rules'       => 'used_for_promo_rules',
                'backend_model'                 => 'backend',
                'backend_type'                  => 'type',
                'backend_table'                 => 'table',
                'frontend_model'                => 'frontend',
                'frontend_input'                => 'input',
                'frontend_label'                => 'label',
                'source_model'                  => 'source',
                'is_required'                   => 'required',
                'is_user_defined'               => 'user_defined',
                'default_value'                 => 'default',
                'is_unique'                     => 'unique',
            ];
        }
         
        public function getMigrationScriptForAttribute($code)
        {
            //load the existing attribute model
            $m = \Mage::getModel('catalog/resource_eav_attribute')
                      ->loadByCode('catalog_product',$code);

            //get a map of "real attribute properties to properties used in setup resource array
            $setupLegendKey = $this->getKeyLegend();
         
            //swap keys from above
            $data       = $m->getData();
            $keysLegend = array_keys($setupLegendKey);
            $newData    = [];

            foreach ($data AS $key => $value)
            {
                if (in_array($key, $keysLegend)) $key = $setupLegendKey[$key];

                $newData[$key] = $value;
            }

            //unset items from model that we don't need and would be discarded by
            //resource script anyways

            if (! isset($newData['attribute_code']))
            {
                echo "//WARNING, attribute_code not found. " . "\n";
                return false;
            }

            $attrCode = $newData['attribute_code'];

            unset(
                $newData['attribute_id'], 
                $newData['attribute_code'], 
                $newData['entity_type_id']
            );

            //chuck a few warnings out there for things that were a little murky
            if($newData['attribute_model'])
            {
                echo "//WARNING, value detected in attribute_model.  We've never seen a value there before and this script doesn't handle it.  Caution, etc. " . "\n";
            }
         
            if($newData['is_used_for_price_rules'])
            {
                echo "//WARNING, non false value detected in is_used_for_price_rules.  The setup resource migration scripts may not support this (per 1.7.0.1)" . "\n";
            }

            // require 'sort.php';
            $newData['option'] = (empty($custom))
                ? $this->getOptionArrayForAttribute($m) //load values for attributes (if any exist)
                : $custom;


            //get text for script
            $array = var_export($newData, true); // push as array
         
            $script = "\n\n<?php
if(! (\$this instanceof Mage_Catalog_Model_Resource_Setup))
{
    Throw New \\Exception(\"Resource Class needs to inherit from \" .
    \"Mage_Catalog_Model_Resource_Setup for this to work\");
}
                         
\$attr = $array;
\$this->addAttribute('catalog_product','$attrCode',\$attr);\n\n";

            return $script;
        }
         
        public function usage()
        {
            echo "USAGE: magento-create-setup.php attribute_code" . "\n";
        }
         
        public function main($argv)
        {
            $script = array_shift($argv);
            $code   = array_shift($argv);

            if(! $code)
            {
                $this->usage();
                exit;
            }

            $script = $this->getMigrationScriptForAttribute($code);

            echo $script;
        }
    }
}

Namespace
{
    USE \MageTools\AttributeGenerator AS Generator;

    // phpstorm complains
    $argv = (isset($argv) && ! empty($argv))
        ? $argv
        : [];

    $attrGenerator = New Generator();

    $attrGenerator->bootstrap();
    $attrGenerator->main($argv);
}
