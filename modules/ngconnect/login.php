<?php

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$loginMethod = $Params['LoginMethod'];

$http->removeSessionVariable('NGConnectLastAccessURI');
if($http->hasGetVariable('redirectURI') && strlen($http->getVariable('redirectURI')) > 0)
{
	$http->setSessionVariable('NGConnectLastAccessURI', urldecode($http->getVariable('redirectURI')));
}
else if($http->hasSessionVariable('LastAccessesURI') && strlen($http->sessionVariable('LastAccessesURI')) > 0)
{
	$http->setSessionVariable('NGConnectLastAccessURI', $http->sessionVariable('LastAccessesURI'));
}

$ngConnectINI = eZINI::instance('ngconnect.ini');
$availableLoginMethods = $ngConnectINI->variable('ngconnect', 'LoginMethods');
$authHandlerClasses = $ngConnectINI->variable('ngconnect', 'AuthHandlerClasses');
$loginWindowType = trim($ngConnectINI->variable('ngconnect', 'LoginWindowType'));
$debugEnabled = (trim($ngConnectINI->variable('ngconnect', 'DebugEnabled')) == 'true');

if(function_exists('curl_init') && function_exists('json_decode'))
{
	if(in_array($loginMethod, $availableLoginMethods) && isset($authHandlerClasses[$loginMethod]))
	{
		$authHandler = ngConnectAuthBase::instance(trim($authHandlerClasses[$loginMethod]));
		if($authHandler instanceof ngConnectAuthBase)
		{
			$result = $authHandler->getRedirectUri();

			if($result['status'] == 'success' && isset($result['redirect_uri']))
			{
				return eZHTTPTool::redirect($result['redirect_uri']);
			}
			else if($debugEnabled && isset($result['message']))
			{
				eZDebug::writeError($result['message'], 'ngconnect/login');
			}
			else if($debugEnabled)
			{
				eZDebug::writeError('Unknown error', 'ngconnect/login');
			}
		}
	}
}
else if($debugEnabled)
{
	eZDebug::writeError('Netgen Connect requires CURL & JSON PHP extensions to work.', 'ngconnect/login');
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