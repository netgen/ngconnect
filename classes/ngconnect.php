<?php

class ngConnect extends eZPersistentObject
{
    function __construct($row)
    {
        parent::__construct($row);
    }

    static function definition()
    {
        return array(
                    'fields' => array(  'user_id' => array( 'name' => 'UserID',
                                                            'datatype' => 'integer',
                                                            'default' => 0,
                                                            'required' => true),
                                        'login_method' => array(    'name' => 'LoginMethod',
                                                                    'datatype' => 'string',
                                                                    'default' => '',
                                                                    'required' => true),
                                        'network_user_id' => array( 'name' => 'NetworkUserID',
                                                                    'datatype' => 'string',
                                                                    'default' => '',
                                                                    'required' => true)),
                    'keys' => array('user_id', 'login_method', 'network_user_id'),
                    'class_name' => 'ngConnect',
                    'sort' => array('user_id' => 'asc', 'login_method' => 'asc', 'network_user_id' => 'asc'),
                    'name' => 'ngconnect');
    }

    static function fetch($userID, $loginMethod, $networkUserID)
    {
        return eZPersistentObject::fetchObject(self::definition(), null,
                array(  'user_id' => $userID,
                        'login_method' => $loginMethod,
                        'network_user_id' => $networkUserID));
    }

    static function fetchBySocialNetwork($loginMethod, $networkUserID)
    {
        $result = eZPersistentObject::fetchObjectList(self::definition(), null,
                    array(  'login_method' => $loginMethod,
                            'network_user_id' => $networkUserID));

        if(is_array($result) && !empty($result))
            return $result;

        return array();
    }

    static function userHasConnection($userID, $loginMethod)
    {
        $count = eZPersistentObject::count(self::definition(), array('user_id' => $userID, 'login_method' => $loginMethod));
        if($count > 0)
            return true;

        $user = eZUser::fetch($userID);
        if(substr($user->Login, 0, 10 + strlen($loginMethod)) === 'ngconnect_' . $loginMethod)
            return true;

        return false;
    }
}

?>
