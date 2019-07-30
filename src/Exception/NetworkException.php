<?php

require_once(dirname(__FILE__) . "/Exception.php");

// 在因为网络或者不可预知的问题导致数据无法发送时，SDK会抛出此异常，用户应当捕获并处理。
class BetaData_Exception_NetworkException extends BetaData_Exception {

}