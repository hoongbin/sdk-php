<?php
/**
 * BetaData licence
 *
 * @copyright  Copyright 2019 BetaData, Inc.
 * @license  http://wwww.betadata.io/ BetaData License
 */

namespace hoongbin\sdkphp;

use yii\base\Component;

/**
 * Class YiiBetaData
 */
class YiiBetaData extends Component
{
    /**
     * @var appID
     */
    public $appId;

    /**
     * @var token
     */
    public $token;

    /**
     * @var 项目名称
     */
    public $project;

    /**
     * @var 埋点地址
     */
    public $options;

    /**
     * @var array 属性
     */
    public $properties = ['event_properties' => [], 'user_properties' => []];

    /**
     * 埋点
     *
     * @param string $eventName 事件名称
     * @param array $eventProperties 事件属性
     * @param array $userProperties 用户属性
     *
     * @throws BetaData_Exception_IllegalDataException
     */
    public function track($eventName, $eventProperties = [], $userProperties = [])
    {
        // 前置操作
        $this->trigger('beforeProperties');

        // 拼装用户属性
        $eventProperties = array_merge($eventProperties, $this->properties['event_properties']);
        $userProperties = array_merge($userProperties, $this->properties['user_properties']);

        // 后置操作
        $this->trigger('afterProperties');

        // 实例betadata
        $betaModel = \BetaData::getInstance($this->appId, $this->token, $this->project, $this->options);

        // 埋点数据推送
        $betaModel->track($eventName, $eventProperties, $userProperties);
    }
}