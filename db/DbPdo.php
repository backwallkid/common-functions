<?php
/**
 * Class DbPdo
 * Author wl.Huang
 * Readme 使用方法
 * 1. 声明类
   eg. $db=new DbPdo(array(
        'host'=>'localhost',
        'username'=>'root',
        'password'=>'123456',
        'dbname'=>'dbname',
        'charset'=>'utf8',  //默认utf8
        'port'=>3306,       //默认3306
        'prefix'=>'pfx_'
   ));
 * 2. __call方法：接受字符串或数组
 * 2.1 eg.->select('a,b,c') or ->select(['a','b','c'])
 * 2.2 eg.->where('1=1 AND 2=2') or ->where(['1=1','2=2']) or ->where('(a>:a OR b<:b) AND c IS NOT NULL')
 * 2.3 eg.->order('id DESC')
 * 2.4 eg.->limit(1)
 * 2.5 eg.->binds([':a'=>3000,':b'=>1000])
 * 3. 未完成：join on
 */
class DbPdo
{
    private $DB_DSN,$DB_USER,$DB_PASS,$dbh,$TBL_PREFIX,$config=array();
    public $error;
    private $select='*',$where,$join,$order,$group,$having,$table='',$limit,$alias='t';
    private $sql='',$binds=array(),$bindValue=array();
    private $log_save_path;

    public function __construct($config=array())
    {
        $this->putConfig($config);
        try {
            $this->dbh=new PDO($this->DB_DSN,$this->DB_USER,$this->DB_PASS);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this;
        } catch (PDOException $e) {
            $this->error='Connection failed: ' . $e->getMessage();
            $this->log($e);
            return false;
        }
    }

    /**
     * 在runSql的execute后运行，清除除了sql以外的一切用户输入变量
     */
    private function resetAll()
    {
        $this->select='*';
        $this->where='';
        $this->join='';
        $this->order='';
        $this->group='';
        $this->having='';
        $this->table='';
        $this->limit='';
        $this->alias='t';
        $this->binds=array();
        $this->bindValue=array();
    }

    private function putConfig($config)
    {
        $host=isset($config['host'])?$config['host']:'';
        $user=isset($config['username'])?$config['username']:'';
        $pass=isset($config['password'])?$config['password']:'';
        $dbname=isset($config['dbname'])?$config['dbname']:'';
        $charset=isset($config['charset'])?$config['charset']:'utf8';
        $port=isset($config['port'])?$config['port']:3306;
        $prefix=isset($config['prefix'])?$config['prefix']:'';

        $this->DB_DSN="mysql:dbname={$dbname};host={$host};charset={$charset};port={$port}";
        $this->DB_USER=$user;
        $this->DB_PASS=$pass;
        $this->TBL_PREFIX=$prefix;
        $this->log_save_path=DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR;
    }

    public function __destruct()
    {
        $this->dbh=null;
    }

    /**
     * 运行sql，prepare后绑定bindValue
     * @return bool|PDOStatement
     */
    protected function runSql()
    {
        try{
            $sth = $this->dbh->prepare($this->sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            foreach($this->bindValue as $name=>$value){
                $sth->bindValue($name,$value,PDO::PARAM_STR);
            }
            $sth->execute();
            $this->resetAll();
            return $sth;
        } catch (PDOException $e) {
            $this->error='Run failed: ' . $e->getMessage();
            $this->log($e);
            return false;
        }
    }

    /**
     * 直接运行insert语句，成功返回id
     * @param $sql string 需要运行的insert语句，可以使用:占位符
     * @param $params array 当$sql中使用占位符时，必须给予对应数量的符值 eg.[':hold'=>'hold']
     * @return string 成功插入的id或错误信息
     */
    public function addBySql($sql,$params=array())
    {
        $this->sql=$sql;$this->bindValue=$params;
        $sth=$this->runSql();
        if($sth instanceof PDOStatement){
            $sth->closeCursor();
            return $this->dbh->lastInsertId();
        }else{
            return $this->error;
        }
    }

    /**
     * 魔法函数call
     * @param $name string 监听的字符串为table,select,where,order,group,having,binds,alias,count
     * @param $arguments array
     * @return $this|mixed|string
     */
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        if(in_array($name,array('table','select','where','order','group','having','binds','alias'))){
            $this->$name=isset($arguments[0])?$arguments[0]:'';
            if($name=='table'&&isset($arguments[1])){
                $this->TBL_PREFIX=$arguments[1];
            }
        }elseif($name=='count') {
            $this->limit=1;
            $this->select='count(1) AS c';
            $res=$this->find($arguments[0]);
            return isset($res['c'])?$res['c']:$res;
        }
        return $this;
    }

