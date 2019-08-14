<?php

class Mv_Megaventory_Block_Adminhtml_Renderer_Boolean extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action {

    public function render(Varien_Object $row) {
    	$default = $row['isdefault'];
    	if (isset($default) && $default == 1){
        	return 'Yes';
    	}
    	else{
    		$actionUrl = $this->getUrl('*/*/makeDefaultInventory', array('id' => $row->getData('id')));
    		return '<a href="'.$actionUrl.'">Make Default</a>';
    	
    	}
    }

}

