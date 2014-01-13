<?php

/** @var array $Params */
/** @var eZModule $module */

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$loginMethod = $Params['LoginMethod'];

$userID = eZUser::currentUserID();

$userConnections = ngConnect::connections( $userID );

foreach ( $userConnections as $userConnectionObject )
{
    if ( $userConnectionObject->LoginMethod == $loginMethod )
    {
        $userConnectionObject->remove();
    }
}

if ( $http->hasSessionVariable( 'LastAccessesURI' ) )
{
    return $module->redirectTo( $http->sessionVariable( 'LastAccessesURI' ) );
}
else
{
    return $module->redirectTo( '/' );
}

?>
