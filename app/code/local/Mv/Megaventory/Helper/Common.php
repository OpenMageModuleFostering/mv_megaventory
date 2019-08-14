<?php

class Mv_Megaventory_Helper_Common
{
	const SHIPPINGSKU  = 'shipping_service_01';
	const DISCOUNTSKU  = 'discount_01';
	
	public static function isMegaventoryEnabled()
	{
		$mvIntegration = Mage::getStoreConfig('megaventory/general/enabled');
		$mvApiUrl = Mage::getStoreConfig('megaventory/general/apiurl');
		$mvApiKey = Mage::getStoreConfig('megaventory/general/apikey');
		
		if ($mvIntegration == '1' && $mvApiUrl != null && $mvApiKey != null)
			return true;
		
		
		return false;
	}
	
	public static function checkConnectivity(){
		
		$megaventoryHelper = Mage::helper('megaventory');
		$accountSettings = $megaventoryHelper->getMegaventoryAccountSettings();
		
		$mvIntegration = Mage::getStoreConfig('megaventory/general/enabled');
		if ($mvIntegration != '1')
		{
			return 'Megaventory extension is disabled';
		}
		
		
		if ($accountSettings === false) //connectivity problem
			return 'There is a problem with your megaventory credentials!';
		else
		{
			$message = '';
			foreach ($accountSettings as $index => $accountSetting) {
				$settingName = $accountSetting['SettingName'];
				$settingValue = $accountSetting['SettingValue'];
				/* if ($settingName == 'isMagentoModuleEnabled' && $settingValue == false)
					$message .= 'Magento module in Megaventory is not enabled.';
				if ($settingName == 'MagentoEndPointURL'){
					$host = parse_url($settingValue,PHP_URL_HOST);
					$magentoHost = $_SERVER['HTTP_HOST'];
					if ($host != $magentoHost)
						$message .= sprintf('The Magento Shop URL in Megaventory (%s) should match with the domain of your Magento store (%s).',$host,$magentoHost);
				}
				if ($settingName == 'MagentoUserName'){
					if (empty($settingValue))
						$message .= 'Magento username in Megaventory is not set.';
					else
					{
						$user = Mage::getModel('api/user')->loadByUsername($settingValue);
						if (!$user->getId()){
							$message .= sprintf('Magento username %s (web-services user in Magento) does not exist.',$settingValue);
						}
					}
				} */
				if ($settingName == 'isOrdersModuleEnabled' && $settingValue == false)
					$message .= 'Ordering module in Megaventory is not enabled.';
				
			}
			if (strlen($message) > 0){
				return $message;
			}
		}
		
		
		return true;
	}
	
	public static function getExtensionVersion()
	{
		//return 'test';
		return (string) Mage::getConfig()->getNode()->modules->Mv_Megaventory->version;
	}
}
