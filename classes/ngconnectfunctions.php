<?php

class ngConnectFunctions
{
	public static function createOrUpdateUser($loginMethod, $userData)
	{
		$user = eZUser::fetchByName('0_ngconnect_' . $loginMethod . '_' . $userData['id']);
		if($user instanceof eZUser)
		{
			return self::updateUser($user, $loginMethod, $userData);
		}

		$user = eZUser::fetchByName('ngconnect_' . $loginMethod . '_' . $userData['id']);
		if($user instanceof eZUser)
		{
			return self::updateUser($user, $loginMethod, $userData);
		}		

		return self::createUser($loginMethod, $userData);
	}

	private static function createUser($loginMethod, $userData)
	{
		$ngConnectINI = eZINI::instance('ngconnect.ini');
		$siteINI = eZINI::instance('site.ini');
		$storageDir = eZSys::storageDirectory() . '/ngconnect';

		$defaultUserPlacement = $ngConnectINI->variable('LoginMethod_' . $loginMethod, 'DefaultUserPlacement');
		$placementNode = eZContentObjectTreeNode::fetch($defaultUserPlacement);

		if(!($placementNode instanceof eZContentObjectTreeNode))
		{
			$defaultUserPlacement = $siteINI->variable('UserSettings', 'DefaultUserPlacement');
			$placementNode = eZContentObjectTreeNode::fetch($defaultUserPlacement);

			if(!($placementNode instanceof eZContentObjectTreeNode))
			{
				return false;
			}
		}

		$contentClass = eZContentClass::fetch($siteINI->variable( 'UserSettings', 'UserClassID'));
		$userCreatorID = $siteINI->variable('UserSettings', 'UserCreatorID');
		$defaultSectionID = $siteINI->variable('UserSettings', 'DefaultSectionID');

		$db = eZDB::instance();
		$db->begin();

		$contentObject = $contentClass->instantiate($userCreatorID, $defaultSectionID);
		$contentObject->store();

		$nodeAssignment = eZNodeAssignment::create(array('contentobject_id' => $contentObject->attribute('id'),
													'contentobject_version' => 1,
													'parent_node' => $placementNode->attribute('node_id'),
													'is_main' => 1));
		$nodeAssignment->store();

		$currentTimeStamp = eZDateTime::currentTimeStamp();
		$version = $contentObject->currentVersion();
		$version->setAttribute('modified', $currentTimeStamp);
		$version->setAttribute('status', eZContentObjectVersion::STATUS_DRAFT);
		$version->store();

		$dataMap = $version->dataMap();

		if(isset($dataMap['first_name']))
		{
			$dataMap['first_name']->fromString($userData['first_name']);
			$dataMap['first_name']->store();
		}

		if(isset($dataMap['last_name']))
		{
			$dataMap['last_name']->fromString($userData['last_name']);
			$dataMap['last_name']->store();
		}

		if(isset($dataMap['image']) && strlen($userData['picture']) > 0)
		{
			if(!(file_exists($storageDir))) mkdir($storageDir);
			$fileName = $storageDir . '/' . $loginMethod . '_' . $userData['id'];

			$image = ngConnectFunctions::fetchDataFromUrl($userData['picture'], true, $fileName);
			if($image)
			{
				$dataMap['image']->fromString($fileName);
				$dataMap['image']->store();
				unlink($fileName);
			}
		}

		if(!isset($dataMap['user_account']))
		{
			$db->rollback();
			return false;
		}

		$userLogin = 'ngconnect_' . $loginMethod . '_' . $userData['id'];
		$userPassword = (string) rand() . 'ngconnect_' . $loginMethod . '_' . $userData['id'] . (string) rand();

		$user = new eZUser(
			array(
				'contentobject_id'		=> $contentObject->attribute('id'),
				'email'					=> strlen($userData['email']) > 0 ?
											$userData['email'] :
											md5('ngconnect_' . $loginMethod . '_' . $userData['id']) . '@localhost.local',
				'login'					=> $userLogin,
				'password_hash'			=> md5("$userLogin\n$userPassword"),
				'password_hash_type'	=> 1
			)
		);
		$user->store();

		$userSetting = new eZUserSetting(
			array(
				'is_enabled'	=> true,
				'max_login'		=> 0,
				'user_id'		=> $contentObject->attribute('id')
			)
		);
		$userSetting->store();

		$dataMap['user_account']->setContent($user);
		$dataMap['user_account']->store();

		$operationResult = eZOperationHandler::execute('content', 'publish', array('object_id' => $contentObject->attribute('id'), 'version' => $version->attribute('version')));

		if((array_key_exists('status', $operationResult) && $operationResult['status'] == eZModuleOperationInfo::STATUS_CONTINUE))
		{
			$db->commit();
			return $user;
		}

		$db->rollback();
		return false;
	}

