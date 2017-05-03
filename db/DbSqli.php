<?php
class DbSqli
{
    private $DB_HOST,$DB_USER,$DB_PASS,$DB_NAME,$DB_CHAR,$conn;

    public function __construct($host='',$user='',$pass='',$dbname='',$charset='')
    {
        $conf=require __DIR__.'/../conf/db.php';
        $this->DB_HOST=$host?$host:$conf['DB_HOST'];
        $this->DB_USER=$user?$user:$conf['DB_USERNAME'];
        $this->DB_PASS=$pass?$pass:$conf['DB_PASSWORD'];
        $this->DB_NAME=$dbname?$dbname:$conf['DB_NAME'];
        $this->DB_CHAR=$charset?$charset:$conf['DB_CHARSET'];
        $this->conn=mysqli_connect($this->DB_HOST,$this->DB_USER,$this->DB_PASS,$this->DB_NAME);
        mysqli_set_charset($this->conn,$this->DB_CHAR);
        return $this;
    }

    public function select($sql)
    {
        $select=array();
        $res=mysqli_query($this->conn,$sql);
        while($array=mysqli_fetch_array($res,MYSQLI_ASSOC))
        {
            $select[]=$array;
        }
        return $select;
    }

    public function __destruct()
    {
        mysqli_close($this->conn);
    }
}