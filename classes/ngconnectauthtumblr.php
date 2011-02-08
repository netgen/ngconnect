<?php

class ngConnectAuthTumblr extends ngConnectAuthBase
{
	const CALLBACK_URI_PART = '/ngconnect/callback/tumblr';
	const TUMBLR_USER_API_URI = 'account/verify_credentials';

	public function getRedirectUri()
	{
		$ngConnectINI = eZINI::instance('ngconnect.ini');
		$http = eZHTTPTool::instance();

		$consumerKey = trim($ngConnectINI->variable('LoginMethod_tumblr', 'AppConsumerKey'));
		$consumerSecret = trim($ngConnectINI->variable('LoginMethod_tumblr', 'AppConsumerSecret'));
		$siteURL = trim($ngConnectINI->variable('ngconnect', 'SiteURL'));

		if(!(strlen($consumerKey) > 0 && strlen($consumerSecret) > 0 && strlen($siteURL) > 0))
		{
			return array('status' => 'error', 'message' => 'Consumer key, consumer secret or site URL undefined.');
		}

		$callbackUri = $siteURL . self::CALLBACK_URI_PART;

		$loginWindowType = trim($ngConnectINI->variable('ngconnect', 'LoginWindowType'));
		if($loginWindowType == 'popup')
		{
			$callbackUri = $siteURL . '/layout/set/ngconnect' . self::CALLBACK_URI_PART;
		}

		$state = md5(session_id() . (string) time());
		$http->setSessionVariable('OAuthState', $state);
		$callbackUri .= '?state=' . $state;

		$connection = new TumblrOAuth($consumerKey, $consumerSecret);
		$tempCredentials = $connection->getRequestToken($callbackUri);
		$redirectUri = $connection->getAuthorizeURL($tempCredentials);

		if(!$redirectUri)
		{
			return array('status' => 'error', 'message' => 'Invalid redirection URI.');
		}

		$http->setSessionVariable('OAuthToken', $tempCredentials['oauth_token']);
		$http->setSessionVariable('OAuthTokenSecret', $tempCredentials['oauth_token_secret']);
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
		if(!$http->hasSessionVariable('OAuthState') || $state != $http->sessionVariable('OAuthState'))
		{
			$http->removeSessionVariable('OAuthState');
			return array('status' => 'error', 'message' => 'State parameter does not match stored value.');
		}
		else
		{
			$http->removeSessionVariable('OAuthState');
		}

		$oAuthToken = trim($http->getVariable('oauth_token'));
		$oAuthVerifier = trim($http->getVariable('oauth_verifier'));

		if(!$http->hasSessionVariable('OAuthToken') || !$http->hasSessionVariable('OAuthTokenSecret')
			|| $oAuthToken != $http->sessionVariable('OAuthToken'))
		{
			$http->removeSessionVariable('OAuthToken');
			$http->removeSessionVariable('OAuthTokenSecret');
			return array('status' => 'error', 'message' => 'Token does not match stored value.');
		}
		else
		{
			$oAuthTokenSecret = $http->sessionVariable('OAuthTokenSecret');
			$http->removeSessionVariable('OAuthToken');
			$http->removeSessionVariable('OAuthTokenSecret');
		}

		$connection = new TumblrOAuth($consumerKey, $consumerSecret, $oAuthToken, $oAuthTokenSecret);
		$accessToken = $connection->getAccessToken($oAuthVerifier);
		if(!(isset($accessToken['oauth_token']) && isset($accessToken['oauth_token_secret'])))
		{
			return array('status' => 'error', 'message' => 'Error while retrieving access token.');
		}

		$connection = new TumblrOAuth($consumerKey, $consumerSecret, $accessToken['oauth_token'], $accessToken['oauth_token_secret']);
		$userXml = $connection->post('http://www.tumblr.com/api/authenticate');
		$userDom = DOMDocument::loadXML($userXml);
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
			'status'		=> 'success',
			'id'			=> $name,
			'first_name'	=> $name,
			'last_name'		=> $name,
			'email'			=> '',
			'picture'		=> $tumbleLog->hasAttribute('avatar-url') ? $tumbleLog->getAttribute('avatar-url') : ''
		);

		return $result;
	}
}

?>