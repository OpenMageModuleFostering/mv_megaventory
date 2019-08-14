<?php

class Mv_Megaventory_Block_Adminhtml_Megaventorylog extends Mage_Adminhtml_Block_Widget_Grid_Container{
	
	public function __construct()
	{
		$this->_blockGroup = 'mv_megaventory';
		$this->_controller = 'adminhtml_megaventorylog';
		$this->_headerText = $this->__('Megaventory Log');
		 
		parent::__construct();
		$this->removeButton('add');
	}
	
}