<?php

class Mv_Megaventory_Block_Adminhtml_Renderer_Product_Inventories extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text {

    public function render(Varien_Object $row) {
    	$i = 0;
    	
    	$i++;
    	
    	$inventories = Mage::helper('megaventory/inventories')->getInventories();
    	
    	if ($row['type_id'] != 'simple' && $row['type_id'] != 'virtual'){
    		return '&nbsp';
    	}
    	
    	if (count($inventories) == 0){
    		$html = "There are no inventories";
    		return $html;
    	}
    	 
    	$adminSession = Mage::getSingleton('admin/session');
    	$workOrders = $adminSession->getData('mv_isWorksModuleEnabled');
    	$html = '';
    	foreach ($inventories as $inventory){
    		if ($inventory['counts_in_total_stock'] == 1){
	    		$html .= '<ul style="padding-bottom:10px;">';
	    		$productStock = Mage::getModel('megaventory/productstocks')->loadInventoryProductstock($inventory->getId(), $row['entity_id']);
	    		$html .= '<li style="width:100%;padding-right:10px;display:table-cell;"><span style="float:left;">'.$inventory->getShortname().'</span></li>';
	    		$html .= '<li title="Quantity" style="padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStockqty(),5).'</span></li>';
	    		$html .= '<li title="Non-Shipped quantity in Sales Orders" style="color:Red;padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStocknonshippedqty(),5).'</span></li>';
	    		if ($workOrders === true)
	    			$html .= '<li title="Non-Allocated quantity in Work Orders" style="color:DarkRed;padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStocknonallocatedwoqty(),5).'</span></li>';
	    		$html .= '<li title="Non-Received quantity in Purchase Orders" style="color:Green;padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStocknonreceivedqty(),5).'</span></li>';
	    		if ($workOrders === true)
	    			$html .= '<li title="Non-Received quantity in Work Orders" style="color:DarkGreen;padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStocknonreceivedwoqty(),5).'</span></li>';
	    		$html .= '<li title="Alert quantity" style="padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStockalarmqty(),5).'</span></li>';
	    		$html .= '</ul>';
    		}
    	}
    	return $html;
    }

}

