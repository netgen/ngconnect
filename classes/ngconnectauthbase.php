<?php

class ngConnectAuthBase
{
	public static function instance($className)
	{
		if(!class_exists($className))
			return false;

		return new $className();
	}
}

?>