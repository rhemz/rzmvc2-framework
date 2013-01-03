<?php


class Database_MySQLi implements Database_Interface
{
	const Default_Port = 3306;

	private $conn;
	
	private $host;
	private $port;
	private $user;
	private $password;
	private $database;

	private $result;


	public function __construct($config)
	{
		$this->port = is_null($config['port']) ? self::Default_Port : $config['port'];
		$this->host = sprintf("%s:%s", $config['hostname'], $this->port);
		$this->user = $config['username'];
		$this->password = $config['password'];
		$this->database = $config['database'];
	}


	public function connect()
	{
		$this->conn = new mysqli($this->host, $this->user, $this->password, $this->database);

		if($this->conn->connect_error)
		{
			throw new Database_Connection_Exception('MySQLi', $this->host, $this->port, $this->user, $this->password);
		}
	}


	public function query($sql, $bindings = null)
	{
		if(!$this->result = $this->conn->query(is_null($bindings) ? $sql : $this->parse_bindings($sql, $bindings)))
		{
			Logger::log($this->conn->mysqli_error, Log_Level::Warning);
			// throw error or return false
		}
		return true;
	}


	public function result()
	{
		if($this->result->num_rows > 0)
		{
			$result = array();
			while($row = $this->result->fetch_assoc())
			{
				$result[] = $row;
			}
			$this->result->free();

			return new Result_Set($result);
		}
	}


	public function close()
	{
		$this->conn->close();
	}


	public function escape($str)
	{
		return $this->conn->real_escape_string($str);
	}


	private function parse_bindings($sql, $bindings)
	{
		$qbits = explode('?', $sql);
		$i = 0;

		// start building bound query
		$sql = $qbits[0];
		foreach($bindings as $val)
		{
			$sql .= $this->translate_binding_datatype($val) . $qbits[++$i];
		}

		return $sql;
	}


	private function translate_binding_datatype($val)
	{
		if(is_string($val))
		{
			return sprintf("'%s'", $this->escape($val));
		}
		else if(is_bool($val))
		{
			return ($val === true) ? 1 : 0;
		}
		else if(is_null($val))
		{
			return 'NULL';
		}

		return $val;
	}


}