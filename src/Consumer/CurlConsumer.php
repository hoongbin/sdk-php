<?php
/**
 * BetaData licence
 *
 * @copyright  Copyright 2019 BetaData, Inc.
 * @license  http://wwww.betadata.io/ BetaData License
 */

require_once(dirname(__FILE__) . "/AbstractConsumer.php");
require_once(dirname(__FILE__) . "/../Exception/NetworkException.php");

/**
 * Consumes messages and sends them to a host/endpoint using cURL
 */
class BetaData_Consumer_CurlConsumer extends BetaData_Consumer_AbstractConsumer {

    /**
     * @var string the url to connect to (e.g. http://api.betadata.io/track)
     */
    protected $_url;

    /**
     * @var int connect_timeout The number of seconds to wait while trying to connect. Default is 5 seconds.
     */
    protected $_connect_timeout;

    /**
     * @var int timeout The maximum number of seconds to allow cURL call to execute. Default is 30 seconds.
     */
    protected $_timeout;

    /**
     * @var int number of cURL requests to run in parallel. 1 by default
     */
    protected $_num_threads;

    /**
     * Creates a new CurlConsumer and assigns properties from the $options array
     * @param array $options
     * @throws BetaData_Exception_NetworkException
     */
    function __construct($options) {
        parent::__construct($options);

        $this->_url = $options['url'];
        $this->_connect_timeout = array_key_exists('connect_timeout', $options) ? $options['connect_timeout'] : 5;
        $this->_timeout = array_key_exists('timeout', $options) ? $options['timeout'] : 30;
        $this->_num_threads = array_key_exists('num_threads', $options) ? max(1, intval($options['num_threads'])) : 1;
    }

    /**
     * Write to the given url using either a forked cURL process or using PHP's cURL extension
     * @param array $data
     * @return bool
     */
    public function persist($data) {
        if (count($data) > 0) {
            if ($this->_debug()) {
                $this->_log('url : ' . $this->_url);
            }

            $mh = curl_multi_init();
            $chs = array();

            $data_size = ceil(count($data) / $this->_num_threads);
            for ($i=0; $i<$this->_num_threads && !empty($data); $i++) {
                if ($this->_debug()) {
                    $this->_log("cmulti threads #${i}");
                }

                $ch = curl_init();
                $chs[] = $ch;
                $body = $this->_assembling_body(array_splice($data, 0, $data_size));
        
                if ($this->_debug()) {
                    // 这个参数为 false, 说明只需要校验,不需要真正写入
                    $this->_log('cmulti dry run');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Dry-Run:true'
                    ));
                }

                curl_setopt($ch, CURLOPT_URL, $this->_url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_connect_timeout);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_USERAGENT, 'PHP SDK');

                //judge https
                $pos = strpos($this->_url, 'https');
                if ($pos === 0) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                }

                curl_multi_add_handle($mh,$ch);
            }

            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh);
            } while ($running > 0);

            $info = curl_multi_info_read($mh);

            $error = false;
            foreach ($chs as $ch) {
                $response = curl_multi_getcontent($ch);
                if (false === $response) {
                    $this->_handleError(curl_errno($ch), curl_error($ch));
                    $error = true;
                } else {
                    $response = json_decode($response);
                    if (!$response->success) {
                        $this->_handleError(0, $response);
                        $error = true;
                    }
                }
                curl_multi_remove_handle($mh, $ch);
            }

            if (CURLE_OK != $info['result']) {
                $this->_handleError($info['result'], "curl error with code=".$info['result']);
                $error = true;
            }

            curl_multi_close($mh);
            return !$error;
        }

        return true;
    }

    private function _assembling_body($events)
    {
        // millisecond now
        $timestamp = $this->_millisecond();

        $data = array(
            'time' => $timestamp,
            'events' => $events,
            'sdk' => array(
                'sdk' => BETADATE_SDK,
                'sdk_version' => BETADATE_SDK_VERSION,
            ),
        );

        if ($this->_debug()) {
            $this->_log('data: ' . json_encode($data));
        }
        
        $data = $this->_encode($data);
        $sign = $this->_generate_sign($this->_options['app_id'] . $data . $timestamp);
        $body = array(
            'app_id' => $this->_options['app_id'],
            'data' => $data,
            'sign' => $sign,
            'timestamp' => $timestamp,
            'project' => $this->_options['project'],
        );
        $params = array();
        foreach ($body as $key => $value) {
            $params[] = $key . '=' . urlencode($value);
        }
        $body = implode('&', $params);

        if ($this->_debug()) {
            $this->_log('body: ' . $body);
        }

        return $body;
    }

    /**
     * @return string
     */
    private function _generate_sign($data)
    {
        return hash_hmac($this->_options['algo'], $data, $this->_options['token']);
    }

    /**
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->_connect_timeout;
    }

    /**
     * @return bool|null
     */
    public function getFork()
    {
        return $this->_fork;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }

    /**
     * Number of requests that will be processed in parallel using curl_multi_exec.
     * @return int
     */
    public function getNumThreads() {
        return $this->_num_threads;
    }
}