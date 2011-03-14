<?php

interface INGConnectAuthInterface
{
	public function getRedirectUri();

	public function processAuth();
}

?>