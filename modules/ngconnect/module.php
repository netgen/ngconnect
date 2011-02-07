<?php

$Module = array( 'name' => 'Netgen Connect', 'variable_params' => true );

$ViewList = array();
$FunctionList = array();

$ViewList['login'] = array(
	'functions' => array('login'),
	'script' => 'login.php',
	'params' => array('LoginMethod')
);

$ViewList['callback'] = array(
	'functions' => array('callback'),
	'script' => 'callback.php',
	'params' => array('LoginMethod')
);

$FunctionList['login'] = array();
$FunctionList['callback'] = array();

?>