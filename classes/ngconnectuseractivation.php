<?php

class ngConnectUserActivation
{
	public static function processUserActivation($user, $password)
	{
		$ini = eZINI::instance();
		$mail = new eZMail();
		$object = $user->contentObject();
		$receiver = $user->attribute('email');

		$tpl = eZTemplate::factory();
		$tpl->setVariable('user', $user);
		$tpl->setVariable('object', $object);
		$hostname = eZSys::hostname();
		$tpl->setVariable('hostname', $hostname);
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
			$userID = $object->attribute('id');

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
			$templateResult = $tpl->fetch('design:user/registrationinfo.tpl');
			if($tpl->hasVariable('content_type'))
				$mail->setContentType($tpl->variable('content_type'));

			$emailSender = $ini->variable('MailSettings', 'EmailSender');
			if($tpl->hasVariable('email_sender'))
				$emailSender = $tpl->variable('email_sender');
			else if (!$emailSender)
				$emailSender = $ini->variable('MailSettings', 'AdminEmail');

			if($tpl->hasVariable('subject'))
				$subject = $tpl->variable('subject');
			else
				$subject = ezpI18n::tr('kernel/user/register', 'Registration info');

			$mail->setSender($emailSender);
			$mail->setReceiver($receiver);
			$mail->setSubject($subject);
			$mail->setBody($templateResult);
			$mailResult = eZMailTransport::send($mail);
		}

		$feedbackTypes = $ini->variableArray( 'UserSettings', 'RegistrationFeedback' );
		foreach ( $feedbackTypes as $feedbackType )
		{
			switch ( $feedbackType )
			{
				case 'email':
				{
					// send feedback with the default email type
					$mail = new eZMail();
					$tpl->resetVariables();
					$tpl->setVariable( 'user', $user );
					$tpl->setVariable( 'object', $object );
					$tpl->setVariable( 'hostname', $hostname );
					$templateResult = $tpl->fetch( 'design:user/registrationfeedback.tpl' );

					if ( $tpl->hasVariable( 'content_type' ) )
						$mail->setContentType( $tpl->variable( 'content_type' ) );

					$emailSender = $ini->variable( 'MailSettings', 'EmailSender' );
					if ( $tpl->hasVariable( 'email_sender' ) )
						$emailSender = $tpl->variable( 'email_sender' );
					else if ( !$emailSender )
						$emailSender = $ini->variable( 'MailSettings', 'AdminEmail' );

					$feedbackReceiver = $ini->variable( 'UserSettings', 'RegistrationEmail' );
					if ( $tpl->hasVariable( 'email_receiver' ) )
						$feedbackReceiver = $tpl->variable( 'email_receiver' );
					else if ( !$feedbackReceiver )
						$feedbackReceiver = $ini->variable( 'MailSettings', 'AdminEmail' );

					if ( $tpl->hasVariable( 'subject' ) )
						$subject = $tpl->variable( 'subject' );
					else
						$subject = ezpI18n::tr( 'kernel/user/register', 'New user registered' );

					$mail->setSender( $emailSender );
					$mail->setReceiver( $feedbackReceiver );
					$mail->setSubject( $subject );
					$mail->setBody( $templateResult );
					$mailResult = eZMailTransport::send( $mail );
				} break;
				default:
				{
					$registrationFeedbackClass = false;
					// load custom registration feedback settings
					if ( $ini->hasGroup( 'RegistrationFeedback_' . $feedbackType ) )
					{
						if ( $ini->hasVariable( 'RegistrationFeedback_' . $feedbackType, 'File' ) )
							include_once( $ini->variable( 'RegistrationFeedback_' . $feedbackType, 'File' ) );
						$registrationFeedbackClass = $ini->variable( 'RegistrationFeedback_' . $feedbackType, 'Class' );
					}
					// try to call the registration feedback class with function registrationFeedback
					if ( $registrationFeedbackClass && method_exists( $registrationFeedbackClass, 'registrationFeedback' ) )
						call_user_func( array( $registrationFeedbackClass, 'registrationFeedback' ), $user, $tpl, $object, $hostname );
					else
						eZDebug::writeWarning( "Unknown feedback type '$feedbackType'", 'ngconnect/profile' );
				}
			}
		}
	}

	public static function validateUserInput($http)
	{
		return array('status' => 'success');
	}
}

?>