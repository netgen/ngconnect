<?php

class ngConnectTemplateFunctions
{
	function operatorList()
	{
		return array('user_exists');
	}

	function namedParameterPerOperator()
	{
		return true;
	}

	function namedParameterList()
	{
		return array('user_exists' => array('login' => array(
											'type' => 'string',
											'required' => true,
											'default' => '')));
	}

	function modify($tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters)
	{
		switch($operatorName)
		{
			case 'user_exists':
			{
				$operatorValue = self::userExists($namedParameters['login']);
			} break;
		}
	}

	static function userExists($login)
	{
		$user = eZUser::fetchByName($login);
		if($user instanceof eZUser)
			return true;
		return false;
	}
}

?>
