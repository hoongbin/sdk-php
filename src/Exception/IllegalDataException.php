<?php

require_once(dirname(__FILE__) . "/Exception.php");

// 在发送的数据格式有误时，SDK会抛出此异常，用户应当捕获并处理。
class BetaData_Exception_IllegalDataException extends BetaData_Exception {

}