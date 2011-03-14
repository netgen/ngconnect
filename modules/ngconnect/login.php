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
else
{
	$http->setSessionVariable('NGConnectLastAccessURI', '/');
}

$ngConnectINI = eZINI::instance('ngconnect.ini');
$availableLoginMethods = $ngConnectINI->variable('ngconnect', 'LoginMethods');
$authHandlerClasses = $ngConnectINI->variable('ngconnect', 'AuthHandlerClasses');
$loginWindowType = trim($ngConnectINI->variable('ngconnect', 'LoginWindowType'));
$debugEnabled = (trim($ngConnectINI->variable('ngconnect', 'DebugEnabled')) == 'true');

if(function_exists('curl_init') && function_exists('json_decode'))
{
	if(in_array($loginMethod, $availableLoginMethods) && isset($authHandlerClasses[$loginMethod]) && class_exists(trim($authHandlerClasses[$loginMethod])))
	{
		$authHandlerClassName = trim($authHandlerClasses[$loginMethod]);
		$authHandler = new $authHandlerClassName();
		if($authHandler instanceof INGConnectAuthInterface)
		{
			$currentUser = eZUser::currentUser();
			if($currentUser->isAnonymous()
				|| (!$currentUser->isAnonymous() && !ngConnect::userHasConnection($currentUser->ContentObjectID, $loginMethod)))
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
		else if($debugEnabled)
		{
			eZDebug::writeError('Invalid auth handler class: ' . $authHandlerClassName, 'ngconnect/login');
		}
	}
	else if($debugEnabled)
	{
		eZDebug::writeError('Invalid login method specified.', 'ngconnect/login');
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
		return $module->redirectTo('/');
	}
}

?>