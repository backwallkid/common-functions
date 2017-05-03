<?php
class FormValidation extends Validation
{
    protected function checkForm($data)
    {
        $err=$this->ruleValidation($data,array_flip(array(
            'name','gender','household','marriage','birthday', 'mobile','personal_id',
            'degree','phone','address','postcode','email','fax'
        )),'empty');
        if($this->checkSolo&&!empty($err)){
            return $err;
        }
        $err=$this->ruleValidation($data,array(
            'gender'=>array('男','女'),
            'marriage'=>array('已婚','未婚'),
            'applicant_identity'=>array('个人','企业'),
            'investment_mode'=>array('独立','合伙'),
            'has_experience'=>array('是','否'),
            'has_assets'=>array('是','否'),
        ),'in');
        if($this->checkSolo&&!empty($err)){
            return $err;
        }
        $err=$this->ruleValidation($data,array(
            'mobile'=>'/^\d{11}$/', // /13[1235689]{1}\d{8}|15[1235689]\d{8}|188\d{8}/
            'postcode'=>'/^[0-9]\d{5}$/',
            'personal_id'=>'/(^(\d{15}|\d{18}|\d{17}x)$)/i',
            'fund_amount'=>'/^([1-9]\d{0,20}|0)([.]?|(\.\d{1,2})?)$/',
            'phone'=>'/^(\+[0-9]{2,4}-)?([0-9]{2,4}-)?[0-9]{7,8}$/',
            'fax'=>'/^(\+[0-9]{2,4}-)?([0-9]{2,4}-)?[0-9]{7,8}$/',
        ),'regex');
        if($this->checkSolo&&!empty($err)){
            return $err;
        }
        $err=$this->ruleValidation($data,array_flip(array(
            'birthday'
        )),'date');
        if(!empty($err)){
            return $err;
        }
        return true;
    }

    public static function tName($name)
    {
        $begonTitle=array(
            'name'=>'姓名',
            'gender'=>'性别',
            'household'=>'户籍',
            'marriage'=>'婚否',
            'birthday'=>'出生年月',
            'mobile'=>'手机',
            'personal_id'=>'身份证号码',
            'degree'=>'学历',
            'phone'=>'电话(宅)',
            'address'=>'通讯地址',
            'postcode'=>'邮编',
            'email'=>'E-mail',
            'fax'=>'传真',
        );
        return isset($begonTitle[$name])?$begonTitle[$name]:'';
    }

    public static function tType($type)
    {
        $types=array(
            'in'=>'超出范围',
            'date'=>'不合规',
            'empty'=>'不能为空',
            'regex'=>'不合规',
        );
        return isset($types[$type])?$types[$type]:'';
    }
}


class Validation
{
    protected $checkSolo=true;

    /**
     * Validation constructor.
     * @param bool|true $solo config the Validation, to set check solo or all
     */
    public function __construct($solo=true)
    {
        $this->checkSolo=$solo;
    }

    protected function ruleValidation($data,$scope,$rule)
    {
        $err=array();
        if(in_array($rule,array('in','date','empty','regex')))
        {
            $vf=$rule.'Validation';
            foreach($data as $name=>$value){
                if($this->checkSolo&&!empty($err)){
                    break;
                }
                if(in_array($name,array_keys($scope))){
                    $err[]=$this->$vf($value,$scope[$name],$name);
                }
            }
        }else{
            $err[]=array('unknow','unexpected rule');
        }
        return $err;
    }

    protected function emptyValidation($value,$compare,$name)
    {
        return !$value?array($name,'empty'):array();
    }

    protected function inValidation($value,$compare,$name)
    {
        return !in_array($value,$compare)?array($name,'in'):array();
    }

    protected function regexValidation($value,$compare,$name)
    {
        return preg_match($compare,$value)!==1?array($name,'regex'):array();
    }

    protected function dateValidation($value,$compare,$name)
    {
        return strtotime($value)===false?array($name,'date'):array();
    }

    /**
     * format the errors from validation returns
     * @param array $err [[$name,$type],[],...]
     * @return array $format
     */
    protected function formatErrors($err)
    {
        $format=array();
        foreach($err as $e)
        {
            list($name,$type)=$e;
            if(!isset($format[$name])) $format[$name]=$type;
        }
        return $format;
    }
}
