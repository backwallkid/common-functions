<?php
class DbPdo
{
    private $DB_DSN,$DB_USER,$DB_PASS,$dbh,$TBL_PREFIX,$config=array();
    public $error;
    private $select='*',$where,$join,$order,$group,$having,$table='',$limit,$alias='t';
    private $sql='',$binds=array();

    public function __construct($config=array())
    {
        $this->putConfig($config);
        try {
            $this->dbh=new PDO($this->DB_DSN,$this->DB_USER,$this->DB_PASS);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this;
        } catch (PDOException $e) {
            $this->error='Connection failed: ' . $e->getMessage();
            return false;
        }
    }

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
    }

    private function putConfig($config)
    {
        $host=isset($config['host'])?$config['host']:'';
        $user=isset($config['username'])?$config['username']:'';
        $pass=isset($config['password'])?$config['password']:'';
        $dbname=isset($config['dbname'])?$config['dbname']:'';
        $charset=isset($config['charset'])?$config['charset']:'';
        $port=isset($config['port'])?$config['port']:3306;
        $prefix=isset($config['prefix'])?$config['prefix']:'';

        $this->DB_DSN="mysql:dbname={$dbname};host={$host};charset={$charset};port={$port}";
        $this->DB_USER=$user;
        $this->DB_PASS=$pass;
        $this->TBL_PREFIX=$prefix;
    }

    public function __destruct()
    {
        $this->dbh=null;
    }

    protected function runSql()
    {
        try{
            $sth = $this->dbh->prepare($this->sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            foreach($this->binds as $name=>$value){
                $sth->bindValue($name,$value,PDO::PARAM_STR);
            }
            $sth->execute();
            $this->resetAll();
            return $sth;
        } catch (PDOException $e) {
            $this->error='Run failed: ' . $e->getMessage();
            return false;
        }
    }

    public function add($sql,$params)
    {
        $this->sql=$sql;$this->binds=$params;
        $sth=$this->runSql();
        if($sth instanceof PDOStatement){
            $sth->closeCursor();
            return $this->dbh->lastInsertId();
        }else{
            return $this->error;
        }
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        if(in_array($name,array('table','select','where','order','group','having','binds','alias'))){
            $this->$name=isset($arguments[0])?$arguments[0]:'';
            if($name=='table'&&isset($arguments[1])){
                $this->TBL_PREFIX=$arguments[1];
            }
        }elseif($name=='count') {
            $this->limit='1';
            $this->select='count(1) AS c';
            $res=$this->find();
            return isset($res['c'])?$res['c']:$res;
        }
        return $this;
    }

    public function findAll($params=array())
    {
        if(!empty($params))$this->binds=$params;
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

    public function find($params=array())
    {
        if(!empty($params))$this->binds=$params;
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
            if($this->where){
                $this->sql.=" WHERE {$this->where}";
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
            $this->error=$e->getMessage();
        }
    }

    private function formatPart($part,$seperate=',',$erm='')
    {
        if(is_array($this->$part)){
            $this->$part=implode($seperate,$this->$part);
        }elseif(is_string($this->$part)){
            $this->$part=trim($this->$part);
        }elseif($erm){
            throw new PDOException($erm);
        }
    }

    public function findAllBySql($sql,$param)
    {
        $this->sql=$sql;$this->binds=$param;
        return $this->findAll();
    }

    public function updateBySql($sql,$param)
    {
        if(strpos($sql,'LIMIT')===false)$sql.=' LIMIT 1';
        $this->sql=$sql;$this->binds=$param;
        $sth=$this->runSql();
        if($sth instanceof PDOStatement){
            $sth->closeCursor();
            return $sth->rowCount();
        }else{
            return $this->error;
        }
    }
}