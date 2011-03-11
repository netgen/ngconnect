<?php

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$ini = eZINI::instance();

if($http->hasPostVariable('OkButton'))
{
	if($http->hasSessionVariable('NGConnectLastAccessURI'))
	{
		return $module->redirectTo($http->sessionVariable('NGConnectLastAccessURI'));
	}
	else
	{
		return $module->redirectTo('/');
	}
}
else
{
	$module->setTitle('Successful registration');

	$tpl = eZTemplate::factory();
	$tpl->setVariable('module', $module);

	$verifyUserEmail = $ini->variable('UserSettings', 'VerifyUserEmail');
	if($verifyUserEmail == 'enabled')
		$tpl->setVariable('verify_user_email', true);
	else
		$tpl->setVariable('verify_user_email', false);

	$Result = array();
	$Result['content'] = $tpl->fetch('design:ngconnect/success.tpl');
	$Result['path'] = array(array('text' => ezpI18n::tr('kernel/user', 'User'), 'url' => false),
							array('text' => ezpI18n::tr('kernel/user', 'Success'), 'url' => false));
}

?>
