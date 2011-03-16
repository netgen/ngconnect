<?php

class ngConnectAuthFacebook implements INGConnectAuthInterface
{
	const AUTH_URI = 'https://www.facebook.com/dialog/oauth?display=%display%&client_id=%app_id%&redirect_uri=%site_url%&scope=%permissions%&state=%state%';
	const TOKEN_URI = 'https://graph.facebook.com/oauth/access_token?client_id=%app_id%&redirect_uri=%site_url%&client_secret=%app_secret%&code=%code%';
	const GRAPH_URI = 'https://graph.facebook.com/me?%access_token%';
	const PICTURE_URI = 'http://graph.facebook.com/%user_id%/picture';
	const CALLBACK_URI_PART = '/ngconnect/callback/facebook';

	public function getRedirectUri()
	{
		$ngConnectINI = eZINI::instance('ngconnect.ini');
		$http = eZHTTPTool::instance();

		$appID = trim($ngConnectINI->variable('LoginMethod_facebook', 'FacebookAppID'));

		if(strlen($appID) == 0)
		{
			return array('status' => 'error', 'message' => 'Facebook app ID undefined.');
		}

		$displayType = 'page';
		$callbackUri = self::CALLBACK_URI_PART;

		$loginWindowType = trim($ngConnectINI->variable('ngconnect', 'LoginWindowType'));
		if($loginWindowType == 'popup')
		{
			$displayType = 'popup';
			$callbackUri = '/layout/set/ngconnect' . self::CALLBACK_URI_PART;
		}
		eZURI::transformURI($callbackUri, false, 'full');

		$permissionsArray = $ngConnectINI->variable('LoginMethod_facebook', 'Permissions');
		$permissionsString = '';
		if(is_array($permissionsArray) && count($permissionsArray) > 0)
		{
			$permissionsString = implode(',', $permissionsArray);
		}

		$state = md5(session_id() . (string) time());
		$http->setSessionVariable('NGConnectOAuthState', $state);

		$redirectUri = str_replace(array('%display%', '%app_id%', '%site_url%', '%permissions%', '%state%'),
									array(urlencode($displayType), urlencode($appID), urlencode($callbackUri), urlencode($permissionsString), $state),
									self::AUTH_URI);
		return array('status' => 'success', 'redirect_uri' => $redirectUri);
	}

	public function processAuth()
	{
		$ngConnectINI = eZINI::instance('ngconnect.ini');
		$http = eZHTTPTool::instance();

		$appID = trim($ngConnectINI->variable('LoginMethod_facebook', 'FacebookAppID'));
		$appSecret = trim($ngConnectINI->variable('LoginMethod_facebook', 'FacebookAppSecret'));

		if(!(strlen($appID) > 0 && strlen($appSecret) > 0))
		{
			return array('status' => 'error', 'message' => 'Facebook app ID or Facebook app secret undefined.');
		}

		if(!($http->hasGetVariable('code') && strlen(trim($http->getVariable('code'))) > 0
			&& $http->hasGetVariable('state') && strlen(trim($http->getVariable('state'))) > 0))
		{
			return array('status' => 'error', 'message' => 'code or state GET parameters undefined.');
		}

		$state = trim($http->getVariable('state'));
		if(!$http->hasSessionVariable('NGConnectOAuthState') || $state != $http->sessionVariable('NGConnectOAuthState'))
		{
			$http->removeSessionVariable('NGConnectOAuthState');
			return array('status' => 'error', 'message' => 'State parameter does not match stored value.');
		}
		else
		{
			$http->removeSessionVariable('NGConnectOAuthState');
		}

		$code = trim($http->getVariable('code'));

		$callbackUri = self::CALLBACK_URI_PART;
		$loginWindowType = trim($ngConnectINI->variable('ngconnect', 'LoginWindowType'));
		if($loginWindowType == 'popup')
		{
			$callbackUri = '/layout/set/ngconnect' . self::CALLBACK_URI_PART;
		}
		eZURI::transformURI($callbackUri, false, 'full');

		$tokenUri = str_replace(array('%app_id%', '%site_url%', '%app_secret%', '%code%'),
								array(urlencode($appID), urlencode($callbackUri), urlencode($appSecret), urlencode($code)),
								self::TOKEN_URI);

		$accessToken = ngConnectFunctions::fetchDataFromUrl($tokenUri);
		if(!$accessToken)
		{
			return array('status' => 'error', 'message' => 'Error while retrieving access token.');
		}

		$accessTokenJson = json_decode($accessToken, true);
		if($accessTokenJson !== null)
		{
			return array('status' => 'error', 'message' => $accessTokenJson['error']['message']);
		}

		$graphUri = str_replace(array('%access_token%'),
								array(trim($accessToken)),
								self::GRAPH_URI);

		$graphResponse = ngConnectFunctions::fetchDataFromUrl($graphUri);
		if(!$graphResponse)
		{
			return array('status' => 'error', 'message' => 'Error while retrieving graph response.');
		}

		$user = json_decode($graphResponse, true);
		if($user === null)
		{
			return array('status' => 'error', 'message' => 'Invalid JSON data returned.');
		}

		if(!isset($user['id']))
		{
			return array('status' => 'error', 'message' => 'Invalid Facebook user.');
		}

		$pictureUri = self::PICTURE_URI;
		$imageSize = trim($ngConnectINI->variable('LoginMethod_facebook', 'ImageSize'));
		if($imageSize == 'original')
			$pictureUri = $pictureUri . '?type=large';

		$result = array(
			'status'				=> 'success',
			'login_method'			=> 'facebook',
			'id'					=> $user['id'],
			'first_name'			=> isset($user['first_name']) ? $user['first_name'] : '',
			'last_name'				=> isset($user['last_name']) ? $user['last_name'] : '',
			'email'					=> isset($user['email']) ? $user['email'] : '',
			'picture'				=> str_replace('%user_id%', $user['id'], $pictureUri)
		);

		return $result;
	}
}

?>