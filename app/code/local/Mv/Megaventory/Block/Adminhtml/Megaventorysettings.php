<?php

class Mv_Megaventory_Block_Adminhtml_Megaventorysettings extends Mage_Core_Block_Template{
	
	private $_settings;
	private $_inventories;
	private $_taxes;
	private $_defaultMagentoCurrency;
	private $_defaultMegaventoryCurrency;
	private $_mvConnectivity = false;
	private $_magentoInstallations = false;
	
	public function __construct()
	{
		$this->_settings = Mage::getStoreConfig('megaventory/general');
		$this->_inventories = Mage::helper('megaventory/inventories')->getInventories();
		$this->_taxes = Mage::helper('megaventory/taxes')->getTaxes();
		$this->_mvConnectivity = Mv_Megaventory_Helper_Common::checkConnectivity();
		
		$this->_defaultMegaventoryCurrency = $this->setDefaultMegaventoryCurrency();
		
		$this->_defaultMagentoCurrency = Mage::getStoreConfig('currency/options/default');
		
		if ($this->_mvConnectivity !== false){
			$megaventoryHelper = Mage::helper('megaventory');
			$setting = $megaventoryHelper->getMegaventoryAccountSettings('MagentoInstallations');
			$this->_magentoInstallations = $setting['0']['SettingValue'];
		}
		
		parent::__construct();
	}
	
	public function getSettingValue($name){
		
		if (isset($this->_settings[$name]))
			return $this->_settings[$name];
		else
			return '';
	}
	
	public function getFormKey()
	{
		return Mage::getSingleton('core/session')->getFormKey();
	}
	
	public function getInventories()
	{
		return $this->_inventories;
	}
	
	public function getTaxes()
	{
		return $this->_taxes;
	}
	
	public function getDefaultMagentoCurrency()
	{
		return $this->_defaultMagentoCurrency;
	}
	
	public function getDefaultMegaventoryCurrency()
	{
		return $this->_defaultMegaventoryCurrency;
	}
	
	public function connectivityOk()
	{
		return $this->_mvConnectivity;
	}

	public function getMagentoInstallations()
	{
		return $this->_magentoInstallations;
	}
		
	public function checkBaseCurrencies()
	{
		if ($this->_defaultMagentoCurrency != $this->_defaultMegaventoryCurrency)
			return false;
		
		return true;
	}
	
	private function setDefaultMegaventoryCurrency()
	{
		if ($this->_mvConnectivity !== true)
			return false;
		
		$apikey = Mage::getStoreConfig('megaventory/general/apikey');
		$apiurl = Mage::getStoreConfig('megaventory/general/apiurl');
		
		
		$data = array
		(
				'APIKEY' => $apikey,
				'query' => 'mv.CurrencyIsDefault = 1'
		);
			
		$megaventoryHelper = Mage::helper('megaventory');
		$json_result = $megaventoryHelper->makeJsonRequest($data ,'CurrencyGet',0,$apiurl);
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0')
			return false;
		
		return $json_result['mvCurrencies'][0]['CurrencyCode'];
	}
}