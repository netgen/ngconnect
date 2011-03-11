<?php

$Module = array( 'name' => 'Netgen Connect', 'variable_params' => true );

$ViewList = array();
$FunctionList = array();

$ViewList['login'] = array(
	'functions' => array('login'),
	'script' => 'login.php',
	'ui_context' => 'authentication',
	'params' => array('LoginMethod')
);

$ViewList['callback'] = array(
	'functions' => array('callback'),
	'script' => 'callback.php',
	'ui_context' => 'authentication',
	'params' => array('LoginMethod')
);

$ViewList['profile'] = array(
	'functions' => array('profile'),
	'script' => 'profile.php',
	'ui_context' => 'authentication',
	'params' => array()
);

$ViewList['success'] = array(
	'functions' => array('success'),
	'script' => 'success.php',
	'ui_context' => 'authentication',
	'params' => array()
);


$FunctionList['login'] = array();
$FunctionList['callback'] = array();
$FunctionList['profile'] = array();
$FunctionList['success'] = array();

?>