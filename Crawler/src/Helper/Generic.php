<?php
Namespace
{
    if (! defined ('LOGDIR')) define ('LOGDIR', realpath(dirname(__DIR__) . '../logs'));
}

Namespace Helper
{
    Abstract Class Generic
    {
        protected $file         = '';
        private   $_err         = [],
                  $_log         = '';

        public function __construct()
        {
            $this->_log = LOGDIR . '/ZRErrors.log';
        }

        public function createFromCSV()
        {
            $content = array_map('str_getcsv', file($this->file));
            $headers = array_shift($content);

            array_walk($content, function (&$row, $key, $headers)
            {
                if (count($headers) !== count($row))
                {
                    $tmp = [];

                    foreach ($headers AS $inc => $key)
                    {
                        $tmp[$key] = (isset($row[$inc])) ? $row[$inc] : false;
                    }

                    // log
                    $this->_err[] = $tmp;
                    $row = $tmp; // includes odd shaped arrays instead of removing them...

                }

                else $row = array_combine($headers, $row);

            }, $headers);

            if (! empty($this->_err)) $this->_write();

            return array_filter($content);
        }

        public function setFile($file)
        {
            $this->file = $file;

                return $this;
        }

        private function _write()
        {
            file_put_contents($this->_err, json_encode($this->_err, JSON_PRETTY_PRINT), LOCK_EX | FILE_APPEND);

            $this->_err = '';

            return $this;
        }
    }
}