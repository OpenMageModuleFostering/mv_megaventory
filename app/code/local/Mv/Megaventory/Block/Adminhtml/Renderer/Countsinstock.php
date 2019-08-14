<?php

class Mv_Megaventory_Block_Adminhtml_Renderer_Countsinstock extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action {

    public function render(Varien_Object $row) {
    	
    	$inventory = $row['id'];
    	
    	$countsInTotalStock = $row['counts_in_total_stock'];
    	if (isset($countsInTotalStock) && $countsInTotalStock == 1){
        	$checked = ' checked ';
    	}
    	else{
    		$checked = ' ';
    	}
    	
    	$url = '/index.php/megaventory/index/updateCountsInStock/key/'.$this->getFormKey();
    	$onclick = 
    	' onclick="MegaventoryManager.changeCountsInStock(\''.$inventory.'\', this.checked ,\''.$url.'\')" ';
    	
    	$html = '<input type="checkbox"'.$checked.$onclick.'/>';

    	
    	return $html;
    }

    
}

