<?php

 
class Mv_Megaventory_Block_Adminhtml_Product_Edit_Tab_Megaventory extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Inventory
{
	
    public function __construct()
    {
        parent::__construct();
        
        $this->setTemplate('megaventory/catalog/product/tab/inventory.phtml');
    }
    
    public function getInventories()
    {
    	return Mage::helper('megaventory/inventories')->getInventories();
    }
    
    public function getInventoryProductstock($inventoryId, $productId)
    {
    	return Mage::getModel('megaventory/productstocks')->loadInventoryProductstock($inventoryId, $productId);
    }
    
    public function isVirtual()
    {
    	return $this->getProduct()->getTypeInstance()->isVirtual();
    }
    
    public function isStockReadonly()
    {
  		return false;
		//return Mv_Megaventory_Helper_Common::isMegaventoryEnabled();
    }
}