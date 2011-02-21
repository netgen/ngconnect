<?php

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$loginMethod = $Params['LoginMethod'];

$ngConnectINI = eZINI::instance('ngconnect.ini');
$availableLoginMethods = $ngConnectINI->variable('ngconnect', 'LoginMethods');
$authHandlerClasses = $ngConnectINI->variable('ngconnect', 'AuthHandlerClasses');
$loginWindowType = trim($ngConnectINI->variable('ngconnect', 'LoginWindowType'));

if(in_array($loginMethod, $availableLoginMethods) && isset($authHandlerClasses[$loginMethod]))
{
	$authHandler = ngConnectAuthBase::instance(trim($authHandlerClasses[$loginMethod]));
	if($authHandler instanceof ngConnectAuthBase)
	{
		$result = $authHandler->processAuth();

		if($result['status'] == 'success')
		{
			$user = ngConnectFunctions::createOrUpdateUser($loginMethod, $result);
			if($user instanceof eZUser && $user->canLoginToSiteAccess($GLOBALS['eZCurrentAccess']))
			{
				$user->loginCurrent();
			}
			else
			{
				$user->logoutCurrent();
			}
		}
	}
}

if($loginWindowType != 'popup')
{
	if($http->hasSessionVariable('NGConnectLastAccessURI'))
	{
		return $module->redirectTo($http->sessionVariable('NGConnectLastAccessURI'));
	}
	else
	{
		$rootNodeID = eZINI::instance('content.ini')->variable('NodeSettings', 'RootNode');
		return $module->redirect('content', 'view', array('full', $rootNodeID));
	}
}

?>