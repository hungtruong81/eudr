<?php
if (!isset($GLOBALS['THRIFT_ROOT'])) {
    $GLOBALS['THRIFT_ROOT'] = dirname(__FILE__).'/thriftlib';
}
if (!isset($GLOBALS['SCRIBE_ROOT'])) {
    $GLOBALS['SCRIBE_ROOT'] = dirname(__FILE__).'/Log';
}
require $GLOBALS['SCRIBE_ROOT']. '/scriber.php';

class Core_Log
{
    public static function sendLog($message="", $category = 'default')
    {
        $config = array();
        $config['scribe_servers'] = array('127.0.0.1');
        $config['scribe_ports'] 	 = array('1463');
        try {
            $scriber = new scriber($config);
            $scriber->writeLog($category, $message);
        } catch (Exception $e) {
        }
    }
}
