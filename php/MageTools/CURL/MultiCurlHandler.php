<?php
Namespace // Assigning MAGE to a global namespace
{
    define('MAGENTO', realpath(dirname(dirname(dirname(__DIR__)))) . '/Magento');
    require_once MAGENTO . '/app/Mage.php';

    \Mage::app();
}

Namespace MagenTools\CURL
{
    Class MultiCurlRequests
    {
        /**
         * Class that represent a single curl request
         */
        public  $url        = false,
                $method     = 'GET',
                $postData   = null,
                $headers    = null,
                $options    = null;

        /**
         * @param $url
         * @param string $method
         * @param null $postData
         * @param null $headers
         * @param array $options
         */
        public function __construct($url, $method = "GET", $postData = null, $headers = null, array $options = [])
        {
            $this->url      = $url;
            $this->method   = $method;
            $this->postData = $postData;
            $this->headers  = $headers;
            $this->options  = $options;
        }

        /**
         * @return void
         */
        public function __destruct()
        {
            unset(
                $this->url,
                $this->method,
                $this->postData,
                $this->headers,
                $this->options
            );
        }
    }

    /**
     * MultiCurl custom exception
     */
    Class MultiCurlException Extends \Exception
    {
    }

    /**
     * Class that holds a rolling queue of curl requests.
     *
     * @throws MultiCurlException
     */
    Class MultiCurl
    {
        /**
         * @var int
         *
         * Window size is the max number of simultaneous connections allowed.
         *
         * REMEMBER TO RESPECT THE SERVERS:
         * Sending too many requests at one time can easily be perceived
         * as a DOS attack. Increase this scrollSize if you are making requests
         * to multiple servers or have permission from the receving server admins.
         */
        private $scrollSize = 5;

        /**
         * @var float
         *
         * Timeout is the timeout used for curl_multi_select.
         */
        private $timeout = 10;

        /**
         * @var string|array
         *
         * Callback function to be applied to each result.
         */
        private $callback;

        /**
         * @var array
         *
         * Set your base options that you want to be used with EVERY request.
         */
        protected $options = [
            CURLOPT_SSL_VERIFYPEER  => 0,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_CONNECTTIMEOUT  => 30,
            CURLOPT_TIMEOUT         => 30
        ];

        /**
         * @var array
         */
        private $headers    = [],

        /**
         * @var Request[]
         *
         * The request queue
         */
            $requests       = [],

        /**
         * @var RequestMap[]
         *
         * Maps handles to request indexes
         */
            $requestMap     = [];


        /**
         * Callback function to be applied to each result.
         *
         * Can be specified as 'my_callback_function'
         * or array($object, 'my_callback_method').
         *
         * Function should take three parameters: $response, $info, $request.
         * $response is response body, $info is additional curl info.
         * $request is the original request
         *
         * @param null $callback
         */
        public function __construct($callback = null)
        {
            $this->callback = $callback;
        }

        /**
         * @param string $name
         * @return mixed
         */
        public function __get($name)
        {
            return (isset($this->{$name})) ? $this->{$name} : null;
        }

        /**
         * @param string $name
         * @param mixed $value
         * @return $this
         */
        public function __set($name, $value)
        {
            // append the base options & headers
            $this->{$name} = ($name == "options" || $name == "headers")
                ? $value + $this->{$name}
                : $value;

            return $this;
        }

        /**
         * Add a request to the request queue
         *
         * @param MultiCurlRequests $request
         * @return bool
         */
        public function add(MultiCurlRequests $request)
        {
            $this->requests[] = $request;

                return $this;
        }

        /**
         * Create new Request and add it to the request queue
         *
         * @param  string $url
         * @param  string $method
         * @param  null $postData
         * @param  null $headers
         * @param  array $options
         * @return $this
         */
        public function request($url, $method = "GET", $postData = null, $headers = null, array $options = [])
        {
            $this->requests[] = New MultiCurlRequests($url, $method, $postData, $headers, $options);

                return $this;
        }

        /**
         * Perform GET request
         *
         * @param  string $url
         * @param  null $headers
         * @param  array $options
         * @return bool
         */
        public function get($url, $headers = null, array $options = [])
        {
            return $this->request($url, "GET", null, $headers, $options);
        }

        /**
         * Perform POST request
         *
         * @param  string $url
         * @param  null $postData
         * @param  null $headers
         * @param  array $options
         * @return bool
         */
        public function post($url, $postData = null, $headers = null, array $options = [])
        {
            return $this->request($url, "POST", $postData, $headers, $options);
        }

        /**
         * Execute processing
         *
         * @param int $scrollSize Max number of simultaneous connections
         * @return string|bool
         */
        public function execute($scrollSize = null)
        {
            // rolling curl window must always be greater than 1
            return (1 !== count($this->requests))
                ? $this->rollingCurl($scrollSize) // start the multi-curling. scrollSize is the max number of simultaneous connections
                : $this->singleCurl();

        }

        /**
         * Performs a single curl request
         *
         * @access private
         * @return string
         */
        private function singleCurl()
        {
            $ch = curl_init();
            $request = array_shift($this->requests);
            $options = $this->getOptions($request);

                curl_setopt_array($ch, $options);

            $output = curl_exec($ch);
            $info   = curl_getinfo($ch);

            // it's not necessary to set a callback for one-off requests
            if ($this->callback) 
            {
                $callback = $this->callback;
                if (is_callable($this->callback)) call_user_func($callback, $output, $info, $request);

            } 
            
            else 
            {
                return $output;
            }

            return true; // will never be hit if is_callable
        }

        /**
         * Performs multiple curl requests
         *
         * @access private
         * @throws MultiCurlException
         * @param int $scrollSize Max number of simultaneous connections
         * @return bool
         */
        private function rollingCurl($scrollSize = null)
        {
            if ($scrollSize)
                $this->scrollSize = $scrollSize;

            // make sure the rolling window isn't greater than the # of urls
            if (count($this->requests) < $this->scrollSize)

                $this->scrollSize = count($this->requests);

            if ($this->scrollSize < 2)

                Throw New MultiCurlException("Window size must be greater than 1");
            
            $master = curl_multi_init();

            // start the first batch of requests
            for ($i = 0; $i < $this->scrollSize; $i++) 
            {
                $ch = curl_init();
                $options = $this->getOptions($this->requests[$i]);

                curl_setopt_array($ch, $options);
                curl_multi_add_handle($master, $ch);

                // Add to our request Maps
                $key = (string)$ch;
                $this->requestMap[$key] = $i;
            }

            do 
            {
                while (CURLM_CALL_MULTI_PERFORM == ($execRun = curl_multi_exec($master, $running)));

                if (CURLM_OK != $execRun)

                    break;

                // a request was just completed -- find out which one
                while ($done = curl_multi_info_read($master))
                {
                    // get the info and content returned on the request
                    $info   = curl_getinfo($done['handle']);
                    $output = curl_multi_getcontent($done['handle']);

                    // send the return values to the callback function.
                    $callback = $this->callback;
                    if (is_callable($callback))
                    {
                        $key     = (string)$done['handle'];
                        $request = $this->requests[$this->requestMap[$key]];

                            unset($this->requestMap[$key]);

                        call_user_func($callback, $output, $info, $request);
                    }

                    // start a new request (it's important to do this before removing the old one)
                    if ($i < count($this->requests) && isset($this->requests[$i]) && $i < count($this->requests))
                    {
                        $ch = curl_init();
                        $options = $this->getOptions($this->requests[$i]);

                            curl_setopt_array($ch, $options);

                        curl_multi_add_handle($master, $ch);

                        // Add to our request Maps
                        $key = (string)$ch;
                        $this->requestMap[$key] = $i;
                        $i++;
                    }

                    // remove the curl handle that just completed
                    curl_multi_remove_handle($master, $done['handle']);

                }

                // Block for data in / output; error handling is done by curl_multi_exec
                if ($running)

                    curl_multi_select($master, $this->timeout);

            } while ($running);

                curl_multi_close($master);

            return $this;
        }


        /**
         * Helper function to set up a new request by setting the appropriate options
         *
         * @access private
         * @param MultiCurlRequests $request
         * @return array|mixed|null
         */
        private function getOptions(MultiCurlRequests $request)
        {
            // options for this entire curl object
            $options = $this->__get('options');

            if ('Off' == ini_get('safe_mode') || ! ini_get('safe_mode'))
            {
                $options[CURLOPT_FOLLOWLOCATION] = 1;
                $options[CURLOPT_MAXREDIRS]      = 5;
            }

            $headers = $this->__get('headers');

            // append custom options for this specific request
            if ($request->options)

                $options = $request->options + $options;

            // set the request URL
            $options[CURLOPT_URL] = $request->url;

            // posting data w/ this request?
            if ($request->postData)
            {
                $options[CURLOPT_POST]       = 1;
                $options[CURLOPT_POSTFIELDS] = $request->postData;
            }

            if ($headers)
            {
                $options[CURLOPT_HEADER]     = 0;
                $options[CURLOPT_HTTPHEADER] = $headers;
            }

            return $options;
        }

        /**
         * @return void
         */
        public function __destruct()
        {
            unset(
                $this->scrollSize,
                $this->callback,
                $this->options,
                $this->headers,
                $this->requests
            );
        }
    }
}