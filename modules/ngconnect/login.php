<?php

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$loginMethod = $Params['LoginMethod'];

$http->removeSessionVariable('NGConnectLastAccessURI');
if($http->hasSessionVariable('LastAccessesURI') && strlen($http->sessionVariable('LastAccessesURI')) > 0)
{
	$http->setSessionVariable('NGConnectLastAccessURI', $http->sessionVariable('LastAccessesURI'));
}

$ngConnectINI = eZINI::instance('ngconnect.ini');
$availableLoginMethods = $ngConnectINI->variable('ngconnect', 'LoginMethods');
$authHandlerClasses = $ngConnectINI->variable('ngconnect', 'AuthHandlerClasses');
$loginWindowType = trim($ngConnectINI->variable('ngconnect', 'LoginWindowType'));

if(in_array($loginMethod, $availableLoginMethods) && isset($authHandlerClasses[$loginMethod]))
{
	$authHandler = ngConnectAuthBase::instance(trim($authHandlerClasses[$loginMethod]));
	if($authHandler instanceof ngConnectAuthBase)
	{
		$result = $authHandler->getRedirectUri();

		if($result['status'] == 'success')
		{
			return eZHTTPTool::redirect($result['redirect_uri']);
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