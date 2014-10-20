<?php
Namespace // insert into a generic namespace
{
    /**
     * Usage: php shutdown.php
     *
     * Add a SIG anytime such as CTRL + C
     *
     */

    ignore_user_abort(0);

    $shutdown = function ()
    {
        echo "Shutdown function\n";
    };

    register_shutdown_function($shutdown);

}

Namespace Registered
{
    Class Shutdown
    {
        public function __construct()
        {
            echo __METHOD__ . "\n";
        }

        public function __destruct()
        {
            echo __METHOD__ . "\n";
        }
    }
}

Namespace
{
    New Registered\Shutdown(); // blind function

    declare(ticks = 1);
    function sig_handler($signo)
    {
        switch ($signo)
        {
            case SIGTERM:
                echo "\nSIG: handle shutdown task\n";
                exit;

            case SIGHUP:
                echo "\nSIG: handle restart task\n";
                exit;

            case SIGUSR1:
                echo "\nSIG: caught SIGUSR1 task\n";
                exit;

            case SIGINT:
                echo "\nSIG: caught CTRL+C\n";
                exit;

            default:
                echo "\nSIG: weird signal...\n";
                exit;
        }
    }

    echo "Generating signal handler....\n";

        pcntl_signal(SIGTERM, 'sig_handler');
        pcntl_signal(SIGHUP,  'sig_handler');
        pcntl_signal(SIGUSR1, 'sig_handler');
        pcntl_signal(SIGINT,  'sig_handler');

    while(1)
    {
        echo "."; sleep(1);
    }

    echo "Generating signal SIGTERM to self...\n";
}
