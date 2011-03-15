<?php

class ngConnectAuthTumblr implements INGConnectAuthInterface
{
	const CALLBACK_URI_PART = '/ngconnect/callback/tumblr';
	const TUMBLR_USER_API_URI = 'account/verify_credentials';

	public function getRedirectUri()
	{
		$ngConnectINI = eZINI::instance('ngconnect.ini');
		$http = eZHTTPTool::instance();

		$consumerKey = trim($ngConnectINI->variable('LoginMethod_tumblr', 'AppConsumerKey'));
		$consumerSecret = trim($ngConnectINI->variable('LoginMethod_tumblr', 'AppConsumerSecret'));

		if(!(strlen($consumerKey) > 0 && strlen($consumerSecret) > 0))
		{
			return array('status' => 'error', 'message' => 'Consumer key or consumer secret undefined.');
		}

		$callbackUri = self::CALLBACK_URI_PART;

		$loginWindowType = trim($ngConnectINI->variable('ngconnect', 'LoginWindowType'));
		if($loginWindowType == 'popup')
		{
			$callbackUri = '/layout/set/ngconnect' . self::CALLBACK_URI_PART;
		}

		$state = md5(session_id() . (string) time());
		$http->setSessionVariable('NGConnectOAuthState', $state);
		$callbackUri .= '?state=' . $state;
		eZURI::transformURI($callbackUri, false, 'full');

		$connection = new TumblrOAuth($consumerKey, $consumerSecret);
		$tempCredentials = $connection->getRequestToken($callbackUri);
		$redirectUri = $connection->getAuthorizeURL($tempCredentials);

		if(!$redirectUri)
		{
			return array('status' => 'error', 'message' => 'Invalid redirection URI.');
		}

		$http->setSessionVariable('NGConnectOAuthToken', $tempCredentials['oauth_token']);
		$http->setSessionVariable('NGConnectOAuthTokenSecret', $tempCredentials['oauth_token_secret']);
		return array('status' => 'success', 'redirect_uri' => $redirectUri);
	}

	public function processAuth()
	{
		$ngConnectINI = eZINI::instance('ngconnect.ini');
		$http = eZHTTPTool::instance();

		$consumerKey = trim($ngConnectINI->variable('LoginMethod_tumblr', 'AppConsumerKey'));
		$consumerSecret = trim($ngConnectINI->variable('LoginMethod_tumblr', 'AppConsumerSecret'));

		if(!(strlen($consumerKey) > 0 && strlen($consumerSecret) > 0))
		{
			return array('status' => 'error', 'message' => 'Consumer key or consumer secret undefined.');
		}

		if(!($http->hasGetVariable('oauth_token') && strlen(trim($http->getVariable('oauth_token'))) > 0
			&& $http->hasGetVariable('oauth_verifier') && strlen(trim($http->getVariable('oauth_verifier'))) > 0
			&& $http->hasGetVariable('state') && strlen(trim($http->getVariable('state'))) > 0))
		{
			return array('status' => 'error', 'message' => 'oauth_token, oauth_verifier or state GET parameters undefined.');
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

		$oAuthToken = trim($http->getVariable('oauth_token'));
		$oAuthVerifier = trim($http->getVariable('oauth_verifier'));

		if(!$http->hasSessionVariable('NGConnectOAuthToken') || !$http->hasSessionVariable('NGConnectOAuthTokenSecret')
			|| $oAuthToken != $http->sessionVariable('NGConnectOAuthToken'))
		{
			$http->removeSessionVariable('NGConnectOAuthToken');
			$http->removeSessionVariable('NGConnectOAuthTokenSecret');
			return array('status' => 'error', 'message' => 'Token does not match stored value.');
		}
		else
		{
			$oAuthTokenSecret = $http->sessionVariable('NGConnectOAuthTokenSecret');
			$http->removeSessionVariable('NGConnectOAuthToken');
			$http->removeSessionVariable('NGConnectOAuthTokenSecret');
		}

		$connection = new TumblrOAuth($consumerKey, $consumerSecret, $oAuthToken, $oAuthTokenSecret);
		$accessToken = $connection->getAccessToken($oAuthVerifier);
		if(!(isset($accessToken['oauth_token']) && isset($accessToken['oauth_token_secret'])))
		{
			return array('status' => 'error', 'message' => 'Error while retrieving access token.');
		}

		$connection = new TumblrOAuth($consumerKey, $consumerSecret, $accessToken['oauth_token'], $accessToken['oauth_token_secret']);
		$userXml = $connection->post('http://www.tumblr.com/api/authenticate');
		$userDom = new DOMDocument('1.0', 'utf-8');
		$userDom->loadXML($userXml);
		if(!($userXml && $userDom))
		{
			return array('status' => 'error', 'message' => 'Invalid Tumblr user.');
		}

		if($userDom->hasChildNodes() && $userDom->firstChild->hasChildNodes())
		{
			$tumbleLogList = $userDom->firstChild->getElementsByTagName('tumblelog');
			if($tumbleLogList->length > 0)
			{
				$tumbleLog = $tumbleLogList->item(0);

				if(!($tumbleLog->hasAttribute('name') && strlen($tumbleLog->getAttribute('name')) > 0))
				{
					return array('status' => 'error', 'message' => 'Invalid Tumblr user.');
				}
			}
		}

		$name = $tumbleLog->getAttribute('name');
		$result = array(
			'status'				=> 'success',
			'login_method'			=> 'tumblr',
			'id'					=> $name,
			'first_name'			=> $name,
			'last_name'				=> $name,
			'email'					=> '',
			'picture'				=> $tumbleLog->hasAttribute('avatar-url') ? $tumbleLog->getAttribute('avatar-url') : ''
		);

		return $result;
	}
}

?>