	private static function updateUser($user, $loginMethod, $userData)
	{
		$storageDir = eZSys::storageDirectory() . '/ngconnect';
		$currentTimeStamp = eZDateTime::currentTimeStamp();

		$contentObject = $user->contentObject();
		if(!($contentObject instanceof eZContentObject))
		{
			return false;
		}

		$version = $contentObject->currentVersion();

		$db = eZDB::instance();
		$db->begin();

		$version->setAttribute('modified', $currentTimeStamp);
		$version->store();

		$dataMap = $version->dataMap();

		if(isset($dataMap['first_name']))
		{
			$dataMap['first_name']->fromString($userData['first_name']);
			$dataMap['first_name']->store();
		}

		if(isset($dataMap['last_name']))
		{
			$dataMap['last_name']->fromString($userData['last_name']);
			$dataMap['last_name']->store();
		}

		if(isset($dataMap['image']) && strlen($userData['picture']) > 0)
		{
			if(!(file_exists($storageDir))) mkdir($storageDir);
			$fileName = $storageDir . '/' . $loginMethod . '_' . $userData['id'];

			$image = ngConnectFunctions::fetchDataFromUrl($userData['picture'], true, $fileName);
			if($image)
			{
				$dataMap['image']->fromString($fileName);
				$dataMap['image']->store();
				unlink($fileName);
			}
		}

		$user->setAttribute('email', strlen($userData['email']) > 0 ? $userData['email'] :
										md5('ngconnect_' . $loginMethod . '_' . $userData['id']) . '@localhost.local');
		$user->store();

		$userSetting = eZUserSetting::fetch($user->attribute('contentobject_id'));
		$userSetting->setAttribute('is_enabled', true);
		$userSetting->setAttribute('max_login', 0);
		$userSetting->store();

		$contentObject->setName($contentObject->contentClass()->contentObjectName($contentObject));
		$contentObject->store();

		$db->commit();
		return $user;
	}

	public static function fetchDataFromUrl($url, $saveToFile = false, $fileName = '')
	{
		$handle = curl_init($url);
		if(!$handle) return false;

		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($handle, CURLOPT_TIMEOUT, 10);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_HEADER, false);
		curl_setopt($handle, CURLOPT_POST, 0);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($handle, CURLOPT_MAXREDIRS, 1);

		if($saveToFile)
		{
			$fileHandle = fopen($fileName, 'w');
			if(!$fileHandle)
			{
				curl_close($handle);
				return false;
			}

			curl_setopt($handle, CURLOPT_FILE, $fileHandle);
		}

		$data = curl_exec($handle);
		curl_close($handle);

		if($saveToFile)
		{
			fclose($fileHandle);
		}

		return $data;
	}

	public static function connectUser($userID, $loginMethod, $networkUserID)
	{
		$ngConnect = ngConnect::fetch($userID, $loginMethod, $networkUserID);
		if(!($ngConnect instanceof ngConnect))
		{
			$ngConnect = new ngConnect(array(
				'user_id'				=> $userID,
				'login_method'			=> $loginMethod,
				'network_user_id'		=> $networkUserID
			));
			$ngConnect->store();
		}
	}
}

?>