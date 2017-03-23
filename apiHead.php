<?php
date_default_timezone_set('Asia/Shanghai');

function get($id)
{
    return isset($_GET[$id])?$_GET[$id]:null;
}

function post($id)
{
    return isset($_POST[$id])?$_POST[$id]:null;
}

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