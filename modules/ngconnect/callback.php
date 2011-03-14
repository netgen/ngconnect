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
				$http->removeSessionVariable('NGConnectRedirectToProfile');
				$http->removeSessionVariable('NGConnectUserID');
				$http->removeSessionVariable('NGConnectLoginMethod');
				$http->removeSessionVariable('NGConnectNetworkUserID');
				$http->removeSessionVariable('NGConnectNetworkEmail');

				$currentUser = eZUser::currentUser();
				if($currentUser->isAnonymous())
				{
					$socialNetworkConnections = ngConnect::fetchBySocialNetwork($loginMethod, $result['id']);
					if(is_array($socialNetworkConnections) && !empty($socialNetworkConnections))
					{
						$usersFound = array();
						$userIDs = array();
						foreach($socialNetworkConnections as $connection)
						{
							$userToLogin = eZUser::fetch($connection->UserID);
							if($userToLogin instanceof eZUser && $userToLogin->isEnabled()
								&& $userToLogin->canLoginToSiteAccess($GLOBALS['eZCurrentAccess']))
							{
								$usersFound[] = $userToLogin;
								$userIDs[] = $userToLogin->ContentObjectID;
							}
						}

						if(!empty($usersFound))
						{
							$usersFound[0]->loginCurrent();
							array_shift($userIDs);
							//TODO: Enable user to choose which account to login to
							//if more than one is found connected to provided social
							//network account
						}
						else
						{
							eZUser::logoutCurrent();
						}
					}
					else
					{
						$user = ngConnectFunctions::createOrUpdateUser($loginMethod, $result);
						if($user instanceof eZUser && $user->isEnabled()
							&& $user->canLoginToSiteAccess($GLOBALS['eZCurrentAccess']))
						{
							$user->loginCurrent();

							if(substr($user->Login, 0, 10) === 'ngconnect_')
							{
								$http->setSessionVariable('NGConnectUserID', $user->ContentObjectID);
								$http->setSessionVariable('NGConnectLoginMethod', $loginMethod);
								$http->setSessionVariable('NGConnectNetworkUserID', $result['id']);
								$http->setSessionVariable('NGConnectNetworkEmail', $result['email']);

								if($loginWindowType == 'page')
								{
									return $module->redirectToView('profile');
								}
								else
								{
									$http->setSessionVariable('NGConnectRedirectToProfile', 'true');
								}
							}
						}
						else
						{
							eZUser::logoutCurrent();
						}
					}
				}
				else
				{
					ngConnectFunctions::connectUser($currentUser->ContentObjectID, $loginMethod, $result['id']);
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