    /**
     * 查找所有
     * @param array $params 查询条件 eg.['a'=>1,'b'=>2]
     * @return array|string
     */
    public function findAll($params=array())
    {
        $this->makeParams($params);
        $this->makeSql();
        if($this->error)return $this->error;
        $sth=$this->runSql();
        if($sth instanceof PDOStatement){
            $res = $sth->fetchAll(PDO::FETCH_ASSOC);
            $sth->closeCursor();
            return $res;
        }else{
            return $this->error;
        }
    }

    /**
     * 查找一条
     * @param array $params 查询条件 eg.['a'=>1,'b'=>2]
     * @return mixed|string
     */
    public function find($params=array())
    {
        $this->makeParams($params);
        $this->limit=1;
        $this->makeSql();
        if($this->error)return $this->error;
        $sth=$this->runSql();
        if($sth instanceof PDOStatement){
            $res = $sth->fetch(PDO::FETCH_ASSOC);
            $sth->closeCursor();
            return $res;
        }else{
            return $this->error;
        }
    }

    /**
     * 将find和findAll的params参数分解为where和bindValue
     * @param array $params eg.['a'=>1,'b'=>2]
     */
    protected function makeParams($params=array())
    {
        if(!empty($params)){
            $this->where=is_array($this->where)?$this->where:array($this->where);
            foreach($params as $name=>$value){
                $this->where[]="`{$name}`=:{$name}";
                $this->bindValue[':'.$name]=$value;
            }
        }
    }

    /**
     * 制作sql语句
     * 将设置的table,where等值合并为一条sql语句，
     * 当binds有值时，使用:占位符拼入where
     */
    protected function makeSql()
    {
        try{
            if(!is_string($this->table)||empty($this->table)){
                throw new PDOException('table value error');
            }
            $this->formatPart('select',',','select value error');
            $this->formatPart('where',' AND ');
            $this->formatPart('order');
            $this->formatPart('group');
            $this->formatPart('having',' AND ');

            $this->sql="SELECT {$this->select} FROM `{$this->TBL_PREFIX}{$this->table}`";

            $bind_where=array();
            if(!empty($this->binds)){
                $this->bindValue+=$this->binds;
            }
            if($this->where){
                $bind_where[]=$this->where;
            }
            if(!empty($bind_where)){
                $this->sql.=" WHERE ".implode(' AND ',$bind_where);
            }
            if($this->order){
                $this->sql.=" ORDER BY {$this->order}";
            }
            if($this->group){
                $this->sql.=" GROUP BY {$this->group}";
                if($this->having){
                    $this->sql.=" HAVING {$this->having}";
                }
            }
            if($this->limit){
                $this->sql.=" LIMIT {$this->limit}";
            }
        }catch (PDOException $e){
            $this->log($e);
            $this->error=$e->getMessage();
        }
    }

    /**
     * 将select,where,order等条件由数组合并为字符串
     * @param $part string 条件
     * @param string $separate 连接字符串
     * @param string $erm 错误码
     */
    private function formatPart($part,$separate=',',$erm='')
    {
        if(is_array($this->$part)){
            $this->$part=array_filter($this->$part);
            $this->$part=implode($separate,$this->$part);
        }elseif(is_string($this->$part)){
            $this->$part=trim($this->$part);
        }elseif($erm){
            throw new PDOException($erm);
        }
    }

