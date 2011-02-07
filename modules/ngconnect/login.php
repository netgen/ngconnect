<?php

$module = $Params['Module'];
$loginMethod = $Params['LoginMethod'];

$ngConnectINI = eZINI::instance('ngconnect.ini');
$availableLoginMethods = $ngConnectINI->variable('ngconnect', 'LoginMethods');
$authHandlerClasses = $ngConnectINI->variable('ngconnect', 'AuthHandlerClasses');
$loginWindowType = trim($ngConnectINI->variable('ngconnect', 'LoginWindowType'));
$rootNodeID = eZINI::instance('content.ini')->variable('NodeSettings', 'RootNode');

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

if($loginWindowType != 'popup') return $module->redirect('content', 'view', array('full', $rootNodeID));

?>