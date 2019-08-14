<?php

class Mv_Megaventory_Helper_Currencies extends Mage_Core_Helper_Abstract
{
	public function getCurrencies()
	{
		return Mage::getModel('megaventory/currencies')->getCollection()->load();
	}
	
	public function addMagentoCurrencies($megaventoryHelper){
		
		$defaultCurrencyCode = Mage::getStoreConfig('currency/options/default');
		$allowedCurrencies = Mage::getStoreConfig('currency/options/allow');
		
		$currencyCodes = explode(',', $allowedCurrencies);
		
		$totals = 0;
		foreach ($currencyCodes as $currencyCode)
		{
			/* if ($defaultCurrencyCode == $currencyCode)
				$default = true;
			else
				$default = false; */
			
			$mvCurrency = array(
					'CurrencyCode' => $currencyCode,
					'CurrencyDescription' => $currencyCode,
					'CurrencySymbol' => '',
					'CurrencyIsDefault' => false,
					'CurrencyInReports' => 'true',
			);
			
			$data = array
			(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'mvCurrency' => $mvCurrency,
				'mvRecordAction' => 'Insert'
			);
			
			$json_result = $megaventoryHelper->makeJsonRequest($data ,'CurrencyUpdate',0);
			
			$errorCode = $json_result['ResponseStatus']['ErrorCode'];
			
			if ($errorCode == 0){
				/* $entityID = $json_result['mvCurrency']['CurrencyId'];
				$newMVCurrency->setData('megaventory_id',$entityID); */
				$totals++;
			}
			/* else if ($errorCode == 500 && strpos($json_result['ResponseStatus']['Message'],'already exists') !== false)
			{
				$newMVCurrency->setData('megaventory_id',$json_result['entityID']);
			} */
			
			$newMVCurrency = Mage::getModel('megaventory/currencies')->load($currencyCode,'code');
			$newMVCurrency->setData('code',$currencyCode);
			$newMVCurrency->setData('description',$currencyCode);
			$newMVCurrency->setData('megaventory_id',$json_result['entityID']);
			$newMVCurrency->save();
		}
		
		
		$message = 'Added to Megaventory '.$totals.' currencies'.Mage::registry('tickImage');
		$megaventoryHelper->sendProgress(14, $message, '0', 'entities',true);
		
		return $totals;
	}
	
	
	private function checkIfCurrencyExists($mvCurrency){
		
		$currency = Mage::getModel('megaventory/currencies')->load($mvCurrency['CurrencyID'], 'megaventory_id');
		if (!$currency)
			return false;
		
		return $currency;
	}
	
	public function addSingleCurrency($currencyCode)
	{
		$mvCurrency = array(
				'CurrencyCode' => $currencyCode,
				'CurrencyDescription' => $currencyCode,
				'CurrencySymbol' => '',
				'CurrencyIsDefault' => false,
				'CurrencyInReports' => 'true',
		);
			
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'mvCurrency' => $mvCurrency,
				'mvRecordAction' => 'Insert'
		);
			
		$json_result = Mage::helper('megaventory')->makeJsonRequest($data ,'CurrencyUpdate',0);
			
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
			
		$newMVCurrency = Mage::getModel('megaventory/currencies')->load($currencyCode,'code');
		$newMVCurrency->setData('code',$currencyCode);
		$newMVCurrency->setData('description',$currencyCode);
		$newMVCurrency->setData('megaventory_id',$json_result['entityID']);
		$newMVCurrency->save();
	}

}
