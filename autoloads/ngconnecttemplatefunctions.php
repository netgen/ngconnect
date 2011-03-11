<?php

class ngConnectTemplateFunctions
{
	function operatorList()
	{
		return array('user_has_connection', 'user_exists');
	}

	function namedParameterPerOperator()
	{
		return true;
	}

	function namedParameterList()
	{
		return array('user_has_connection' => array('user_id' => array(
														'type' => 'integer',
														'required' => true,
														'default' => 0),
											 		'login_method' => array(
											 			'type' => 'string',
											 			'required' => true,
											 			'default' => '')),
					'user_exists' => array('login' => array(
												'type' => 'string',
												'required' => true,
												'default' => '')));
	}

	function modify($tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters)
	{
		switch($operatorName)
		{
			case 'user_has_connection':
			{
				$operatorValue = ngConnect::userHasConnection($namedParameters['user_id'], $namedParameters['login_method']);
			} break;
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
