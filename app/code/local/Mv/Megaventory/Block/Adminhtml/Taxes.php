<?php

class Mv_Megaventory_Block_Adminhtml_Taxes extends Mage_Adminhtml_Block_Widget_Grid_Container{
	
	public function __construct()
	{
		$this->_blockGroup = 'mv_megaventory';
		$this->_controller = 'adminhtml_taxes';
		$this->_headerText = $this->__('Taxes');
		 
		parent::__construct();
		
		$this->removeButton('add');
	
		//$importLink = 'http://magento.localhost.com/index.php/megaventory/index/inmportinventories';
		
		$this->_addButton('import', array(
				'label'     => 'Synchronize Taxes',
				'onclick' => "setLocation('{$this->getUrl('*/*/synchronizeTaxes')}')",
				'class'     => 'add',
		));
		
	}
	
}