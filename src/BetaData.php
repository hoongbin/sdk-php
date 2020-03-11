<?php
/**
 * BetaData licence
 *
 * @copyright  Copyright 2019 BetaData, Inc.
 * @license  http://wwww.betadata.io/ BetaData License
 */

define('BETADATE_SDK', 'php');
define('BETADATE_SDK_VERSION', '0.1.0');

require_once(dirname(__FILE__) . '/Base.php');
require_once(dirname(__FILE__) . '/Exception/IllegalDataException.php');
require_once(dirname(__FILE__) . '/Exception/NetworkException.php');
require_once(dirname(__FILE__) . '/Consumer/FileConsumer.php');
require_once(dirname(__FILE__) . '/Consumer/CurlConsumer.php');
require_once(dirname(__FILE__) . '/Consumer/SocketConsumer.php');

/**
 * BetaData Analytics SDK
 */
class BetaData extends BetaData_Base
{
    /**
     * Instances' list of the betadata class (for singleton use, splitted by token)
     * @var betadata
     */
    private static $_instances = array();

    /**
     * An array of properties to attach to every tracked
     * @var array
     */
    private $_super_properties = array('event_properties' => array(), 'user_properties' => array());

    /**
     * @var array a queue to hold messages in memory before flushing in batches
     */
    private $_queue = array();

    /**
     * @var Consumer_AbstractConsumer the consumer to use when flushing messages
     */
    private $_consumer = null;

    /**
     * Instantiates a new betadata instance.
     * @param $app_id
     * @param $token
     * @param $project
     * @param array $options
     */
    public function __construct($app_id, $token, $project, $options = array())
    {
        // Inject options information
        $options = array_merge(array('app_id' => $app_id, 'token' => $token, 'project' => $project), $options);
        parent::__construct($options);

        if ($this->_debug()) {
            $this->_log('using options: ' . json_encode($this->_options));
        }

        // instantiate the chosen consumer
        $this->_consumer = $this->_get_consumer();
    }

    /**
     * Returns a singleton instance of betadata
     * @param $appid
     * @param $token
     * @param array $options
     * @return betadata
     */
    public static function getInstance($appid, $token,  $project, $options = array())
    {
        if(!isset(self::$_instances[$appid])) {
            self::$_instances[$appid] = new BetaData($appid, $token, $project, $options);
        }
        return self::$_instances[$appid];
    }

    /**
     * Given a strategy type, return a new PersistenceStrategy object
     * @return ConsumerStrategies_AbstractConsumer
     */
    private function _get_consumer()
    {
        $key = $this->_options['consumer'];
        $Strategy = $this->_options['consumers'][$key];
        if ($this->_debug()) {
            $this->_log("using consumer: ${key} -> ${Strategy}");
        }
        return new $Strategy($this->_options);
    }

    /**
     * Iterate the queue and write in batches using the instantiated Consumer Strategy
     * @return bool whether or not the flush was successful
     */
    public function flush()
    {
        $queue_size = count($this->_queue);
        $succeeded = true;
        $num_threads = $this->_consumer->getNumThreads();

        if ($this->_debug()) {
            $this->_log("flush called - queue size: ${queue_size}");
        }

        while($queue_size > 0 && $succeeded) {
            $batch_size = min(array($queue_size, $this->_options['max_batch_size']*$num_threads));
            $batch = array_splice($this->_queue, 0, $batch_size);
            $succeeded = $this->_consumer->persist($batch);

            if (!$succeeded) {
                if ($this->_debug()) {
                    $this->_log('batch consumption failed!');
                }
                $this->_queue = array_merge($batch, $this->_queue);

                if ($this->_debug()) {
                    $this->_log("added batch back to queue, queue size is now ${queue_size}");
                }
            }

            $queue_size = count($this->_queue);
            if ($this->_debug()) {
                $this->_log("batch of $batch_size consumed, queue size is now ${queue_size}");
            }
        }

        return $succeeded;
    }
    
    /**
     * Track an event defined by $event_name associated with metadata defined by $event_properties and $user_properties
     * @param string  $event_name       事件名称
     * @param array   $event_properties 事件属性
     * @param array   $user_properties  用户属性
     * @param boolean $is_back           是否后台事件
     */
    public function track($event_name, $event_properties = array(), $user_properties = array(), $is_back = true)
    {
        if (!is_string($event_name)) {
            throw new BetaData_Exception_IllegalDataException('event name must be a str.');
        }
        $this->_assert($event_name);

        $sdk = array(
            '_sdk' => BETADATE_SDK,
            '_sdk_version' => BETADATE_SDK_VERSION,
        );

        $back = array(
            'is_back' => $is_back
        );

        $message['_event'] = $event_name;
        $message['_time']  = $this->_millisecond();
        $message['event_properties'] = array_merge($this->_super_properties['event_properties'], $sdk, $back, $event_properties);
        $message['user_properties']  = array_merge($this->_super_properties['user_properties'], $user_properties);
        $this->_assert_properties($message['event_properties']);
        $this->_assert_properties($message['user_properties']);

        if(empty($message['user_properties'])) {
            unset($message['user_properties']);
        }
        array_push($this->_queue, json_encode($message));

        if ($this->_debug()) {
            $this->_log('queued message: '.json_encode($message));
        }

        // force a flush if we've reached our threshold
        if (count($this->_queue) >= $this->_options['max_queue_size']) {
            $this->flush();
        }
    }

    /**
     * Determine if the Key meets the requirements
     *
     * @param $key
     * @throws BetaData_Exception_IllegalDataException
     */
    private function _assert($key)
    {
        $name_pattern = "/^((?!^^event_properties$|^user_properties$|^id$|^second_id$|^users$|^events$|^user$|^event$|^time$|^date$|^datetime$)[a-zA-Z_$][a-zA-Z\\d_$]{0,99})$/i";
        if (!preg_match($name_pattern, $key)) {
            throw new BetaData_Exception_IllegalDataException('key must be a valid variable key.');
        }
    }

    /**
     * Determine if the properties meets the requirements
     *
     * @param array $properties
     * @throws BetaData_Exception_IllegalDataException
     */
    private function _assert_properties($properties = array())
    {
        if (!$properties) {
            return;
        }

        foreach ($properties as $key => $value) {
            if (!is_string($key)) {
                throw new BetaData_Exception_IllegalDataException("property key must be a str. [key=${key}]");
            }
            if (strlen($key) > 255) {
                throw new BetaData_Exception_IllegalDataException("the max length of property key is 256. [key=${key}]");
            }
            $this->_assert($key);
        }
    }

    /**
     * Flush the queue when we destruct the client with retries
     */
    public function __destruct()
    {
        $attempts = 0;
        $max_attempts = max(1, intval($this->_options['max_attempts']));
        $success = false;
        while (!$success && $attempts < $max_attempts) {
            if ($this->_debug()) {
                $this->_log('destruct flush attempt #'.($attempts+1));
            }
            $success = $this->flush();
            $attempts++;
        }
    }
}
