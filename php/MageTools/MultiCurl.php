<?php
Namespace // Assigning MAGE to a global namespace
{
    define('MAGENTO', realpath(dirname(dirname(dirname(__DIR__)))) . '/Magento');
    require_once MAGENTO . '/app/Mage.php';

    \Mage::app();
}

Namespace MageTools
{
    Class MultiCurl
    {
        protected $result = [];

        public function doRequests($data, $options = [])
        {
            $multi  = []; // array of curl handles
            $result = []; // data to be returned
        
            // multi handle
            $mh = curl_multi_init();
        
            // loop through $data and create curl handles
            // then add them to the multi-handle
            foreach ($data AS $id => $d) 
            {
        
                $multi[$id] = curl_init();
        
                $url = (is_array($d) && ! empty($d['url']))
                    ? $d['url']
                    : $d;

                curl_setopt($multi[$id], CURLOPT_URL,            $url);
                curl_setopt($multi[$id], CURLOPT_HEADER,         0);
                curl_setopt($multi[$id], CURLOPT_RETURNTRANSFER, 1);

                // post?
                if (is_array($d) && ! empty($d['post']))
                {
                    curl_setopt($multi[$id], CURLOPT_POST,       1);
                    curl_setopt($multi[$id], CURLOPT_POSTFIELDS, $d['post']);
                }
        
                // extra options?
                if (! empty($options)) curl_setopt_array($multi[$id], $options);

                curl_multi_add_handle($mh, $multi[$id]);
            }
        
            // execute the handles
            $running = null;
            do
            {
                if ($running <= 4) curl_multi_exec($mh, $running); //add handle

                usleep(100); //sleep

                echo "\nProcesses running: " . count($running);

            } while ($running > 0);
        
        
            // get content and remove handles
            foreach ($multi AS $id => $c)
            {
                $result[$id] = curl_multi_getcontent($c);
                curl_multi_remove_handle($mh, $c);
            }
        
            // all done
            curl_multi_close($mh);
        
            $this->result = $result;

            return $this;
        }

        public function getResults()
        {
            return (isset($this->result) && ! empty($this->result))
                ? $this->result
                : false;
        }
    }
}
