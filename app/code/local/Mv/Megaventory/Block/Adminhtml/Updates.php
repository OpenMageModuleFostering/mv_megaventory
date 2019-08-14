<?php

class Mv_Megaventory_Block_Adminhtml_Updates extends Mage_Adminhtml_Block_Widget_Grid_Container{
	
	public function __construct()
	{
		$this->_blockGroup = 'mv_megaventory';
		$this->_controller = 'adminhtml_updates';
		$this->_headerText = $this->__('Pending Updates');
		 
		parent::__construct();
		
		$this->removeButton('add');
	
		
		$this->_addButton('process', array(
            'label'     => Mage::helper('megaventory')->__('Process Now'),
            'onclick'   => "setLocation('{$this->getUrl('*/*/processupdates')}')",
            'class'     => 'go'
        ), 0, 100, 'header', 'header');
	}
	
}