<?php
/**
* PHP mysqli class
*/
class DB
{
    private $errno = 0;
    private $error = '';
    private $host;
    private $port;
    private $username;
    private $password;
    private $dbname;
    private $charset;
    public $db = false;
    public $iskeep = false;

    function __construct($host, $port, $username, $password, $dbname, $charset, $iskeep=false,$isthrow=false)
    {
		$this->host = $host;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
		$this->dbname = $dbname;
		$this->charset = $charset;
        $this->iskeep = $iskeep;
        try {
            $this->connect($isthrow);
        } catch (Exception $e) {
            serr("[MYSQLD][db-".__LINE__."] MYSQL construct failed.");
            if ( $isthrow ) throw new Exception("MYSQL construct failed.");
        }
    }

    private function connect($isthrow=false)
    {
        if (!$this->db || !mysqli_ping($this->db)) {
            if ($this->db) {
                @mysqli_close($this->db);
            }
            for ($i=0; $i < 2; $i++) {
                $db = mysqli_connect(($this->iskeep?"p:":"").$this->host, $this->username, $this->password, $this->dbname,$this->port);
                if(!$db){
                    $this->db = false;
                    $this->errno = mysqli_connect_errno();
                    $this->error = mysqli_connect_error();
                }
                else {
                    $this->db = $db;
                    $this->errno = 0;
                    $this->error = '';
                    mysqli_set_charset($this->db, $this->charset);
                    return $this->db;
                }
            }
            serr("[MYSQLD][db-".__LINE__."] MySQL reconnect failed ".$this->errno." , ".$this->error);
            if ( $isthrow ) throw new Exception("MYSQL reconnect failed.");
            return false;
        }
        return $this->db;
    }


    /**
     * @param string $sql
     * @return query_result|false
     */
    public function runSql($sql,$isthrow=false)
    {
        if (!$this->db) {
            try {
                $this->db = $this->connect($isthrow);
            } catch (Exception $e) {
                if ( $isthrow ) throw new Exception("MYSQL reconnect failed.");
                return false;
            }
        }
        $res = false;
        for ($i = 0; $i < 2; $i++)
        {
            $res = mysqli_query($this->db,$sql);
            if (false===$res) {
                $this->errno = $errno = mysqli_errno($this->db);
                $this->error = $error = mysqli_error($this->db);
                if ($errno ==2006 || $error == 2013) {
                    try {
                        $this->db = $this->connect($isthrow);
                    } catch (Exception $e) {
                        if ( $isthrow ) throw new Exception("MYSQL reconnect failed.");
                        return false;
                    }
                }
                else {
                    serr("[MYSQLD][db-".__LINE__."] MySQL query failed ".$this->errno." , ".$this->error." , ".$sql);
                    if ( $isthrow ) throw new Exception("MYSQL query failed.");
                    return false;
                }
            }
            else {
                return $res;
            }
        }
        return false;
    }

    /**
     * 获取受影响的行数
     * @return int|false
     */
    public function affectedRows($isthrow=false)
    {
        if (!$this->db) {
            try {
                $this->db = $this->connect($isthrow);
            } catch (Exception $e) {
                if ( $isthrow ) throw new Exception("MYSQL reconnect failed.");
                return false;
            }
        }
        return mysqli_affected_rows($this->db);
    }


    /**
     * 运行Sql,以多维数组方式返回结果集
     * @author aaron
     * @param string $sql
     * @return array 成功返回数组，失败时返回array()
     */
    public function getData($sql,$isthrow=false)
    {
        $data = array();
        try {
            $res = $this->runSql($sql,$isthrow);
            if ($res === false) {
                return $data;
            }
            while($array = mysqli_fetch_array($res, MYSQL_ASSOC)){
                $data[] = $array;
            }
            mysqli_free_result($res);
            return $data;
        } catch (Exception $e) {
            if ( $isthrow ) throw new Exception("MYSQL connect failed.");
            return $data;
        }
    }



    /**
     * 运行Sql,以数组方式返回结果集第一条记录
     * @param string $sql
     * @return array 成功返回数组，失败时返回array()
     */
    public function getLine($sql,$isthrow=false)
    {
        $data = $this->getData($sql,$isthrow);
        if ($data) {
            return @reset($data);
        } else {
            return array();
        }
    }

    /**
     * 运行Sql,返回结果集第一条记录的第一个字段值
     * @param string $sql
     * @return mixxed 成功时返回一个值，失败时返回false
     */
    public function getVar($sql,$isthrow=false)
    {
        $data = $this->getLine($sql,$isthrow);
        if ($data) {
            return $data[@reset(@array_keys($data))];
        } else {
            return FALSE;
        }
    }
	
	/**
	 * 插入数据，如果is_sql为true则不插入数据而是返回生成的sql语句
	 */
	public function insert($tablename, $data, $is_sql = false) {
		$data = array_map(array($this, '_value'), $data);
		$sql = 'insert into `'.$tablename.'`(`'.implode('`,`', array_keys($data)).'`) values('.implode(',', $data).')';
		if($is_sql) {
			return $sql;
		}
		
		$this->runSql($sql);
		
		return intval($this->lastId());
	}
	
	protected function _value($value) {
		if($value === null) {
			return 'NULL';
		} elseif($value === true) {
			return 1;
		} elseif ($value === false) {
			return 0;
		} elseif (is_numeric($value)) {
			return $value;
		} else {
			return '"'. addcslashes($value, '\\"') . '"';
		}
	}


    /**
     * 获取新增的id
     * @return int 成功返回last_id,失败时返回false
     */
    public function lastId($isthrow=false)
    {
        if (!$this->db) {
            try {
                $this->db = $this->connect($isthrow);
            } catch (Exception $e) {
                if ( $isthrow ) throw new Exception("MYSQL reconnect failed.");
                return false;
            }
        }
        return mysqli_insert_id($this->db);
    }
    /**
     * 关闭数据库连接
     * @return bool
     */
    public function closeDb()
    {
        return @mysqli_close($this->db);
    }

    public function close()
    {
        return $this->closeDb();
    }


    public function errno()
    {
        return $this->errno;
    }
    public function errmsg()
    {
        return $this->error;
    }

    public function version($isthrow)
    {
        if (!$this->db) {
            try {
                $this->db = $this->connect($isthrow);
            } catch (Exception $e) {
                if ( $isthrow ) throw new Exception("MYSQL reconnect failed.");
                return false;
            }
        }
        return mysqli_get_client_version($this->db);
    }
}
