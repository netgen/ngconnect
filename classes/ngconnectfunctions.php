<?php

class ngConnectFunctions
{
    /**
     * Creates a user with provided auth data
     *
     * @param array $authResult
     *
     * @return bool|eZUser
     */
    public static function createUser( $authResult )
    {
        $ngConnectINI = eZINI::instance( 'ngconnect.ini' );
        $siteINI = eZINI::instance( 'site.ini' );

        $defaultUserPlacement = $ngConnectINI->variable( 'LoginMethod_' . $authResult['login_method'], 'DefaultUserPlacement' );
        $placementNode = eZContentObjectTreeNode::fetch( $defaultUserPlacement );

        if ( !$placementNode instanceof eZContentObjectTreeNode )
        {
            $defaultUserPlacement = $siteINI->variable( 'UserSettings', 'DefaultUserPlacement' );
            $placementNode = eZContentObjectTreeNode::fetch( $defaultUserPlacement );

            if ( !$placementNode instanceof eZContentObjectTreeNode )
            {
                return false;
            }
        }

        $contentClass = eZContentClass::fetch( $siteINI->variable( 'UserSettings', 'UserClassID' ) );
        $userCreatorID = $siteINI->variable( 'UserSettings', 'UserCreatorID' );
        $defaultSectionID = $siteINI->variable( 'UserSettings', 'DefaultSectionID' );

        $db = eZDB::instance();
        $db->begin();

        $contentObject = $contentClass->instantiate( $userCreatorID, $defaultSectionID );
        $contentObject->store();

        $nodeAssignment = eZNodeAssignment::create(
            array(
                'contentobject_id' => $contentObject->attribute( 'id' ),
                'contentobject_version' => 1,
                'parent_node' => $placementNode->attribute( 'node_id' ),
                'is_main' => 1
            )
        );
        $nodeAssignment->store();

        $currentTimeStamp = eZDateTime::currentTimeStamp();

        /** @var eZContentObjectVersion $version */
        $version = $contentObject->currentVersion();
        $version->setAttribute( 'modified', $currentTimeStamp );
        $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
        $version->store();

        $dataMap = $version->dataMap();
        self::fillUserObject( $version->dataMap(), $authResult );

        if ( !isset( $dataMap['user_account'] ) )
        {
            $db->rollback();
            return false;
        }

        $userLogin = 'ngconnect_' . $authResult['login_method'] . '_' . $authResult['id'];
        $userPassword = (string) rand() . 'ngconnect_' . $authResult['login_method'] . '_' . $authResult['id'] . (string) rand();

        $userExists = false;
        if ( eZUser::requireUniqueEmail() )
            $userExists = eZUser::fetchByEmail( $authResult['email'] ) instanceof eZUser;

        if ( empty( $authResult['email'] ) || $userExists )
            $email = md5( 'ngconnect_' . $authResult['login_method'] . '_' . $authResult['id'] ) . '@localhost.local';
        else
            $email = $authResult['email'];

        $user = new eZUser(
            array(
                'contentobject_id' => $contentObject->attribute( 'id' ),
                'email' => $email,
                'login' => $userLogin,
                'password_hash' => md5( "$userLogin\n$userPassword" ),
                'password_hash_type' => 1
            )
        );
        $user->store();

        $userSetting = new eZUserSetting(
            array(
                'is_enabled' => true,
                'max_login' => 0,
                'user_id' => $contentObject->attribute('id')
            )
        );
        $userSetting->store();

        $dataMap['user_account']->setContent( $user );
        $dataMap['user_account']->store();

        $operationResult = eZOperationHandler::execute(
            'content',
            'publish',
            array(
                'object_id' => $contentObject->attribute( 'id' ),
                'version' => $version->attribute( 'version' )
            )
        );

        if ( ( array_key_exists( 'status', $operationResult ) && $operationResult['status'] == eZModuleOperationInfo::STATUS_CONTINUE ) )
        {
            $db->commit();
            return $user;
        }

        $db->rollback();
        return false;
    }

