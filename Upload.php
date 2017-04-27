<?php
/**
 * Class Upload
 * $config array ['ALLOW_MIME'=>'','MAX_SIZE'=>'','ALLOW_EXT'=>'','UPLOAD_DIR'=>'','SAVE_PATH'=>'']
 * $files array $_FILES
 */
class Upload
{
    const OK=0;
    const EMPTY_FILES=100;
    const FILE_NOT_EXIST=101;
    const FILE_EMPTY=101;
    const FILE_UPLOAD_ERR=102;
    const FILE_OVER_MIME=103;
    const FILE_OVER_SIZE=104;
    const FILE_OVER_EXT=105;
    const FILE_SAVE_FAIL=106;
    const FILES_SAVE_ERR=201;

    private $ALLOW_MIME_TYPE=array();
    private $MAX_FILE_SIZE=0;
    private $ALLOW_FILE_EXT=array();
    private $UPLOAD_DIR='';
    private $SAVE_PATH='';
    private $FILES=null;

    public function __construct($config,$files)
    {
        $this->ALLOW_MIME_TYPE=isset($config['ALLOW_MIME'])?$config['ALLOW_MIME']:array('image/jpeg','image/png');
        $this->MAX_FILE_SIZE=isset($config['MAX_SIZE'])?$config['MAX_SIZE']:3000000;
        $this->ALLOW_FILE_EXT=isset($config['ALLOW_EXT'])?$config['ALLOW_EXT']:array('jpg','jpeg','png');
        $this->UPLOAD_DIR=isset($config['UPLOAD_DIR'])?$config['UPLOAD_DIR']:'/uploads';
        $this->SAVE_PATH=isset($config['SAVE_PATH'])?$config['SAVE_PATH']:(dirname(__FILE__).DIRECTORY_SEPARATOR.'uploads');
        if(!is_dir($this->SAVE_PATH)){
            mkdir($this->SAVE_PATH);
        }
        $this->FILES=$files;
    }

    protected function checkFiles()
    {
        return empty($this->FILES)?self::EMPTY_FILES:self::OK;
    }

    protected function checkFileName($iName)
    {
        if(!isset($this->FILES[$iName]))
            return self::FILE_NOT_EXIST;
        if(empty($this->FILES[$iName]))
            return self::FILE_EMPTY;
        $name = $this->FILES[$iName]['name'];
        $tmp_name = $this->FILES[$iName]['tmp_name'];
        $size = $this->FILES[$iName]['size'];
        $type= $this->FILES[$iName]['type'];
        $error = $this->FILES[$iName]['error'];
        if($error != UPLOAD_ERR_OK)
            return self::FILE_UPLOAD_ERR;
        if(!in_array($type,$this->ALLOW_MIME_TYPE))
            return self::FILE_OVER_MIME;
        if($size>$this->MAX_FILE_SIZE)
            return self::FILE_OVER_SIZE;
        if(!in_array(self::getExt($name),$this->ALLOW_FILE_EXT))
            return self::FILE_OVER_EXT;
        return self::OK;
    }

    protected static function getExt($fileName)
    {
        return pathinfo($fileName, PATHINFO_EXTENSION);
    }

    protected static function trans($code)
    {
        $translation=array(
            self::OK=>'保存成功',
            self::EMPTY_FILES=>'空上传',
            self::FILE_NOT_EXIST=>'指定文件不存在',
            self::FILE_EMPTY=>'指定文件空',
            self::FILE_UPLOAD_ERR=>'指定文件上传失败',
            self::FILE_OVER_MIME=>'指定文件超出MIME范围',
            self::FILE_OVER_SIZE=>'指定文件超出限制大小',
            self::FILE_OVER_EXT=>'指定文件超出允许后缀范围',
            self::FILE_SAVE_FAIL=>'指定文件保存失败',
            self::FILES_SAVE_ERR=>'多个文件保存出错',
        );
        return isset($translation[$code])?$translation[$code]:'';
    }

    protected function makeResponse($code)
    {
        return array(
            'code'=>$code,
            'msg'=>self::trans($code)
        );
    }

    public function saveOne($iName,$fName=null)
    {
        $save_path='';
        $code=$this->checkFiles();
        if($code===self::OK){
            $code=$this->checkFileName($iName);
            if($code===self::OK){
                if($fName===null)
                    $fName=date('YmdHis').rand(0000,9999).'.'.self::getExt($this->FILES[$iName]['name']);
                $save_path="$this->UPLOAD_DIR/$fName";
                $res=move_uploaded_file($this->FILES[$iName]['tmp_name'], $this->SAVE_PATH.DIRECTORY_SEPARATOR.$fName);
                if($res===true){
                    $code=self::OK;
                }else{
                    $code=self::FILE_SAVE_FAIL;
                }
            }
        }
        $response=$this->makeResponse($code);
        $response['fname']=$fName;
        $response['save_path']=$save_path;
        return $response;
    }

    public function saveByNames($iNames=array())
    {
        $code=$this->checkFiles();
        $errNames=array();
        if($code===self::OK){
            $fe=!empty($iNames)?$iNames:$this->FILES;
            foreach($fe as $iName=>$fName)
            {
                $res=$this->saveOne($iName,is_string($fName)?$fName:null);
                if($res['code']!==self::OK){
                    $errNames[$iName]=$res['msg'];
                }
            }
            $code=empty($errNames)?self::OK:self::FILES_SAVE_ERR;
        }
        $response=$this->makeResponse($code);
        $response['errNames']=$errNames;
        return $response;
    }
}
