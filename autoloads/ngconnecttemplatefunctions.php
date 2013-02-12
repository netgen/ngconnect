<?php

class ngConnectTemplateFunctions
{
    /**
     * Returns configured operators
     *
     * @return array
     */
    function operatorList()
    {
        return array( 'user_exists' );
    }

    /**
     * Returns if template operators support named parameters
     *
     * @return bool
     */
    function namedParameterPerOperator()
    {
        return true;
    }

    /**
     * Returns definition of named parameters
     *
     * @return array
     */
    function namedParameterList()
    {
        return array(
            'user_exists' => array(
                'login' => array(
                    'type' => 'string',
                    'required' => true,
                    'default' => ''
                )
            )
        );
    }

    /**
     * Executes the operators
     *
     * @param eZTemplate $tpl
     * @param string $operatorName
     * @param array $operatorParameters
     * @param string $rootNamespace
     * @param string $currentNamespace
     * @param mixed $operatorValue
     * @param array $namedParameters
     */
    function modify($tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters)
    {
        switch ( $operatorName )
        {
            case 'user_exists':
            {
                $operatorValue = self::userExists( $namedParameters['login'] );
            } break;
        }
    }

    /**
     * Returns true if user with $login exists, false otherwise
     *
     * @param string $login
     *
     * @return bool
     */
    static function userExists( $login )
    {
        $user = eZUser::fetchByName( $login );
        return $user instanceof eZUser;
    }
}
