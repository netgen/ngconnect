<?php

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$siteINI = eZINI::instance();
$ngConnectINI = eZINI::instance('ngconnect.ini');
$regularRegistration = $ngConnectINI->variable('ngconnect', 'RegularRegistration');

//Couple of sanity checks to see if view can run
$runView = false;
if($http->hasSessionVariable('NGConnectUserID') && $http->hasSessionVariable('NGConnectAuthResult') && $regularRegistration != 'disabled')
{
	$authResult = $http->sessionVariable('NGConnectAuthResult');
	$user = eZUser::fetch($http->sessionVariable('NGConnectUserID'));
	if($user instanceof eZUser && $user->isEnabled() && $user->canLoginToSiteAccess($GLOBALS['eZCurrentAccess']))
	{
		if($regularRegistration == 'optional')
		{
			$user->loginCurrent();

			if(substr($user->Login, 0, 10) === 'ngconnect_')
			{
				$runView = true;
			}
		}
		else
		{
			eZUser::logoutCurrent();
			$runView = true;
		}
	}
	else
	{
		eZUser::logoutCurrent();
	}
}

if($runView)
{
	if($http->hasPostVariable('LoginButton'))
	{
		//user is trying to connect to the existing account

		$badLogin = false;
		$loginNotAllowed = false;
		$login = trim($http->postVariable('Login'));
		$password = trim($http->postVariable('Password'));

		$userToLogin = eZUser::fetchByName($login);
		if($userToLogin instanceof eZUser && $userToLogin->PasswordHash == eZUser::createHash($login, $password, eZUser::site(), eZUser::hashType()))
		{
			if($userToLogin->isEnabled() && $userToLogin->canLoginToSiteAccess($GLOBALS['eZCurrentAccess']))
			{
				eZUser::logoutCurrent();
				$userToLogin->loginCurrent();
				ngConnectFunctions::connectUser($userToLogin->ContentObjectID, $authResult['login_method'], $authResult['id']);
				redirect($http, $module);
			}
			else
			{
				$badLogin = false;
				$loginNotAllowed = true;
			}
		}
		else
		{
			$badLogin = true;
			$loginNotAllowed = false;
		}
	}
	else if($http->hasPostVariable('SkipButton') && $regularRegistration == 'optional')
	{
		//user is allowed to skip only if set so by settings

		if($http->hasPostVariable('DontAskMeAgain'))
		{
			$user->Login = '0_' . $user->Login;
			$user->store();

			redirect($http, $module);
		}
		else
		{
			redirect($http, $module, false);
		}
	}
	else if($http->hasPostVariable('SaveButton'))
	{
		//user wants to "convert" to regular account

		if($http->hasSessionVariable('NGConnectStartedRegistration'))
		{
			eZDebug::writeWarning('Cancel module run to protect against multiple form submits', 'ngconnect/profile');
			$http->removeSessionVariable('NGConnectStartedRegistration');
			return eZModule::HOOK_STATUS_CANCEL_RUN;
		}
		$http->setSessionVariable('NGConnectStartedRegistration', 1);

		$dataMap = $user->contentObject()->dataMap();
		$dataType = $dataMap['user_account']->dataType();
		$validationResult = $dataType->validateObjectAttributeHTTPInput($http, 'ContentObjectAttribute', $dataMap['user_account']);

		if($validationResult === eZInputValidator::STATE_ACCEPTED)
		{
			$login = trim($http->postVariable('ContentObjectAttribute_data_user_login_' . $dataMap['user_account']->attribute('id')));
			$email = trim($http->postVariable('ContentObjectAttribute_data_user_email_' . $dataMap['user_account']->attribute('id')));
			$password = trim($http->postVariable('ContentObjectAttribute_data_user_password_' . $dataMap['user_account']->attribute('id')));

			if(strlen($password) == 0 && $siteINI->variable('UserSettings', 'GeneratePasswordIfEmpty') == 'true')
			{
				$password = $user->createPassword($siteINI->variable('UserSettings', 'GeneratePasswordLength'));
			}

			$db = eZDB::instance();
			$db->begin();

			$user->setAttribute('login', $login);
			$user->setAttribute('email', $email);
			$user->setAttribute('password_hash', eZUser::createHash($login, $password, eZUser::site(), eZUser::hashType()));
			$user->setAttribute('password_hash_type', eZUser::hashType());
			$user->store();

			ngConnectFunctions::connectUser($user->ContentObjectID, $authResult['login_method'], $authResult['id']);

			$db->commit();

			$http->removeSessionVariable('NGConnectStartedRegistration');

			if($authResult['email'] == '' || $email != $authResult['email'])
			{
				//we only validate the account if no email was provided by social network or entered email is not the same
				//as the one from social network

				ngConnectUserActivation::processUserActivation($user, $password);

				$http->removeSessionVariable('NGConnectUserID');
				$http->removeSessionVariable('NGConnectAuthResult');

				return $module->redirectToView('success');
			}

			if($regularRegistration != 'optional') $user->loginCurrent();
			redirect($http, $module);
		}

		$http->removeSessionVariable('NGConnectStartedRegistration');
	}

	$tpl = eZTemplate::factory();

	$tpl->setVariable('ngconnect_user', $user);
	$tpl->setVariable('network_email', trim($authResult['email']));

	if(isset($badLogin) && $badLogin)
		$tpl->setVariable('bad_login', true);
	else if(isset($loginNotAllowed) && $loginNotAllowed)
		$tpl->setVariable('login_not_allowed', true);

	$tpl->setVariable('persistent_variable', false);

	$Result = array();
	$Result['content'] = $tpl->fetch( 'design:ngconnect/profile.tpl' );
	$Result['path'] = array(array(	'text' => ezpI18n::tr('extension/ngconnect/ngconnect/profile', 'Profile setup'),
									'url' => false));

	$contentInfoArray = array();
	$contentInfoArray['persistent_variable'] = false;
	if($tpl->variable('persistent_variable') !== false)
		$contentInfoArray['persistent_variable'] = $tpl->variable('persistent_variable');
	$Result['content_info'] = $contentInfoArray;
}
else
{
	redirect($http, $module);
}

function redirect($http, $module, $clearSession = true)
{
	if($clearSession)
	{
		$http->removeSessionVariable('NGConnectUserID');
		$http->removeSessionVariable('NGConnectAuthResult');
	}

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