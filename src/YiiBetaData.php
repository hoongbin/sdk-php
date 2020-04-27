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
        if (isset($this->properties['event_properties']['_backend_event']) && $this->properties['event_properties']['_backend_event'] == true) {
            // 设备号
            if (isset($this->properties['event_properties']['_device_id'])) {
                unset($this->properties['event_properties']['_device_id']);
            }
            // 操作系统
            if (isset($this->properties['event_properties']['_os'])) {
                unset($this->properties['event_properties']['_os']);
            }
            // 操作系统版本
            if (isset($this->properties['event_properties']['sv'])) {
                unset($this->properties['event_properties']['sv']);
            }
            // 屏幕宽度
            if (isset($this->properties['event_properties']['_screen_width'])) {
                unset($this->properties['event_properties']['_screen_width']);
            }
            // 屏幕高度
            if (isset($this->properties['event_properties']['_screen_height'])) {
                unset($this->properties['event_properties']['_screen_height']);
            }
            // 设备型号
            if (isset($this->properties['event_properties']['_model'])) {
                unset($this->properties['event_properties']['_model']);
            }
            // 设备制造商
            if (isset($this->properties['event_properties']['_manufacturer'])) {
                unset($this->properties['event_properties']['_manufacturer']);
            }
            // 网络类型
            if (isset($this->properties['event_properties']['_network_type'])) {
                unset($this->properties['event_properties']['_network_type']);
            }
            // 客户端IP
            if (isset($this->properties['event_properties']['_ip'])) {
                unset($this->properties['event_properties']['_ip']);
            }
            // 渠道
            if (isset($this->properties['event_properties']['_channel'])) {
                unset($this->properties['event_properties']['_channel']);
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