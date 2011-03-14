<?php

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$loginMethod = $Params['LoginMethod'];

$ngConnectINI = eZINI::instance('ngconnect.ini');
$availableLoginMethods = $ngConnectINI->variable('ngconnect', 'LoginMethods');
$authHandlerClasses = $ngConnectINI->variable('ngconnect', 'AuthHandlerClasses');
$loginWindowType = trim($ngConnectINI->variable('ngconnect', 'LoginWindowType'));
$debugEnabled = (trim($ngConnectINI->variable('ngconnect', 'DebugEnabled')) == 'true');

//we don't allow ngconnect/profile to run by default
$http->removeSessionVariable('NGConnectRedirectToProfile');
$http->removeSessionVariable('NGConnectUserID');
$http->removeSessionVariable('NGConnectAuthResult');

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
				$currentUser = eZUser::currentUser();
				if($currentUser->isAnonymous())
				{
					$socialNetworkConnections = ngConnect::fetchBySocialNetwork($loginMethod, $result['id']);
					if(is_array($socialNetworkConnections) && !empty($socialNetworkConnections))
					{
						// user has already "converted" their social network account to regular
						// eZ Publish account, so just find it and login to it
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
						//no "conversion" happened before, so we create a new account/or update existing social
						//network account and go from there
						$regularRegistration = $ngConnectINI->variable('ngconnect', 'RegularRegistration');
						if($regularRegistration == 'disabled')
						{
							//"Conversion" of accounts is disabled
							//we just create/update a social network account and login the user

							$user = ngConnectFunctions::createOrUpdateUser($loginMethod, $result);
							if($user instanceof eZUser && $user->isEnabled()
								&& $user->canLoginToSiteAccess($GLOBALS['eZCurrentAccess']))
							{
								$user->loginCurrent();
							}
							else
							{
								eZUser::logoutCurrent();
							}
						}
						else
						{
							//"conversion" is not disabled, we redirect to ngconnect/profile

							$user = ngConnectFunctions::createOrUpdateUser($loginMethod, $result);
							if($user instanceof eZUser && $user->isEnabled()
								&& $user->canLoginToSiteAccess($GLOBALS['eZCurrentAccess']))
							{
								//we will login the user only if "conversion" is optional
								if($regularRegistration == 'optional') $user->loginCurrent();

								$http->setSessionVariable('NGConnectUserID', $user->ContentObjectID);
								$http->setSessionVariable('NGConnectAuthResult', $result);

								if($loginWindowType == 'page')
								{
									return $module->redirectToView('profile');
								}
								else
								{
									$http->setSessionVariable('NGConnectRedirectToProfile', 'true');
								}
							}
							else
							{
								eZUser::logoutCurrent();
							}
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