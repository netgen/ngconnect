<?php

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$siteINI = eZINI::instance();

//Couple of sanity checks
$accessAllowed = false;
if($http->hasSessionVariable('NGConnectUserID') && $http->hasSessionVariable('NGConnectLoginMethod')
	&& $http->hasSessionVariable('NGConnectNetworkUserID') && $http->hasSessionVariable('NGConnectNetworkEmail'))
{
	$currentUser = eZUser::currentUser();
	$user = eZUser::fetch($http->sessionVariable('NGConnectUserID'));
	if($user instanceof eZUser && substr($user->Login, 0, 10) === 'ngconnect_'
		&& $user->isEnabled() && $user->canLoginToSiteAccess($GLOBALS['eZCurrentAccess'])
		&& $user->ContentObjectID == $currentUser->ContentObjectID)
	{
		$accessAllowed = true;
	}
}

if($accessAllowed)
{
	if($http->hasPostVariable('SkipButton'))
	{
		if($http->hasPostVariable('DontAskMeAgain'))
		{
			$http->removeSessionVariable('NGConnectUserID');
			$http->removeSessionVariable('NGConnectLoginMethod');
			$http->removeSessionVariable('NGConnectNetworkUserID');
			$http->removeSessionVariable('NGConnectNetworkEmail');

			$user->Login = '0_' . $user->Login;
			$user->store();
		}

		redirect($http, $module);
	}
	else if($http->hasPostVariable('SaveButton'))
	{
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

			$ngConnect = ngConnect::fetch($user->ContentObjectID, $http->sessionVariable('NGConnectLoginMethod'), $http->sessionVariable('NGConnectNetworkUserID'));
			if(!($ngConnect instanceof ngConnect))
			{
				$ngConnect = new ngConnect(array(
					'user_id'				=> $user->ContentObjectID,
					'login_method'			=> $http->sessionVariable('NGConnectLoginMethod'),
					'network_user_id'		=> $http->sessionVariable('NGConnectNetworkUserID')
				));
				$ngConnect->store();
			}

			$db->commit();

			$redirectToSuccess = false;
			if($http->sessionVariable('NGConnectNetworkEmail') == '' || $email != $http->sessionVariable('NGConnectNetworkEmail'))
			{
				ngConnectUserActivation::processUserActivation($user, $password);
				$redirectToSuccess = true;
			}

			$http->removeSessionVariable('NGConnectUserID');
			$http->removeSessionVariable('NGConnectLoginMethod');
			$http->removeSessionVariable('NGConnectNetworkUserID');
			$http->removeSessionVariable('NGConnectNetworkEmail');

			$http->removeSessionVariable('NGConnectStartedRegistration');

			if($redirectToSuccess)
			{
				$module->redirectToView('success');
			}
			else
			{
				redirect($http, $module);
			}
		}

		$http->removeSessionVariable('NGConnectStartedRegistration');
	}

	$tpl = eZTemplate::factory();

	$tpl->setVariable('ngconnect_user', $user);
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
	$http->removeSessionVariable('NGConnectUserID');
	$http->removeSessionVariable('NGConnectLoginMethod');
	$http->removeSessionVariable('NGConnectNetworkUserID');
	$http->removeSessionVariable('NGConnectNetworkEmail');

	redirect($http, $module);
}

function redirect($http, $module)
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