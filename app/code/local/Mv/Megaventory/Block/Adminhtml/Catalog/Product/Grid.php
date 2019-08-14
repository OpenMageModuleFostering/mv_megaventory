<?php

class Mv_Megaventory_Block_Adminhtml_Catalog_Product_Grid extends Mage_Adminhtml_Block_Catalog_Product_Grid {

    protected function _prepareColumns() {
		parent::_prepareColumns();
    
        $isMvEnabled = Mv_Megaventory_Helper_Common::isMegaventoryEnabled();
        
    	$inventories = Mage::helper('megaventory/inventories')->getInventories();
    	
    	if ($isMvEnabled == true){
    		
    		if (isset($this->_columns['websites'])){
	    		$this->addColumnAfter('inventories', array(
	    				'header' => 'Megaventory Qty',
	    				'index' => 'inventories',
	    				'type' => 'text',
	    				'align' => 'left',
	    				'width' => '250px',
	    				'filter' => false,
	    				'sortable' => false,
	    				'renderer' => "Mv_Megaventory_Block_Adminhtml_Renderer_Product_Inventories",
	    		),'websites');
    		}
    		else{
    			$this->addColumn('inventories', array(
    					'header' => 'Megaventory Qty',
    					'index' => 'inventories',
    					'type' => 'text',
    					'align' => 'left',
    					'width' => '250px',
    					'filter' => false,
    					'sortable' => false,
    					'renderer' => "Mv_Megaventory_Block_Adminhtml_Renderer_Product_Inventories",
    			));
    		}
    		
    		unset($this->_columns['visibility']);
    		//unset($this->_columns['qty']);
    		
    		$this->sortColumnsByOrder();
    	}
    	
        return $this;
    }

}