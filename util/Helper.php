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

    public static function get_cdn_client_ip($cdn = 'HTTP_CDN_SRC_IP', $type = 0)
    {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER[$cdn])) {
            $ip     =   $_SERVER[$cdn];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        //IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    public static function get_ip()
    {
        $onlineip='';
        if(getenv('HTTP_CLIENT_IP')&&strcasecmp(getenv('HTTP_CLIENT_IP'),'unknown')){
            $onlineip=getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR')&&strcasecmp(getenv('HTTP_X_FORWARDED_FOR'),'unknown')){
            $onlineip=getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR')&&strcasecmp(getenv('REMOTE_ADDR'),'unknown')){
            $onlineip=getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR'])&&$_SERVER['REMOTE_ADDR']&&strcasecmp($_SERVER['REMOTE_ADDR'],'unknown')){
            $onlineip=$_SERVER['REMOTE_ADDR'];
        }
        $ips=explode(',',$onlineip);
        $ip=trim(array_pop($ips));
        $ip=explode(':',$ip);
        return isset($ip[0])&&$ip[0]?$ip[0]:$onlineip;
    }

    public static function getToken( $len = 32, $md5 = true )
    {
        # Seed random number generator
        # Only needed for PHP versions prior to 4.2
        mt_srand( (double)microtime()*1000000 );
        # Array of characters, adjust as desired
        $chars = array(
            'Q', '@', '8', 'y', '%', '^', '5', 'Z', '(', 'G', '_', 'O', '`',
            'S', '-', 'N', '<', 'D', '{', '}', '[', ']', 'h', ';', 'W', '.',
            '/', '|', ':', '1', 'E', 'L', '4', '&', '6', '7', '#', '9', 'a',
            'A', 'b', 'B', '~', 'C', 'd', '>', 'e', '2', 'f', 'P', 'g', ')',
            '?', 'H', 'i', 'X', 'U', 'J', 'k', 'r', 'l', '3', 't', 'M', 'n',
            '=', 'o', '+', 'p', 'F', 'q', '!', 'K', 'R', 's', 'c', 'm', 'T',
            'v', 'j', 'u', 'V', 'w', ',', 'x', 'I', '$', 'Y', 'z', '*'
        );
        # Array indice friendly number of chars;
        $numChars = count($chars) - 1; $token = '';
        # Create random token at the specified length
        for ( $i=0; $i<$len; $i++ )
            $token .= $chars[ mt_rand(0, $numChars) ];
        # Should token be run through md5?
        if ( $md5 ) {
            # Number of 32 char chunks
            $chunks = ceil( strlen($token) / 32 ); $md5token = '';
            # Run each chunk through md5
            for ( $i=1; $i<=$chunks; $i++ )
                $md5token .= md5( substr($token, $i * 32 - 32, 32) );
            # Trim the token
            $token = substr($md5token, 0, $len);
        } return $token;
    }
}