<?php
class Helper
{
    public static function get($name,$default=null){return isset($_GET[$name])?$_GET[$name]:$default;}

    public static function post($name,$default=null){return isset($_POST[$name])?$_POST[$name]:$default;}

    public static function redirect($url='/'){header('location:'.$url);exit;}

    public static function getSession($name){return isset($_SESSION[$name])?$_SESSION[$name]:null;}

    public static function setSession($name,$value){$_SESSION[$name]=$value;return true;}

    public static function getCookie($name){return isset($_COOKIE[$name])?$_COOKIE[$name]:null;}

    public static function setCookie($name,$value){setcookie($name,$value,time()+86400*30);return true;}

    public static function getPage($default='index')
    {
        preg_match('/.*\/(.*?)\.php/i',$_SERVER['SCRIPT_FILENAME'],$match);
        return isset($match[1])?$match[1]:$default;
    }

    public static function request($url,array $content,$method='POST')
    {
        if($method=='POST'){
            $context = array(
                'http' => array(
                    'method'  => $method,
                    'header'  => 'Content-Type:application/json;charset=utf-8',
                    'content' => $content)
            );
            $stream_context = stream_context_create($context);
            $data = file_get_contents($url, false, $stream_context);
        }else{
            $data=file_get_contents($url.'?'.http_build_query($content));
        }
        return $data;
    }

    public static function getNoncestr()
    {
        $str='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($str),0,rand(16,32));
    }

    public static function makeWxOauthUrl($appid,$redirectUri,$scope='snsapi_userinfo',$state='')
    {
        $fmt='https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&state=%s&connect_redirect=1#wechat_redirect';
        return vsprintf($fmt,array($appid,urlencode($redirectUri),$scope,$state));
    }

    public static function isEnableCookie()
    {
        setcookie("CookieCheck","OK",time()+3600,"/","test.com");
        return !isset($_COOKIE["CookieCheck"])?false:true;
    }

    public static function plainText($msg='')
    {
        return <<<EOF
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body>$msg</body>
</html>
EOF;
    }

    public function request_by_curl($remote_server, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_USERAGENT, "");
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}