    /**
     * 直接运行select语句查询
     * @param $sql string 需要运行的select语句，可使用:占位符
     * @param $param array 当$sql中含有:占位符时，需给予对应数量的符值
     * @return array|string
     */
    public function findAllBySql($sql,$param)
    {
        $this->sql=$sql;$this->binds=$param;
        return $this->findAll();
    }

    /**
     * 添加一条数据
     * @param $data array 数据 eg.['a'=>1,'b'=>2]
     * @param $table string 表名
     * @return int|string 成功返回id，失败返回错误
     */
    public function add($data,$table)
    {
        $params=array();$cols=$hs='';
        $h=':h';$i=0;
        foreach ($data as $col=>$val){
            $cols.=",`{$col}`";
            $hs.=','.$h.$i;
            $params[$h.$i]=$val;
            $i++;
        }
        $cols=substr($cols,1);$hs=substr($hs,1);
        $sql="INSERT INTO `{$this->TBL_PREFIX}{$table}`({$cols}) VALUE ({$hs})";
        $this->sql=$sql;$this->bindValue=$params;
        $sth=$this->runSql();
        if($sth instanceof PDOStatement){
            $sth->closeCursor();
            return $this->dbh->lastInsertId();
        }else{
            return $this->error;
        }
    }

    /**
     * 直接运行update语句更新
     * @param $sql string 需要运行的update语句，可使用:占位符
     * @param $params array 当$sql中含有:占位符时，需给予对应数量的符值
     * @return array|string 成功返回成功更新的行数，失败返回错误
     */
    public function updateBySql($sql,$params=array())
    {
        $this->sql=$sql;$this->bindValue=$params;
        $sth=$this->runSql();
        if($sth instanceof PDOStatement){
            $sth->closeCursor();
            return $sth->rowCount();
        }else{
            return $this->error;
        }
    }

    /**
     * 记录错误日志，当runSql运行出现错误时，将关键信息记入log文件
     * @param PDOException $e
     * @param string $file_name
     */
    public function log(PDOException $e,$file_name='pdo_err')
    {
        $dd=date('Ym');
        $date=date('Y-m-d H:i:s');
        $bv=var_export($this->bindValue,true);

        $errmsg="date:{$date}---------code:{$e->getCode()}\r\n";
        if($this->sql)$errmsg.="sql:{$this->sql}\r\n";
        if($bv)$errmsg.="bindValue:{$bv}\r\n";
        $errmsg.="msg:{$e->getMessage()}\r\n";

        file_put_contents("{$this->log_save_path}{$file_name}_{$dd}.log",$errmsg,FILE_APPEND);
    }

    /**
     * 更新数据
     * @param $data array 数据 eg.['a'=>1,'b'=>2]
     * @param $table string 表名
     * @param $condition array 更新条件 eg.['a'=>1,'b'=>2]
     * @return int|string 成功返回更新行数，失败返回错误
     */
    public function update(array $data,$table,array $condition)
    {
        $params=array();$set=$where=array();
        $h=':h';$i=0;
        foreach ($data as $col=>$val){
            $set[]="`{$col}`={$h}{$i}";
            $params[$h.$i]=$val;
            $i++;
        }
        foreach($condition as $cc=>$cv){
            $where[]="`{$cc}`={$h}{$i}";
            $params[$h.$i]=$cv;
            $i++;
        }
        $set=implode(',',$set);$where=implode(' AND ',$where);
        $sql="UPDATE `{$this->TBL_PREFIX}{$table}` SET {$set} WHERE {$where}";
        $this->sql=$sql;$this->bindValue=$params;
        $sth=$this->runSql();
        if($sth instanceof PDOStatement){
            $sth->closeCursor();
            return $sth->rowCount();
        }else{
            return $this->error;
        }
    }

    /**
     * 记录运行的sql语句和bindValue帮助查错
     */
    public function logSql()
    {
        file_put_contents("{$this->log_save_path}pdo_catch.log","sql:{$this->sql}\r\nbindValue:".var_export($this->bindValue,true)."\r\n",FILE_APPEND);
    }
}