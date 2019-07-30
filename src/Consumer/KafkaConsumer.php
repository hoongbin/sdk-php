<?php
require_once(dirname(__FILE__) . "/AbstractConsumer.php");
/**
 * Consumes messages and writes them to a kafka queue
 */
class BetaData_Consumer_KafkaConsumer extends BetaData_Consumer_AbstractConsumer
{
    /**
     * Creates a new KafkaConsumer and assigns properties from the $options array
     * @param array $options
     */
    function __construct($options)
    {
        parent::__construct($options);
    }


    /**
     * Append $batch to a queue
     * @param array $batch
     * @return bool
     */
    public function persist($batch)
    {
        return true;
    }
}