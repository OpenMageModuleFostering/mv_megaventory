<?php

class Mv_Megaventory_Block_Adminhtml_Megaventorysettings extends Mage_Core_Block_Template{
	
	private $_settings;
	private $_inventories;
	private $_taxes;
	private $_currencies;
	private $_mvConnectivity = false;
	
	public function __construct()
	{
		$this->_settings = Mage::getStoreConfig('megaventory/general');
		$this->_inventories = Mage::helper('megaventory/inventories')->getInventories();
		$this->_taxes = Mage::helper('megaventory/taxes')->getTaxes();
		$this->_mvConnectivity = Mv_Megaventory_Helper_Common::checkConnectivity();
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
	
	public function getCurrencies()
	{
		return $this->_currencies;
	}
	
	public function connectivityOk()
	{
		return $this->_mvConnectivity;
	}

}