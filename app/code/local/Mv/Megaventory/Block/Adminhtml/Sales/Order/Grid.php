<?php


class Mv_Megaventory_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid {

    protected function _prepareColumns() {


        parent::_prepareColumns();
        
	    if (Mv_Megaventory_Helper_Common::isMegaventoryEnabled()){
	        $this->addColumn('inventory_id', array(
	        		 
	        		'header' => 'Inventory',
	        		'index' => 'inventory_id',
	        		'width' => '150px',
	        		'renderer' => 'Mv_Megaventory_Block_Adminhtml_Renderer_Orderinventory',
    				'filter' => false,
    				'sortable' => false
	        		
	        ));
        }

    }
}