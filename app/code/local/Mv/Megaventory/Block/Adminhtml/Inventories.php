<?php

class Mv_Megaventory_Block_Adminhtml_Inventories extends Mage_Adminhtml_Block_Widget_Grid_Container{
	
	public function __construct()
	{
		$this->_blockGroup = 'mv_megaventory';
		$this->_controller = 'adminhtml_inventories';
		$this->_headerText = $this->__('Inventories');
		 
		parent::__construct();
		
		$this->removeButton('add');
	
		//$importLink = 'http://magento.localhost.com/index.php/megaventory/index/inmportinventories';
		
		$this->_addButton('import', array(
				'label'     => 'Synchronize Inventories',
				'onclick' => "setLocation('{$this->getUrl('*/*/importInventories')}')",
				'class'     => 'add',
		));
		
	}
	
}