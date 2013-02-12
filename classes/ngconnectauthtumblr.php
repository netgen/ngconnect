<?php

class ngConnectAuthTumblr implements INGConnectAuthInterface
{
    const CALLBACK_URI_PART = '/ngconnect/callback/tumblr';
    const TUMBLR_USER_API_URI = 'account/verify_credentials';

    /**
     * This method is used to process the first part of authentication workflow, before redirect
     *
     * @return array Array with status and redirect URI
     */
    public function getRedirectUri()
    {
        $ngConnectINI = eZINI::instance( 'ngconnect.ini' );
        $http = eZHTTPTool::instance();

        $consumerKey = trim( $ngConnectINI->variable( 'LoginMethod_tumblr', 'AppConsumerKey' ) );
        $consumerSecret = trim( $ngConnectINI->variable( 'LoginMethod_tumblr', 'AppConsumerSecret' ) );

        if ( empty( $consumerKey ) || empty( $consumerSecret ) )
        {
            return array( 'status' => 'error', 'message' => 'Consumer key or consumer secret undefined.' );
        }

        $callbackUri = self::CALLBACK_URI_PART;

        $loginWindowType = trim( $ngConnectINI->variable( 'ngconnect', 'LoginWindowType' ) );
        if ( $loginWindowType == 'popup' )
        {
            $callbackUri = '/layout/set/ngconnect' . self::CALLBACK_URI_PART;
        }

        $state = md5( session_id() . (string) time() );
        $http->setSessionVariable( 'NGConnectOAuthState', $state );
        $callbackUri .= '?state=' . $state;
        eZURI::transformURI( $callbackUri, false, 'full' );

        $connection = new TumblrOAuth( $consumerKey, $consumerSecret );
        $tempCredentials = $connection->getRequestToken( $callbackUri );
        $redirectUri = $connection->getAuthorizeURL( $tempCredentials );

        if ( !$redirectUri )
        {
            return array( 'status' => 'error', 'message' => 'Invalid redirection URI.' );
        }

        $http->setSessionVariable( 'NGConnectOAuthToken', $tempCredentials['oauth_token'] );
        $http->setSessionVariable( 'NGConnectOAuthTokenSecret', $tempCredentials['oauth_token_secret'] );
        return array( 'status' => 'success', 'redirect_uri' => $redirectUri );
    }

    /**
     * This method is used to process the second part of authentication workflow, after redirect
     *
     * @return array Array with status and user details
     */
    public function processAuth()
    {
        $ngConnectINI = eZINI::instance( 'ngconnect.ini' );
        $http = eZHTTPTool::instance();

        $consumerKey = trim( $ngConnectINI->variable( 'LoginMethod_tumblr', 'AppConsumerKey' ) );
        $consumerSecret = trim( $ngConnectINI->variable( 'LoginMethod_tumblr', 'AppConsumerSecret' ) );

        if ( empty( $consumerKey ) || empty( $consumerSecret ) )
        {
            return array( 'status' => 'error', 'message' => 'Consumer key or consumer secret undefined.' );
        }

        $oAuthToken = trim( $http->getVariable( 'oauth_token', '' ) );
        $oAuthVerifier = trim( $http->getVariable( 'oauth_verifier', '' ) );
        $state = trim( $http->getVariable( 'state', '' ) );

        if ( empty( $oAuthToken ) || empty( $oAuthVerifier ) || empty( $state ) )
        {
            return array( 'status' => 'error', 'message' => 'oauth_token, oauth_verifier or state GET parameters undefined.' );
        }

        if ( !$http->hasSessionVariable( 'NGConnectOAuthState' ) || $state != $http->sessionVariable( 'NGConnectOAuthState' ) )
        {
            $http->removeSessionVariable( 'NGConnectOAuthState' );
            return array( 'status' => 'error', 'message' => 'State parameter does not match stored value.' );
        }
        else
        {
            $http->removeSessionVariable( 'NGConnectOAuthState' );
        }

        if ( !$http->hasSessionVariable( 'NGConnectOAuthToken' ) || !$http->hasSessionVariable( 'NGConnectOAuthTokenSecret' )
            || $oAuthToken != $http->sessionVariable( 'NGConnectOAuthToken' ) )
        {
            $http->removeSessionVariable( 'NGConnectOAuthToken' );
            $http->removeSessionVariable( 'NGConnectOAuthTokenSecret' );
            return array( 'status' => 'error', 'message' => 'Token does not match stored value.' );
        }
        else
        {
            $oAuthTokenSecret = $http->sessionVariable( 'NGConnectOAuthTokenSecret' );
            $http->removeSessionVariable( 'NGConnectOAuthToken' );
            $http->removeSessionVariable( 'NGConnectOAuthTokenSecret' );
        }

        $connection = new TumblrOAuth( $consumerKey, $consumerSecret, $oAuthToken, $oAuthTokenSecret );
        $accessToken = $connection->getAccessToken( $oAuthVerifier );
        if ( !( isset( $accessToken['oauth_token'] ) && isset( $accessToken['oauth_token_secret'] ) ) )
        {
            return array( 'status' => 'error', 'message' => 'Error while retrieving access token.' );
        }

        $connection = new TumblrOAuth( $consumerKey, $consumerSecret, $accessToken['oauth_token'], $accessToken['oauth_token_secret'] );
        $userData = $connection->get( 'http://api.tumblr.com/v2/user/info' );

        $userData = json_decode( $userData, true );
        if ( !is_array( $userData ) || empty( $userData['response']['user']['name'] ) )
        {
            return array( 'status' => 'error', 'message' => 'Invalid Tumblr user.' );
        }

        $name = $userData['response']['user']['name'];
        $result = array(
            'status' => 'success',
            'login_method' => 'tumblr',
            'id' => $name,
            'first_name' => $name,
            'last_name' => $name,
            'email' => '',
            'picture' => ''
        );

        return $result;
    }
}
