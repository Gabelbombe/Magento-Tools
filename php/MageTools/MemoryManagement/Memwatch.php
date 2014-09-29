<?php
Namespace // Assigning MAGE to a global namespace
{
    define('MAGENTO', realpath(dirname(dirname(dirname(__DIR__)))) . '/Magento');
    require_once MAGENTO . '/app/Mage.php';

    \Mage::app();
}

Namespace MageTools\MemoryManagement {
    ini_set('display_startup_errors', 1);
    ini_set('display_errors', 1);
    error_reporting(-1);

    Class Memwatch
    {
        protected $stat = [
            "HIGHEST_MEMORY"    => 0,
            "HIGHEST_DIFF"      => 0,
            "PERCENTAGE_BREAK"  => 0,
            "AVERAGE"           => [],
            "LOOPS"             => 0
        ];

        private $peakPoint      = 90; //90%

        private $available      = null,
                $memoryStart    = null,
                $memoryDiff     = 0;

        public function __construct()
        {
            $this->available = filter_var(ini_get("memory_limit"), FILTER_SANITIZE_NUMBER_INT) * 1024 * 1024;
            $this->watcher   = $this->stat;
        }

        public function watch()
        {
            $this->start();

            $data = "";
            $i = 0;

            while (1) //true
            {
                $i ++;

                // Get used memory
                $memoryUsed = memory_get_peak_usage(false);

                // Get Diffrence
                $this->memoryDiff = $memoryUsed - $this->memoryStart;

                // Start memory Usage again
                $this->memoryStart();

                // Gather some stats
                $this->stat['HIGHEST_MEMORY'] = ($memoryUsed > $this->stat['HIGHEST_MEMORY'])
                    ? $memoryUsed
                    : $this->stat['HIGHEST_MEMORY'];

                $this->stat['HIGHEST_DIFF'] = ($this->memoryDiff > $this->stat['HIGHEST_DIFF'])
                    ? $this->memoryDiff
                    : $this->stat['HIGHEST_DIFF'];

                $this->stat['AVERAGE'][] = $this->memoryDiff;
                $this->stat['LOOPS'] ++;

                $percentage = (($memoryUsed + $this->stat['HIGHEST_DIFF']) / $this->available) * 100;

                // Stop your scipt
                if ($percentage > $this->peakPoint)
                {

                    print(sprintf("Stopped at: %0.2f", $percentage) . "%\n");

                    $this->stat['AVERAGE'] = array_sum($this->stat['AVERAGE']) / count($this->stat['AVERAGE']);

                        $this->stat = array_map(function ($v)
                        {
                            return sprintf("%0.2f", $v / (1024 * 1024));
                        }, $this->stat);

                    $stat['LOOPS'] = $i;
                    $stat['PERCENTAGE_BREAK'] = sprintf("%0.2f", $percentage) . "%";
                    echo json_encode($this->stat, 128);
                    break;
                }

                $data .= str_repeat(' ', 1024 * 25); // 1kb every time
            }
        }

        public function start()
        {
            $this->$memoryStart = memory_get_peak_usage(false);

                return $this;
        }
    }
}