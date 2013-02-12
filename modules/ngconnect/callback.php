<?php

/** @var array $Params */
/** @var eZModule $module */

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$loginMethod = $Params['LoginMethod'];

$ngConnectINI = eZINI::instance( 'ngconnect.ini' );
$availableLoginMethods = $ngConnectINI->variable( 'ngconnect', 'LoginMethods' );
$authHandlerClasses = $ngConnectINI->variable( 'ngconnect', 'AuthHandlerClasses' );
$loginWindowType = trim( $ngConnectINI->variable( 'ngconnect', 'LoginWindowType' ) );
$debugEnabled = ( trim( $ngConnectINI->variable( 'ngconnect', 'DebugEnabled' ) ) == 'true' );
$regularRegistration = trim( $ngConnectINI->variable( 'ngconnect', 'RegularRegistration' ) ) == 'enabled';

//we don't allow ngconnect/profile to run by default
$http->removeSessionVariable( 'NGConnectRedirectToProfile' );
$http->removeSessionVariable( 'NGConnectAuthResult' );
$http->removeSessionVariable( 'NGConnectForceRedirect' );

if ( function_exists( 'curl_init' ) && function_exists( 'json_decode' ) )
{
    if ( in_array( $loginMethod, $availableLoginMethods ) && isset( $authHandlerClasses[$loginMethod] ) && class_exists( trim( $authHandlerClasses[$loginMethod] ) ) )
    {
        $authHandlerClassName = trim( $authHandlerClasses[$loginMethod] );
        $authHandler = new $authHandlerClassName();
        if ( $authHandler instanceof INGConnectAuthInterface )
        {
            $result = $authHandler->processAuth();
            if ( $result['status'] == 'success' && $result['login_method'] == $loginMethod )
            {
                $currentUser = eZUser::currentUser();
                if ( !$currentUser->isAnonymous() )
                {
                    // non anonymous user is requesting connection to social network
                    // who are we to say no? connect the user and bail out
                    ngConnectFunctions::connectUser( $currentUser->ContentObjectID, $result['login_method'], $result['id'] );
                }
                else
                {
                    // we check if there are accounts that have a connection to social network
                    // we consider a disabled account as connected too, to allow admins to disable them and actually
                    // keep users from logging to a new account with same social network account
                    $socialNetworkConnections = ngConnect::fetchBySocialNetwork( $result['login_method'], $result['id'] );
                    if ( is_array( $socialNetworkConnections ) && !empty( $socialNetworkConnections ) )
                    {
                        // there are connected accounts, find them and login in
                        $usersFound = array();
                        $userIDs = array();
                        foreach ( $socialNetworkConnections as $connection )
                        {
                            $userToLogin = eZUser::fetch( $connection->UserID );
                            if ( $userToLogin instanceof eZUser && $userToLogin->isEnabled()
                                 && $userToLogin->canLoginToSiteAccess( $GLOBALS['eZCurrentAccess'] )
                            )
                            {
                                $usersFound[] = $userToLogin;
                                $userIDs[] = $userToLogin->ContentObjectID;
                            }
                        }

                        if ( !empty( $usersFound ) )
                        {
                            $usersFound[0]->loginCurrent();
                            array_shift( $userIDs );
                            //TODO: Enable user to choose which account to login to
                            //if more than one is found connected to provided social
                            //network account
                        }
                        else
                        {
                            eZUser::logoutCurrent();
                        }
                    }
                    else
                    {
                        // no previously connected accounts, try to find existing social network account
                        $user = eZUser::fetchByName( 'ngconnect_' . $result['login_method'] . '_' . $result['id'] );
                        if ( $user instanceof eZUser )
                        {
                            if ( $user->isEnabled() && $user->canLoginToSiteAccess( $GLOBALS['eZCurrentAccess'] ) )
                            {
                                ngConnectFunctions::updateUser( $user, $result );
                                $user->loginCurrent();
                            }
                            else
                            {
                                eZUser::logoutCurrent();
                            }
                        }
                        else
                        {
                            // we didn't find any social network accounts, create new account
                            // redirect to ngconnect/profile if enabled
                            $forceRedirect = false;
                            if ( eZUser::requireUniqueEmail() && eZUser::fetchByEmail( $result['email'] ) instanceof eZUser
                                 && trim( $ngConnectINI->variable( 'ngconnect', 'DuplicateEmailForceRedirect' ) ) == 'enabled'
                            )
                            {
                                $forceRedirect = true;
                            }

                            if ( $regularRegistration || $forceRedirect )
                            {
                                if ( !$regularRegistration && $forceRedirect )
                                    $http->setSessionVariable( 'NGConnectForceRedirect', 'true' );

                                $http->setSessionVariable( 'NGConnectAuthResult', $result );

                                if ( $loginWindowType == 'page' )
                                    return $module->redirectToView( 'profile' );
                                else
                                    $http->setSessionVariable( 'NGConnectRedirectToProfile', 'true' );
                            }
                            else
                            {
                                $user = ngConnectFunctions::createUser( $result );
                                if ( $user instanceof eZUser && $user->canLoginToSiteAccess( $GLOBALS['eZCurrentAccess'] ) )
                                {
                                    $user->loginCurrent();
                                }
                                else
                                {
                                    eZUser::logoutCurrent();
                                }
                            }
                        }
                    }
                }
            }
            else if ( $debugEnabled && isset( $result['message'] ) )
            {
                eZDebug::writeError( $result['message'], 'ngconnect/callback' );
            }
            else if ( $debugEnabled )
            {
                eZDebug::writeError( 'Unknown error', 'ngconnect/callback' );
            }
        }
        else if ( $debugEnabled )
        {
            eZDebug::writeError( 'Invalid auth handler class: ' . $authHandlerClassName, 'ngconnect/callback' );
        }
    }
    else if ( $debugEnabled )
    {
        eZDebug::writeError( 'Invalid login method specified.', 'ngconnect/callback' );
    }
}
else if ( $debugEnabled )
{
    eZDebug::writeError( 'Netgen Connect requires CURL & JSON PHP extensions to work.', 'ngconnect/callback' );
}

if ( $loginWindowType != 'popup' )
{
    if ( $http->hasSessionVariable( 'NGConnectLastAccessURI' ) )
    {
        return $module->redirectTo( $http->sessionVariable( 'NGConnectLastAccessURI' ) );
    }
    else
    {
        return $module->redirectTo( '/' );
    }
}
