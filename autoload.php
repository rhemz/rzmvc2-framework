<?php

function __autoload($class)
{
	$class = strtolower($class);
	$config =& Config::get_instance();

	$suffixes = array(
		$config->get('paths.controller_suffix') => $config->get('paths.controllers'),
		$config->get('paths.model_suffix') => $config->get('paths.models')
	);

	// look for user defined models and controllers first
	foreach($suffixes as $suffix => $path)
	{
		if(stripos($class, ($s = sprintf("_%s", $suffix))) !== false)
		{
			$class = str_ireplace($s, '', $class);
			require_once(APPLICATION_PATH . $path . DIRECTORY_SEPARATOR . $class . PHP_EXT);
			return;
		}
	}


	// if not found, look everywhere. application directory first, then framework
	foreach(array(APPLICATION_PATH, FRAMEWORK_PATH) as $path)
	{
		foreach(new RecursiveIteratorIterator($i = new RecursiveDirectoryIterator($path)) as $item)
		{
			if( $item->isDir() 
				&& !in_array($i, $config->get('paths.nolook'))
				&& file_exists($p = $item->getPathname() . DIRECTORY_SEPARATOR . $class . PHP_EXT))
			{
				require_once($p);
				return;
			}
		}
	}
	
}