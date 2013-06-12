<?php

class ngConnectAuthTwitter implements INGConnectAuthInterface
{
    const CALLBACK_URI_PART = '/ngconnect/callback/twitter';
    const TWITTER_USER_API_URI = 'account/verify_credentials';

    /**
     * This method is used to process the first part of authentication workflow, before redirect
     *
     * @return array Array with status and redirect URI
     */
    public function getRedirectUri()
    {
        $ngConnectINI = eZINI::instance( 'ngconnect.ini' );
        $http = eZHTTPTool::instance();

        $consumerKey = trim( $ngConnectINI->variable( 'LoginMethod_twitter', 'AppConsumerKey' ) );
        $consumerSecret = trim( $ngConnectINI->variable( 'LoginMethod_twitter', 'AppConsumerSecret' ) );

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

        $connection = new TwitterOAuth( $consumerKey, $consumerSecret );
        $connection->host = "https://api.twitter.com/1.1/";
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

        $consumerKey = trim( $ngConnectINI->variable( 'LoginMethod_twitter', 'AppConsumerKey' ) );
        $consumerSecret = trim( $ngConnectINI->variable( 'LoginMethod_twitter', 'AppConsumerSecret' ) );

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

        $connection = new TwitterOAuth( $consumerKey, $consumerSecret, $oAuthToken, $oAuthTokenSecret );
        $connection->host = "https://api.twitter.com/1.1/";
        $accessToken = $connection->getAccessToken( $oAuthVerifier );
        if ( !( isset( $accessToken['oauth_token'] ) && isset( $accessToken['oauth_token_secret'] ) ) )
        {
            return array( 'status' => 'error', 'message' => 'Error while retrieving access token.' );
        }

        $connection = new TwitterOAuth( $consumerKey, $consumerSecret, $accessToken['oauth_token'], $accessToken['oauth_token_secret'] );
        $connection->host = "https://api.twitter.com/1.1/";
        $user = $connection->get( self::TWITTER_USER_API_URI );
        if ( !isset( $user->id ) || empty( $user->id ) )
        {
            return array( 'status' => 'error', 'message' => 'Invalid Twitter user.' );
        }

        if ( isset( $user->profile_image_url ) && !empty( $user->profile_image_url ) )
        {
            $pictureUri = $user->profile_image_url;
            $imageSize = trim( $ngConnectINI->variable( 'LoginMethod_facebook', 'ImageSize' ) );
            if ( $imageSize == 'original' )
            {
                //Hm... it seems there's no way to get the full size image through API
                //Even https://api.twitter.com/1/users/profile_image/username never returns full version
                //Replacing is not safe, but at least we're replacing last occurrence
                $pictureUri = substr_replace( $user->profile_image_url, '', strrpos( $user->profile_image_url, '_normal' ), 7 );
            }
        }
        else
        {
            $pictureUri = '';
        }

        $result = array(
            'status' => 'success',
            'login_method' => 'twitter',
            'id' => $user->id,
            'first_name' => isset( $user->name ) ? $user->name : '',
            'last_name' => '',
            'email' => '',
            'picture' => $pictureUri
        );

        return $result;
    }
}
