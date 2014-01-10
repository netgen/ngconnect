<?php

/** @var array $Params */
/** @var eZModule $module */

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$loginMethod = $Params['LoginMethod'];

$userID = eZUser::currentUserID();

$unlinkedArray = array();

$userConnections = ngConnect::connections( $userID );

foreach ( $userConnections as $userConnectionObject )
{
    if ( $userConnectionObject->LoginMethod == $loginMethod )
    {
        $unlinkedArray[] = $loginMethod;

        $userConnectionObject->remove();
    }
}

if ( $http->hasSessionVariable( 'NGConnectLastAccessURI' ) )
{
    return $module->redirectTo( '/user/edit' );
}

?>
