<?php

namespace Midnite81\Loggable;

use Carbon\Carbon;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class BaseLogger
{
    /** @var string */
    protected $directoryPath;

    /** @var string */
    protected $filePrefix;

    /** @var \Monolog\Logger */
    protected $logger;

    /** @var StreamHandler */
    protected $streamHandler;

    /** @var Formatter */
    protected $formatter;

    /**
     * Get Directory Path
     *
     * @return string
     */

    /**
     * Return the path to where the log file will be stored
     *
     * @return string
     */
    abstract public function getDirectoryPath();

    /**
     * Returns the prefix to the log file.
     * All files have the date appended to them
     *
     * @return string
     */
    abstract public function getFilePrefix();

    /**
     * Returns the extension of the log file.
     * If null, will default to 'log'
     *
     * @return string
     */
    abstract public function getExtension();

    /**
     * Return the name for the logger
     *
     * @return string
     */
    abstract public function getLoggerName();

    /**
     * Get the maximum number of days a log file can exist for
     *
     * @return int
     */
    public function getMaxLogDays() {
        return 5;
    }

    /**
     * Return the logger
     * (This function can be overwritten on your child class)
     *
     * @return Logger
     * @throws \Exception
     */
    public function getLogger() {
        $directory = $this->getDirectoryPath();
        $file = $this->getFilePrefix() . '-' . date('Y-m-d') . '.' . $this->getExtension();
        $filename = $directory . DIRECTORY_SEPARATOR . $file;

        $log = new Logger($this->getLoggerName());
        $formatter = new LineFormatter(null, null, false, true);
        $stream = new StreamHandler($filename);
        $stream->setFormatter($formatter);
        $log->pushHandler($stream);

        return $log;
    }


    /**
     * Clear older files so we don't fill the server with log files
     */
    public function clearOldLogs()
    {
        $files = glob($this->getDirectoryPath() . DIRECTORY_SEPARATOR . '*.' . $this->getExtension());
        $allowableMaxAge = Carbon::now()->startOfDay()->subDay($this->getMaxLogDays());

        if ($files) {
            foreach ($files as $file) {
                $fileDate = str_replace([$this->getFilePrefix() . '-', '.' . $this->getExtension()], "", basename($file));
                if (Carbon::createFromFormat('Y-m-d', $fileDate)->lessThan($allowableMaxAge)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Call Static Magic Method
     *
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($name, $arguments)
    {
        $class = new static;

        $log = $class->getLogger();

        return call_user_func_array([$log, $name], $arguments);
    }

    /**
     * Call Magic Method
     *
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $log = $this->getLogger();

        return call_user_func_array([$log, $name], $arguments);
    }
}