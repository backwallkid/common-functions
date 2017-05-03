<?php
defined('APP_ROOT') or define('APP_ROOT',dirname(__FILE__));
date_default_timezone_set('Asia/Shanghai');

function get($name,$default=null){return isset($_GET[$name])?$_GET[$name]:$default;}
function post($name,$default=null){return isset($_POST[$name])?$_POST[$name]:$default;}


$existOptions=array(
    'getTicket'
);
$op=get('op');
if($op===null||!in_array($op,$existOptions)){
    header('Location:http://www.test.com');
}else{
    call_user_func($op);
}

function getTicket()
{
    //TODO: logic
}