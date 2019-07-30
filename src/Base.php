<?php
/**
 * BetaData licence
 *
 * @copyright  Copyright 2012 BetaData, Inc.
 * @license  http://wwww.betadata.io/ BetaData License
 */

/**
 * This a BetaData_Base class which all classes extend from to provide some very basic
 * debugging and logging functionality. It also serves to persist $_options across the library.
 */
class BetaData_Base
{
    /**
     * Default options that can be overridden via the $options constructor arg
     * @var array
     */
    private $_defaults = array(
        'max_batch_size'    => 50, // the max batch size Mixpanel will accept is 50,
        'max_queue_size'    => 1000, // the max num of items to hold in memory before flushing
        'debug'             => true, // enable/disable debug mode
        'consumer'          => 'curl', // which consumer to use
        'consumers'         => array(
            'file'   => 'BetaData_Consumer_FileConsumer',
            'curl'   => 'BetaData_Consumer_CurlConsumer',
            'socket' => 'BetaData_Consumer_SocketConsumer',
            'kafka'  => 'BetaData_Consumer_KafkaConsumer',
            'redis'  => 'BetaData_Consumer_RedisConsumer',
        ),
        'url'               => 'http://backend.yiiplus.local/tracks',
        'error_callback'    => null, // callback to use on consumption failures
        'algo'              => 'sha256',
        'max_attempts'      => 10,
    );

    /**
     * An array of options to be used by the library.
     * @var array
     */
    protected $_options = array();

    /**
     * Construct a new object and merge custom options with defaults
     * @param array $options
     */
    protected function __construct($options = array())
    {
        $options = array_merge($this->_defaults, $options);
        $this->_options = $options;
    }

    /**
     * Log a message to PHP's error log
     * @param $msg
     */
    protected function _log($msg)
    {
        $arr = debug_backtrace();
        $class = $arr[1]['class'];
        $line = $arr[0]['line'];
        error_log ( "[ ${class} - line ${line} ] ${msg}" );
    }

    /**
     * Returns true if in debug mode, false if in production mode
     * @return bool
     */
    protected function _debug()
    {
        return array_key_exists("debug", $this->_options) && $this->_options["debug"] == true;
    }

    /**
     * Return millisecond now
     * @return float millisecond
     */
    protected function _millisecond()
    {
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    /**
     * Return sdk detail
     * @return string sdk detail description
     */
    protected function _sdk_detail()
    {
        try {
            throw new \Exception('');
        } catch (\Exception $e) {
            $trace = $e->getTrace();
            if (count($trace) == 2) {
                // 脚本内直接调用
                $file = $trace[1]['file'];
                $line = $trace[1]['line'];

                return "in ${file} at line ${line}.";
            } else if (count($trace) > 2) {
                if (isset($trace[2]['class'])) {
                    // 类成员函数内调用
                    $class = $trace[2]['class'];
                } else {
                    // 全局函数内调用
                    $class = '';
                }

                // 此处使用 [1] 非笔误，trace 信息就是如此
                $file = $trace[1]['file'];
                $line = $trace[1]['line'];
                $function = $trace[2]['function'];
                $type = $trace[2]['type'];

                return "in ${file} - ${class}{$type}${function}(...) at line ${line}.";
            }
        }
    }
}