<?php

class ngConnectUserActivation
{
	public static function processUserActivation($user, $password)
	{
		$ini = eZINI::instance();
		$tpl = eZTemplate::factory();

		$tpl->setVariable('user', $user);
		$tpl->setVariable('object', $user->contentObject());
		$tpl->setVariable('hostname', eZSys::hostname());
		$tpl->setVariable('password', $password);

		// Check whether account activation is required.
		$verifyUserType = $ini->variable('UserSettings', 'VerifyUserType');
		$sendUserMail = !!$verifyUserType;
		// For compatibility with old setting
		if($verifyUserType === 'email'
			&& $ini->hasVariable('UserSettings', 'VerifyUserEmail')
			&& $ini->variable('UserSettings', 'VerifyUserEmail') !== 'enabled')
		{
			$verifyUserType = false;
		}

		if($verifyUserType === 'email') // and if it is email type
		{
			// Disable user account and send verification mail to the user
			$userID = $user->attribute('contentobject_id');

			// Create enable account hash and send it to the newly registered user
			$hash = md5(mt_rand() . time() . $userID);

			if(eZOperationHandler::operationIsAvailable('user_activation'))
			{
				$operationResult = eZOperationHandler::execute('user', 'activation', array('user_id' => $userID, 'user_hash' => $hash, 'is_enabled' => false));
			}
			else
			{
				eZUserOperationCollection::activation($userID, $hash, false);
			}

			// Log out current user
			eZUser::logoutCurrent();

			$tpl->setVariable('hash', $hash);

			$sendUserMail = true;
		}
		else if($verifyUserType) // custom account activation
		{
			$verifyUserTypeClass = false;
			// load custom verify user settings
			if($ini->hasGroup('VerifyUserType_' . $verifyUserType))
			{
				if($ini->hasVariable('VerifyUserType_' . $verifyUserType, 'File'))
					include_once($ini->variable('VerifyUserType_' . $verifyUserType, 'File'));
				$verifyUserTypeClass = $ini->variable('VerifyUserType_' . $verifyUserType, 'Class');
			}
			// try to call the verify user class with function verifyUser
			if($verifyUserTypeClass && method_exists($verifyUserTypeClass, 'verifyUser'))
				$sendUserMail = call_user_func(array($verifyUserTypeClass, 'verifyUser'), $user, $tpl);
			else
				eZDebug::writeWarning("Unknown VerifyUserType '$verifyUserType'", 'ngconnect/profile');
		}

		// send verification mail to user if email type or custum verify user type returned true
		if($sendUserMail)
		{
			$mail = new eZMail();
			$templateResult = $tpl->fetch('design:user/registrationinfo.tpl');

			if($tpl->hasVariable('content_type'))
				$mail->setContentType($tpl->variable('content_type'));

			$emailSender = $ini->variable('MailSettings', 'EmailSender');
			if($tpl->hasVariable('email_sender'))
				$emailSender = $tpl->variable('email_sender');
			else if (!$emailSender)
				$emailSender = $ini->variable('MailSettings', 'AdminEmail');
			$mail->setSender($emailSender);

			if($tpl->hasVariable('subject'))
				$subject = $tpl->variable('subject');
			else
				$subject = ezpI18n::tr('kernel/user/register', 'Registration info');
			$mail->setSubject($subject);

			$mail->setReceiver($user->attribute('email'));

			$mail->setBody($templateResult);
			$mailResult = eZMailTransport::send($mail);
		}
	}

	public static function validateUserInput($http)
	{
		if ( $http->hasPostVariable( 'data_user_login' ) &&
			 $http->hasPostVariable( 'data_user_email' ) &&
			 $http->hasPostVariable( 'data_user_password' ) &&
			 $http->hasPostVariable( 'data_user_password_confirm' ) )
		{
			$loginName = $http->postVariable( 'data_user_login' );
			$email = $http->postVariable( 'data_user_email' );
			$password = $http->postVariable( 'data_user_password' );
			$passwordConfirm = $http->postVariable( 'data_user_password_confirm' );
			if ( trim( $loginName ) == '' )
			{
				return array('status' => 'error', 'message' => ezpI18n::tr( 'kernel/classes/datatypes', 'The username must be specified.' ));
			}
			else
			{
				$existUser = eZUser::fetchByName( $loginName );
				if ( $existUser != null )
				{
					return array('status' => 'error', 'message' => ezpI18n::tr( 'kernel/classes/datatypes', 'The username already exists, please choose another one.' ));
				}
				// validate user email
				$isValidate = eZMail::validate( $email );
				if ( !$isValidate )
				{
					return array('status' => 'error', 'message' => ezpI18n::tr( 'kernel/classes/datatypes', 'The email address is not valid.' ));
				}
				$authenticationMatch = eZUser::authenticationMatch();
				if ( $authenticationMatch & eZUser::AUTHENTICATE_EMAIL )
				{
					if ( eZUser::requireUniqueEmail() )
					{
						$userByEmail = eZUser::fetchByEmail( $email );
						if ( $userByEmail != null )
						{
							return array('status' => 'error', 'message' => ezpI18n::tr( 'kernel/classes/datatypes', 'A user with this email already exists.' ));
						}
					}
				}
				// validate user name
				if ( !eZUser::validateLoginName( $loginName, $errorText ) )
				{
					return array('status' => 'error', 'message' => ezpI18n::tr( 'kernel/classes/datatypes', $errorText ));
				}
				// validate user password
				$ini = eZINI::instance();
				$generatePasswordIfEmpty = $ini->variable( "UserSettings", "GeneratePasswordIfEmpty" ) == 'true';
				if ( !$generatePasswordIfEmpty || ( $password != "" ) )
				{
					if ( $password == "" )
					{
						return array('status' => 'error', 'message' => ezpI18n::tr( 'kernel/classes/datatypes', 'The password cannot be empty.', 'eZUserType' ));
					}
					if ( $password != $passwordConfirm )
					{
						return array('status' => 'error', 'message' => ezpI18n::tr( 'kernel/classes/datatypes', 'The passwords do not match.', 'eZUserType' ));
					}
					if ( !eZUser::validatePassword( $password ) )
					{
						$minPasswordLength = $ini->hasVariable( 'UserSettings', 'MinPasswordLength' ) ? $ini->variable( 'UserSettings', 'MinPasswordLength' ) : 3;
						return array('status' => 'error', 'message' => ezpI18n::tr( 'kernel/classes/datatypes', 'The password must be at least %1 characters long.', null, array( $minPasswordLength ) ));
					}
					if ( strtolower( $password ) == 'password' )
					{
						return array('status' => 'error', 'message' => ezpI18n::tr( 'kernel/classes/datatypes', 'The password must not be "password".' ));
					}
				}
			}
		}
		else
		{
			return array('status' => 'error', 'message' => ezpI18n::tr( 'kernel/classes/datatypes', 'Input required.' ));
		}

		return array('status' => 'success');
	}
}

?>