<?php


class Mv_Megaventory_Model_Productstocks extends Mage_Core_Model_Abstract
{
	protected function _construct()
	{
		$this->_init('megaventory/productstocks');
	}
	
	public function loadInventoryProductstock($inventoryId, $productId)
	{
		 return $this->getCollection()
		 ->addFieldToFilter('product_id',$productId)
	     ->addFieldToFilter('inventory_id', $inventoryId)
	     ->load()->getFirstItem();
	}
	
	public function loadProductstocks($productId){
		return $this->getCollection()
		->addFieldToFilter('product_id',$productId)
		->load();
	}
}