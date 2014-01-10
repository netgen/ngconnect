<?php

class ngConnectAuthGoogle implements INGConnectAuthInterface
{
    const SCOPE = 'email https://www.googleapis.com/auth/plus.login';
    const CALLBACK_URI_PART = '/ngconnect/callback/google';

    /**
     * This method is used to process the first part of authentication workflow, before redirect
     *
     * @return array Array with status and redirect URI
     */
    public function getRedirectUri()
    {
        $ngConnectINI = eZINI::instance( 'ngconnect.ini' );
        $http = eZHTTPTool::instance();

        $clientID = trim( $ngConnectINI->variable( 'LoginMethod_google', 'GoogleClientID' ) );
        if ( empty( $clientID ) )
        {
            return array( 'status' => 'error', 'message' => 'Google client ID undefined.' );
        }

        $callbackUri = self::CALLBACK_URI_PART;
        $loginWindowType = trim( $ngConnectINI->variable( 'ngconnect', 'LoginWindowType' ) );
        if ( $loginWindowType == 'popup' )
        {
            $callbackUri = '/layout/set/ngconnect' . self::CALLBACK_URI_PART;
        }

        eZURI::transformURI( $callbackUri, false, 'full' );

        $state = md5( session_id() . (string) time() );
        $http->setSessionVariable( 'NGConnectOAuthState', $state );

        $scope = self::SCOPE;
        $userScope = trim( $ngConnectINI->variable( 'LoginMethod_google', 'Scope' ) );
        if ( !empty( $userScope ) )
        {
            $scope = $userScope . ' ' . $scope;
        }

        $client = new Google_Client();
        $client->setApplicationName( trim( $ngConnectINI->variable( 'LoginMethod_google', 'MethodName' ) ) );
        $client->setScopes( $scope );

        $client->setClientId( $clientID );
        $client->setRedirectUri( $callbackUri );
        $client->setState( $state );

        return array( 'status' => 'success', 'redirect_uri' => $client->createAuthUrl() );
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

        $clientID = trim( $ngConnectINI->variable( 'LoginMethod_google', 'GoogleClientID' ) );
        $clientSecret = trim( $ngConnectINI->variable( 'LoginMethod_google', 'GoogleClientSecret' ) );

        if ( empty( $clientID ) || empty( $clientSecret ) )
        {
            return array( 'status' => 'error', 'message' => 'Google client ID or Google client secret undefined.' );
        }

        $code = trim( $http->getVariable( 'code', '' ) );
        $state = trim( $http->getVariable( 'state', '' ) );

        if ( empty( $code ) || empty( $state ) )
        {
            return array( 'status' => 'error', 'message' => 'code or state GET parameters undefined.' );
        }

        if( !$http->hasSessionVariable( 'NGConnectOAuthState' ) || $state != $http->sessionVariable( 'NGConnectOAuthState' ) )
        {
            $http->removeSessionVariable( 'NGConnectOAuthState' );
            return array( 'status' => 'error', 'message' => 'State parameter does not match stored value.' );
        }
        else
        {
            $http->removeSessionVariable('NGConnectOAuthState');
        }

        $callbackUri = self::CALLBACK_URI_PART;
        $loginWindowType = trim( $ngConnectINI->variable( 'ngconnect', 'LoginWindowType' ) );
        if ( $loginWindowType == 'popup' )
        {
            $callbackUri = '/layout/set/ngconnect' . self::CALLBACK_URI_PART;
        }

        eZURI::transformURI( $callbackUri, false, 'full' );

        $scope = self::SCOPE;
        $userScope = trim( $ngConnectINI->variable( 'LoginMethod_google', 'Scope' ) );
        if ( !empty( $userScope ) )
        {
            $scope = $userScope . ' ' . $scope;
        }

        $client = new Google_Client();
        $client->setApplicationName( trim( $ngConnectINI->variable( 'LoginMethod_google', 'MethodName' ) ) );
        $client->setScopes( $scope );

        $client->setClientId( $clientID );
        $client->setClientSecret( $clientSecret );
        $client->setRedirectUri( $callbackUri );
        $client->setUseObjects( true );

        $plus = new Google_PlusService( $client );
        $authString = $client->authenticate();
        if ( empty( $authString ) || empty( $client->getAccessToken() ) )
        {
            return array( 'status' => 'error', 'message' => 'Unable to authenticate to Google.' );
        }

        $me = $plus->people->get( 'me' );
        if ( !$me instanceof Google_Person )
        {
            return array( 'status' => 'error', 'message' => 'Invalid Google user.' );
        }

        $result = array(
            'status' => 'success',
            'login_method' => 'google',
            'id' => $me->id,
            'first_name' => !empty( $me->name->givenName ) ? $me->name->givenName : '',
            'last_name' => !empty( $me->name->familyName ) ? $me->name->familyName : '',
            'email' => !empty( $me->emails[0]['value'] ) ? $me->emails[0]['value'] : '',
            'picture' => !empty( $me->image->url ) ? $me->image->url : ''
        );

        return $result;
    }
}
