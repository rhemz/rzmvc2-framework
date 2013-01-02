<?php


interface Database_Interface
{
	public function __construct($config);

	public function connect();
	
	public function query($sql);

	public function result();

	public function close();

	public function escape();

}


class Database_Connection_Exception extends Rz_MVC_Exception
{
	public function __construct($type, $host, $port, $user, $pass)
	{ 
		$message = sprintf("Unable to connect to %s server ", $type);

		$message .= ENVIRONMENT == Environment::Development
			? sprintf("(%s) on port %d with credentials %s/%s", $host, $port, $user, $pass)
			: "using the supplied settings";

		parent::__construct($message);
	}
}