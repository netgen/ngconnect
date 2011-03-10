<?php

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$loginMethod = $Params['LoginMethod'];

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
					eZUser::logoutCurrent();
				}
			}
			else if($debugEnabled && isset($result['message']))
			{
				eZDebug::writeError($result['message'], 'ngconnect/callback');
			}
			else if($debugEnabled)
			{
				eZDebug::writeError('Unknown error', 'ngconnect/callback');
			}
		}
	}
}
else if($debugEnabled)
{
	eZDebug::writeError('Netgen Connect requires CURL & JSON PHP extensions to work.', 'ngconnect/callback');
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