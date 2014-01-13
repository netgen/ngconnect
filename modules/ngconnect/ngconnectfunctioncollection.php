<?php

class ngConnectFunctionCollection
{
    /**
     * Returns if username is generated or not
     *
     * @return array
     */
    static public function usernameIsGenerated()
    {
        $currentUser = eZUser::currentUser();
        if ( $currentUser instanceof eZUser )
        {
            $pos = strpos( $currentUser->Login, "ngconnect_", 0 );
            if ( $pos !== false && $pos === 0 )
            {
                return array( 'result' => true );
            }
        }

        return array( 'result' => false );
    }

    /**
     * Returns if email is generated or not
     *
     * @return array
     */
    static public function emailIsGenerated()
    {
        $currentUser = eZUser::currentUser();
        if ( $currentUser instanceof eZUser )
        {
            $pos = strpos( $currentUser->Login, "ngconnect_", 0 );
            if ( $pos !== false && $pos === 0 )
            {
                if ( strpos( $currentUser->Email, "@localhost.local", 0 ) === strlen( $currentUser->Email ) - 16 )
                {
                    return array( 'result' => true );
                }
            }
        }

        return array( 'result' => false );
    }

    /**
     * Returns if user has a connection to social network
     *
     * @param int $userID
     * @param string $loginMethod
     *
     * @return array
     */
    static public function userHasConnection( $userID, $loginMethod )
    {
        return array( 'result' => ngConnect::userHasConnection( $userID, $loginMethod ) );
    }

    /**
     * Returns all of a users connections to social networks
     *
     * @param int $userID
     *
     * @return array
     */
    static public function connections( $userID )
    {
        return array( 'result' => ngConnect::connections( $userID ) );
    }
}
