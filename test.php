<?php

require 'src/BetaData.php';

// $data = '{"time":1563869146509,"events":[{"_event":"_app_install","_time":1563869146509,"event_properties":{"_sdk":"php","_sdk_version":"1.10.7","_sdk_method":"code","_sdk_detail":"####\/Users\/chenhongbin\/www\/sdk\/sdk-php\/test.php##5","label":"\u5b89\u88c5"},"user_properties":{"uid":123456}},{"_event":"_app_start","_time":1563869146509,"event_properties":{"_sdk":"php","_sdk_version":"1.10.7","_sdk_method":"code","_sdk_detail":"####\/Users\/chenhongbin\/www\/sdk\/sdk-php\/test.php##5","label":"\u767b\u5f55"},"user_properties":{"uid":123456}}],"sdk":{"sdk":"php","sdk_version":"1.10.7","sdk_method":"code","sdk_detail":"BetaData##flush##\/Users\/chenhongbin\/www\/sdk\/sdk-php\/src\/BetaData.php##124"}}';
// echo $data;
// $z = base64_encode(gzencode(json_encode($data)));
// echo $z;

class a 
{
	function t()
	{
		$analytics = BetaData::getInstance('211288342121', 'eb8fc41f4e55b9ff97300cb119e1a69b', 'moego', array('url' => 'http://t.api.betadata.mocaapp.cn/tracks', 'num_threads' => 2));
		// track an event
		$analytics->track("_app_install", array("label" => "安装"), array('uid' => 123456));
		$analytics->track("_app_start", array("label" => "登录"), array('uid' => 123456));
		$analytics->track("_app_pageview", array("label" => "浏览A"), array('uid' => 123456));
		$analytics->track("_app_pageview", array("label" => "浏览B"), array('uid' => 123456));
		$analytics->track("_app_pageview", array("label" => "浏览C"), array('uid' => 123456));
		$analytics->track("_app_pageview", array("label" => "浏览D"), array('uid' => 123456));
		$analytics->track("_app_end", array("label" => "退出"), array('uid' => 123456));
	}
}

$a = new a();
$a->t();