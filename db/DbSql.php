<?php
class DbSql
{
    private $DB_HOST,$DB_USER,$DB_PASS,$DB_NAME,$DB_CHAR,$conn;

    public function __construct($host='',$user='',$pass='',$dbname='',$charset='')
    {
        $this->DB_HOST=$host;
        $this->DB_USER=$user;
        $this->DB_PASS=$pass;
        $this->DB_NAME=$dbname;
        $this->DB_CHAR=$charset;
        $this->conn=mysql_connect($this->DB_HOST,$this->DB_USER,$this->DB_PASS);
        mysql_select_db($this->DB_NAME);
        mysql_set_charset($this->DB_CHAR,$this->conn);
        return $this;
    }

    public function select($sql)
    {
        $select=array();
        $res=mysql_query($sql,$this->conn);
        while($array=mysql_fetch_array($res,MYSQL_ASSOC))
        {
            $select[]=$array;
        }
        return $select;
    }

    public function __destruct()
    {
        mysql_close($this->conn);
    }
}