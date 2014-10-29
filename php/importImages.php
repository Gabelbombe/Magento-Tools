<?php

Namespace //global
{
    define('MAGENTO', realpath(dirname(dirname(__DIR__))) . '/Magento');
    require_once MAGENTO . '/app/Mage.php';
    \Mage::app('admin');
}

Namespace MageTools
{
    Class ProductsImageUploader
    {
        const IMGDIR = '/var/www/httpdocs/images/products/detail';
        const CATDIR = '/var/www/httpdocs/images/products/category';
        const OLDDIR = '/var/www/httpdocs/images/products/detail-old';
        const WHTDIR = '/var/www/httpdocs/images/products/category-white';

        public $products = [],
               $errors   = [];

        private $_uploadDir;
        private $_imagePaths;

        public function __construct()
        {
            // ....
        }

        public function fetch()
        {
            echo "Starting\n";

            $this->_getProductList();

            return $this;
        }


        public function setUploadDirectory($dir = null)
        {
            if (empty($dir))
            {
                if (isset($this->_uploadDir)) return $this;

                $dir = 'upload';
            }

            $this->_uploadDir = \Mage::getBaseDir('media') . DS . $dir;

            if (! is_dir($this->_uploadDir))
            {
                mkdir($this->_uploadDir, 0700);
                mkdir($this->_uploadDir . DS . "/loaded", 0770);
            }

            return $this;
        }

        public function load(array $product)
        {
            $this->setUploadDirectory();

            chdir($this->_uploadDir);
            $this->_imagePaths = $product['Images'];

            return $this;
        }

        public function showFiles(array $product)
        {
            $this->load($product);

            echo "\n\nUnable to upload the following image files in {$this->_uploadDir}\n\n";
            print_r($this->_imagePaths);

            return $this;
        }

        private function _addImage($path, $sku)
        {
            try
            {
                echo "Loading SKU:  {$sku} ...";

                $product = \Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

                // if the product exists, attempt to add the image to it for all three items
                if (false !== strpos(get_class($product), 'Catalog_Model_Product') && 0 < $product->getId())
                {
                    $product->addImageToMediaGallery($path, [
                        'image',
                        'small_image',
                        'thumbnail'
                    ], false, false);

                    $product->save();
                    echo " ok\n";
                    return 1;
                }
            }

            catch (\Exception $e)
            {
                echo "Exception thrown for {$sku}: " . $e->getMessage() . "\r\n";
            }

            echo " (FAILED) \n";
            return 0;
        }

        public function import()
        {
            echo "\n";

            foreach ($this->products AS $type => $array)
            {
                foreach ($array AS $sku => $product)
                {
                    $this->load ($product);

                    if (! empty($this->_imagePaths))
                    {
                        echo "Trying {$product['Name']}\n";

                        foreach ($this->_imagePaths AS $path) $this->_addImage  ($path, $sku);
                    }

                    else
                    {
                        echo "{$product['Name']} missing images....\n";
                    }
                }
            }

            return $this;
        }

        public function getProductSkuArray()
        {
            return (isset($this->skuMap) && ! empty($this->skuMap))
                ? array_keys($this->skuMap)
                : [];
        }


        private function _shear(array $array)
        {
            $whitelist = [];
            $paths     = [];

            foreach ($array AS $path)
            {
                $name = basename($path);

                if (! in_array($name, $whitelist))
                {
                    $whitelist[] = $name;
                    $paths[]     = $path;
                }
            }

            return $paths;
        }


        protected function _getProductList()
        {
            $_productCollection = \Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('name');

            foreach ($_productCollection AS $product)
            {
                if (-1 !== preg_match("/^([0-9]{5})([0-9]{3})([0-9]+)$/", $sku = $product->getSku(), $matches))
                {
                    if (preg_match('/^[0-9]{5}$/', $sku))
                    {
                        $type = 'config';

                        $cat = self::CATDIR . "/{$sku}-*";
                        $img = self::IMGDIR . "/{$sku}-*";
                        $old = self::OLDDIR . "/{$sku}-*";
                        $wht = self::WHTDIR . "/{$sku}-*";
                    }

                    else
                    {
                        $type = 'simple';

                        $cat = self::CATDIR . "/{$matches[1]}-main-{$matches[2]}*";
                        $img = self::IMGDIR . "/{$matches[1]}-main-{$matches[2]}*";
                        $old = self::OLDDIR . "/{$matches[1]}-main-{$matches[2]}*";
                        $wht = self::WHTDIR . "/{$matches[1]}-main-{$matches[2]}*";
                    }

                    $images = $this->_shear(array_merge(glob($cat), glob($img), glob($old), glob($wht)));

                    if (empty($images))
                    {
                        $this->errors[] = [
                            'Name'  => $product->getName(),
                            'Path'  => [
                                $cat,
                                $img,
                                $old,
                                $wht,
                            ],
                        ];
                    }

                    $this->products[$type][$sku] = [
                        'Name'      => $product->getName(),
                        'Refid'     => (! empty($matches[1]) ? $matches[1] : $sku),
                        'Color'     => (! empty($matches[2]) ? $matches[2] : null),
                        'Size'      => (! empty($matches[2]) ? $matches[2] : null),
                        'Images'    => $images,
                    ];

                }
                else
                {
                    Throw New \RuntimeException("bad SKU");
                }
            }

            return $this;
        }


        public function getErrors()
        {
            print_r($this->errors);
        }
    }
}

Namespace
{
    $f = New MageTools\ProductsImageUploader();
    $f->fetch()->import()->getErrors();
}
