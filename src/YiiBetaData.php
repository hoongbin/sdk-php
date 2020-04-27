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
     * @var 开关
     */
    public $switch = true;

    /**
     * @var array 属性
     */
    public $properties = ['event_properties' => [], 'user_properties' => []];

    /**
     * 埋点
     *
     * @param string  $eventName       事件名称
     * @param array   $eventProperties 事件属性
     * @param array   $userProperties  用户属性
     *
     * @throws BetaData_Exception_IllegalDataException
     */
    public function track($eventName, $eventProperties = [], $userProperties = [])
    {
        // 前置操作
        $this->trigger('beforeProperties');

        // 如果开关关闭不需要上传beta数据
        if ($this->switch == false) {
            return;
        }

        // 如果是后台事件(非用户自主触发)删除相关设备信息
        if (isset($eventProperties['_backend_event']) && $eventProperties['_backend_event'] == true) {
            // 设备号
            if (isset($eventProperties['_device_id'])) {
                unset($eventProperties['_device_id']);
            }
            // 操作系统
            if (isset($eventProperties['_os'])) {
                unset($eventProperties['_os']);
            }
            // 操作系统版本
            if (isset($eventProperties['sv'])) {
                unset($eventProperties['sv']);
            }
            // 屏幕宽度
            if (isset($eventProperties['_screen_width'])) {
                unset($eventProperties['_screen_width']);
            }
            // 屏幕高度
            if (isset($eventProperties['_screen_height'])) {
                unset($eventProperties['_screen_height']);
            }
            // 设备型号
            if (isset($eventProperties['_model'])) {
                unset($eventProperties['_model']);
            }
            // 设备制造商
            if (isset($eventProperties['_manufacturer'])) {
                unset($eventProperties['_manufacturer']);
            }
            // 网络类型
            if (isset($eventProperties['_network_type'])) {
                unset($eventProperties['_network_type']);
            }
            // 客户端IP
            if (isset($eventProperties['_ip'])) {
                unset($eventProperties['_ip']);
            }
            // 渠道
            if (isset($eventProperties['_channel'])) {
                unset($eventProperties['_channel']);
            }
        }

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