    /**
     * Updates user with provided auth data
     *
     * @param eZUser $user
     * @param array $authResult
     *
     * @return bool
     */
    public static function updateUser( $user, $authResult )
    {
        $currentTimeStamp = eZDateTime::currentTimeStamp();

        $contentObject = $user->contentObject();
        if ( !$contentObject instanceof eZContentObject )
        {
            return false;
        }

        /** @var eZContentObjectVersion $version */
        $version = $contentObject->currentVersion();

        $db = eZDB::instance();
        $db->begin();

        $version->setAttribute( 'modified', $currentTimeStamp );
        $version->store();

        self::fillUserObject( $version->dataMap(), $authResult );

        if ( $authResult['email'] != $user->Email )
        {
            $userExists = false;
            if ( eZUser::requireUniqueEmail() )
                $userExists = eZUser::fetchByEmail( $authResult['email'] ) instanceof eZUser;

            if ( empty( $authResult['email'] ) || $userExists )
                $email = md5( 'ngconnect_' . $authResult['login_method'] . '_' . $authResult['id'] ) . '@localhost.local';
            else
                $email = $authResult['email'];

            $user->setAttribute( 'email', $email );
            $user->store();
        }

        $contentObject->setName( $contentObject->contentClass()->contentObjectName( $contentObject ) );
        $contentObject->store();

        $db->commit();
        return $user;
    }

    /**
     * Fills the user object data map with auth data
     *
     * @param array $dataMap
     * @param array $authResult
     */
    private static function fillUserObject( $dataMap, $authResult )
    {
        if ( isset( $dataMap['first_name'] ) )
        {
            $dataMap['first_name']->fromString( $authResult['first_name'] );
            $dataMap['first_name']->store();
        }

        if ( isset( $dataMap['last_name'] ) )
        {
            $dataMap['last_name']->fromString( $authResult['last_name'] );
            $dataMap['last_name']->store();
        }

        if ( isset( $dataMap['image'] )  && !empty( $authResult['picture'] ) )
        {
            $storageDir = eZSys::storageDirectory() . '/ngconnect';
            if ( !( file_exists( $storageDir ) ) ) mkdir( $storageDir );
            $fileName = $storageDir . '/' . $authResult['login_method'] . '_' . $authResult['id'];

            $image = ngConnectFunctions::fetchDataFromUrl( $authResult['picture'], true, $fileName );
            if ( $image )
            {
                $dataMap['image']->fromString( $fileName );
                $dataMap['image']->store();
                unlink( $fileName );
            }
        }
    }

    /**
     * Fetches data from URL
     *
     * @param string $url
     * @param bool $saveToFile
     * @param string $fileName
     * @return bool|mixed
     */
    public static function fetchDataFromUrl( $url, $saveToFile = false, $fileName = '' )
    {
        $handle = curl_init( $url );
        if ( !$handle ) return false;

        curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 10 );
        curl_setopt( $handle, CURLOPT_TIMEOUT, 10 );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $handle, CURLOPT_HEADER, false );
        curl_setopt( $handle, CURLOPT_POST, 0 );
        curl_setopt( $handle, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $handle, CURLOPT_MAXREDIRS, 1 );

        if ( $saveToFile )
        {
            $fileHandle = fopen( $fileName, 'w' );
            if ( !$fileHandle )
            {
                curl_close( $handle );
                return false;
            }

            curl_setopt( $handle, CURLOPT_FILE, $fileHandle );
        }

        $data = curl_exec( $handle );
        curl_close( $handle );

        if ( $saveToFile )
        {
            fclose( $fileHandle );
        }

        return $data;
    }

    /**
     * Connects the eZ User with user from social network
     *
     * @param int $userID
     * @param string $loginMethod
     * @param int $networkUserID
     */
    public static function connectUser( $userID, $loginMethod, $networkUserID )
    {
        $ngConnect = ngConnect::fetch( $userID, $loginMethod, $networkUserID );
        if ( !$ngConnect instanceof ngConnect )
        {
            $ngConnect = new ngConnect(
                array(
                    'user_id' => $userID,
                    'login_method' => $loginMethod,
                    'network_user_id' => $networkUserID
                )
            );
            $ngConnect->store();
        }
    }
}
