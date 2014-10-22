<?php

Namespace //global
{
    define('MAGENTO', realpath(dirname(dirname(__DIR__))) . '/Magento');
    require_once MAGENTO . '/app/Mage.php';
    \Mage::app('admin');
}

Namespace MageTools
{
    Class Products
    {
        protected $error  = [];

        private $products = [],
                $skuMap   = ['config' => [], 'simple' => []];

        public function fetch()
        {
            $this->products = $this->_parseCSV();
            $this->skuArray = $this->_getSkuByProducts();

            return $this;
        }

        protected function _getSkuByProducts()
        {
            if (! isset($this->products)) return [];

            foreach ($this->products as $product)
            {
                if (! preg_match('/^[0-9]/', $product['sku']))
                {
                    $this->error[] = [
                        'Reason' => 'Bad Sku',
                        $product,
                    ];

                    continue;
                }

                if (preg_match('/^[0-9]{9}$/', $product['sku']))
                {

                    print_r(strpos($product['sku'],6)); die;
                }

                $this->skuMap[$product['sku']] =  $product;
            }

            return $this;
        }


        public function getProductSkuArray()
        {
            return (isset($this->skuMap) && ! empty($this->skuMap))
                ? array_keys($this->skuMap)
                : [];
        }


        private function _parseCSV()
        {
            $array  = array_map ('str_getcsv', file (__DIR__ . '/output/simple-products.csv'));
            $header = array_shift ($array);

            array_walk ($array, function (&$row, $null, $header)
            {
                if (count ($header) !== count ($row))
                {
                    $tmp = [];
                    foreach ($header AS $inc => $key)
                        $tmp[$key] = (isset($row[$inc])) ? $row[$inc] : false;
                    file_put_contents (APP_DIR . '/logs/zr_errors.log', json_encode ($tmp, JSON_PRETTY_PRINT), LOCK_EX | FILE_APPEND);
                    $row = $tmp;
                }
                else $row = array_combine ($header, $row);

            }, $header);

            return $array;
        }
    }

    Class SkuImageUploader
    {
        private $_uploadDir;
        private $_imagePaths;

        public function setUploadDirectory($dir = null)
        {
            if (empty($dir))
            {
                if (isset($this->_uploadDir)) return $this;

                $dir = 'upload';
            }

            $this->_uploadDir = Mage::getBaseDir('media') . DIRECTORY_SEPARATOR . $dir;

            // mkdir($this->_uploadDir . DIRECTORY_SEPARATOR . "/loaded", 0770);

            return $this;
        }

        public function load()
        {
            $this->setUploadDirectory();

            // Match product images like 123456.jpg
            //$pattern = '[0-9][0-9][0-9][0-9][0-9][0-9].{jpg,gif,png}'; // numeric only
            $pattern = '*.{jpg,jpeg,gif,png}'; //[0-9]{5}-[A-Za-z0-9]{2,5}-[0-9]{3}

            chdir($this->_uploadDir);
            $this->_imagePaths = glob($pattern, GLOB_BRACE);

            return $this;
        }

        public function showFiles()
        {
            $this->load();

            echo "\n\nUnable to upload the following image files in {$this->_uploadDir}\n\n";
            print_r($this->_imagePaths);

            return $this;
        }

        private function _addImage($path)
        {
            $sku = (string) intval($path);
            try
            {
                echo "\nLoading SKU: {$sku} ... ";

                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

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

        private function _moveImage($path)
        {
            // rename($path, 'loaded' . DIRECTORY_SEPARATOR . $path);
            unlink($path);
        }

        public function import()
        {
            echo "\n";

            if (! isset($this->_imagePaths)) $this->load();

            foreach ($this->_imagePaths AS $path)
            {
                if ($this->_addImage($path)) $this->_moveImage($path);
            }

            return $this;
        }
    }
}

Namespace
{
    $f = New MageTools\Products();
    print_r($f->fetch()->getProductSkuArray());


//    $u = New MageTools\SkuImageUploader();
//    $u->import()->showFiles();
}
