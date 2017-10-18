<?php
/**
+------------------------------------------------------------------------------
 * Upload 文件上传类
+------------------------------------------------------------------------------
 * @package   Upload
+------------------------------------------------------------------------------
 */
class Upload {
    private static $image = null;
    private static $status = 0;
    private static $suffix = null;
    private static $imageType = array('.jpg', '.bmp','.gif','.png'); //允许的图片类型
    private static $message = array(//文件上传错误信息
            '0' => '没有错误发生，文件上传成功。',
            '1' => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。',
            '2' => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。',
            '3' => '文件只有部分被上传。',
            '4' => '没有文件上传。',
            '5' => '未能通过安全检查的文件。',
            '6' => '找不到临时文件夹。',
            '7' => '文件写入失败。',
            '8' => '文件类型不支持',
            '9' => '上传的临时文件丢失。',
      );
      //@ 开始执行文件上传
      public static function start($feild = 'file') {
          if (!empty($_FILES)) {
              self::$status = $_FILES[$feild]['error'];
              if (self::$status > 0)
                  return array('status' => self::$status, 'msg' => self::$message[self::$status]);
              self::$image = $_FILES[$feild]['tmp_name'];
              self::$suffix = strtolower(strrchr($_FILES[$feild]['name'], '.'));
              return array('status' => self::_upload(), 'path' => self::$image, 'msg' => self::$message[self::$status]);
          } else {
              return array('status' => self::$status, 'msg' => self::$message[self::$status]);
          }
      }
    //@ 私有 上传开始
    private static function _upload($path = './upload/') {
        date_default_timezone_set('PRC');
        $newFile = $path . date('Y/m/d/His') . rand(100, 999) . self::$suffix;//定义上传子目录
        self::umkdir(dirname($newFile));
        if (is_uploaded_file(self::$image) && move_uploaded_file(self::$image, $newFile)) {
            self::$image = $newFile; // 生成的新文件名
            if (in_array(self::$suffix, self::$imageType)) // 判断上传类型是否符合规定
                return self::checkHex(); // 返回木马脚本检测的返回值
            else
                return self::$status = 0;
        } else {
            return self::$status = 9;
        }
    }
    //@ 私有 16进制检测
    private static function checkHex() {
        if (file_exists(self::$image)) {
            $resource = fopen(self::$image, 'rb');
            $fileSize = filesize(self::$image);
            fseek($resource, 0); //把文件指针移到文件的开头
            if ($fileSize > 512) { // 若文件大于521B文件取头和尾
                $hexCode = bin2hex(fread($resource, 512));
                fseek($resource, $fileSize - 512);//把文件指针移到文件尾部
                $hexCode .= bin2hex(fread($resource, 512));
            } else { // 取全部
                $hexCode = bin2hex(fread($resource, $fileSize));
            }
            fclose($resource);
            /* 匹配16进制中的 <% ( ) %> */
            /* 匹配16进制中的 <? ( ) ?> */
            /* 匹配16进制中的 <script | /script> 大小写亦可*/
            /* 核心  整个类检测木马脚本的核心在这里  通过匹配十六进制代码检测是否存在木马脚本*/

            if (preg_match("/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hexCode))
                self::$status = 5;
            else
                self::$status = 0;
            return self::$status;
        } else {
            return self::$status = 9;
        }
    }
    //@ 私有 创建目录
    private static function umkdir($dir) {
        if (!file_exists($dir) && !is_dir($dir)) {
            self::umkdir(dirname($dir));
            @mkdir($dir);
        }
    }
}