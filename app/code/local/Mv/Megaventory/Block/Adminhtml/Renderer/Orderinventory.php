<?php

class Mv_Megaventory_Block_Adminhtml_Renderer_Orderinventory extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action {

    public function render(Varien_Object $row) {
    	$inventory = Mage::getModel('megaventory/inventories');
    	$inventoryId = $row['mv_inventory_id'];
    	if ($inventoryId != 0){
    		$inventory->load($inventoryId);
    		return $inventory->getData('shortname');
    	}
    	else{
    		$orderSynchronization = Mage::getStoreConfig('megaventory/general/ordersynchronization');
    		
    		
    		$notAssigned = 'Not Synchronized';
    		if (empty($orderSynchronization) || $orderSynchronization === '0'){
    			return $notAssigned;
    		}
    		$synchronize = '<a onclick="MegaventoryManager.synchronizeOrder(\'' . $this->getUrl('megaventory/index/synchronizeOrder')  .'\','.$row->getId().')" href="#">Retry</a>';
    		return $notAssigned . '<br>' .$synchronize;
    	}
    }
}

