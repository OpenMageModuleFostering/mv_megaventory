<?php


class Mv_Megaventory_Model_Inventories extends Mage_Core_Model_Abstract
{
	protected function _construct()
	{
		$this->_init('megaventory/inventories');
	}
	
	public function loadDefault()
	{
		return $this->load('1','isdefault');
	}
	
	public function loadByName($shortName){
		return $this->load($shortName, 'shortname');
	}
}