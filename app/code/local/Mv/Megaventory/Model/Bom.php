<?php


class Mv_Megaventory_Model_Bom extends Mage_Core_Model_Abstract
{
	protected function _construct()
	{
		$this->_init('megaventory/bom');
	}
	
	public function loadByBOMCode($bundleCode)
	{
		return $this->load($bundleCode,'auto_code');
	}
	
	public function loadByBOMSku($bundleSku)
	{
		return $this->load($bundleSku,'megaventory_sku');
	}
	
}