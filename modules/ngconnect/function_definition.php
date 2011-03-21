<?php

$FunctionList = array();

$FunctionList['username_is_generated'] = array('name' => 'username_is_generated',
												'operation_types' => array('read'),
												'call_method' => array('class' => 'ngConnectFunctionCollection', 'method' => 'usernameIsGenerated'),
												'parameter_type' => 'standard',
												'parameters' => array());

$FunctionList['email_is_generated'] = array('name' => 'email_is_generated',
												'operation_types' => array('read'),
												'call_method' => array('class' => 'ngConnectFunctionCollection', 'method' => 'emailIsGenerated'),
												'parameter_type' => 'standard',
												'parameters' => array());

$FunctionList['user_has_connection'] = array('name' => 'user_has_connection',
												'operation_types' => array('read'),
												'call_method' => array('class' => 'ngConnectFunctionCollection', 'method' => 'userHasConnection'),
												'parameter_type' => 'standard',
												'parameters' => array(array('name' => 'user_id',
																			'type' => 'integer',
																			'required' => true),
                                                                      array('name' => 'login_method',
																			'type' => 'string',
																			'required' => true)));

?>