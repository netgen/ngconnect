<?php

class ngConnectFunctionCollection
{
	static public function usernameIsGenerated()
	{
		$currentUser = eZUser::currentUser();
		if($currentUser instanceof eZUser)
		{
			$pos = strpos($currentUser->Login, "ngconnect_", 0);
			if($pos !== false && $pos === 0)
			{
				return array('result' => true);
			}
		}

		return array('result' => false);
	}

	static public function emailIsGenerated()
	{
		$currentUser = eZUser::currentUser();
		if($currentUser instanceof eZUser)
		{
			$pos = strpos($currentUser->Login, "ngconnect_", 0);
			if($pos !== false && $pos === 0)
			{
				if(strpos($currentUser->Email, "@localhost.local", 0) === strlen($currentUser->Email) - 16)
				{
					return array('result' => true);
				}
			}
		}

		return array('result' => false);
	}

	static public function userHasConnection($userID, $loginMethod)
	{
		return array('result' => ngConnect::userHasConnection($userID, $loginMethod));
	}
}

?>