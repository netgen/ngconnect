<?php

class ngConnect extends eZPersistentObject
{
    /**
     * Constructor
     *
     * @param array $row
     */
    function __construct( $row )
    {
        parent::__construct( $row );
    }

    /**
     * Returns the definition of ngconnect table
     *
     * @return array
     */
    static function definition()
    {
        return array(
            'fields' => array(
                'user_id' => array(
                    'name' => 'UserID',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true
                ),
                'login_method' => array(
                    'name' => 'LoginMethod',
                    'datatype' => 'string',
                    'default' => '',
                    'required' => true
                ),
                'network_user_id' => array(
                    'name' => 'NetworkUserID',
                    'datatype' => 'string',
                    'default' => '',
                    'required' => true
                )
            ),
            'keys' => array( 'user_id', 'login_method', 'network_user_id' ),
            'class_name' => 'ngConnect',
            'sort' => array( 'user_id' => 'asc', 'login_method' => 'asc', 'network_user_id' => 'asc' ),
            'name' => 'ngconnect'
        );
    }

    /**
     * Returns the connection from eZ Publish user to social network user
     *
     * @param int $userID
     * @param string $loginMethod
     * @param int $networkUserID
     *
     * @return ngConnect
     */
    static function fetch( $userID, $loginMethod, $networkUserID )
    {
        return eZPersistentObject::fetchObject(
            self::definition(),
            null,
            array(
                'user_id' => $userID,
                'login_method' => $loginMethod,
                'network_user_id' => $networkUserID
            )
        );
    }

    /**
     * Returns the connection for social network user
     *
     * @param $loginMethod
     * @param $networkUserID
     *
     * @return array
     */
    static function fetchBySocialNetwork( $loginMethod, $networkUserID )
    {
        $result = eZPersistentObject::fetchObjectList(
            self::definition(),
            null,
            array(
                'login_method' => $loginMethod,
                'network_user_id' => $networkUserID
            )
        );

        if ( is_array( $result ) && !empty( $result ) )
            return $result;

        return array();
    }

    /**
     * Returns if eZ Publish user has connection to specified social network
     *
     * @param int $userID
     * @param string $loginMethod
     *
     * @return bool
     */
    static function userHasConnection( $userID, $loginMethod )
    {
        $count = eZPersistentObject::count(
            self::definition(),
            array(
                'user_id' => $userID,
                'login_method' => $loginMethod
            )
        );

        if ( $count > 0 )
            return true;

        $user = eZUser::fetch( $userID );
        if ( substr( $user->Login, 0, 10 + strlen( $loginMethod ) ) === 'ngconnect_' . $loginMethod )
            return true;

        return false;
    }

    /**
     * Returns all of eZ Publish users connections to social networks
     *
     * @param int $userID
     *
     * @return bool
     */
    static function connections( $userID )
    {
        $result = eZPersistentObject::fetchObjectList(
            self::definition(),
            null,
            array(
                'user_id' => $userID
            )
        );

        if ( is_array( $result ) && !empty( $result ) )
            return $result;

        return array();
    }
}
