<?php

class Mv_Megaventory_Block_Adminhtml_Taxes_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		 
		// Set some defaults for our grid
		$this->setDefaultSort('id');
		$this->setId('mv_megaventory_taxes_grid');
		$this->setDefaultFilter('id');
		$this->setDefaultSort('id');
		$this->setDefaultDir('asc');
		$this->setSaveParametersInSession(true);
	}
	 
	protected function _getCollectionClass()
	{
		// This is the model we are using for the grid
		return 'megaventory/taxes_collection';
	}
	 
	protected function _prepareCollection()
	{
		// Get and set our collection for the grid
		$collection = Mage::getResourceModel($this->_getCollectionClass());
		$this->setCollection($collection);
		 
		return parent::_prepareCollection();
	}
	 
	protected function _prepareColumns()
	{
		// Add the columns that should appear in the grid
		$this->addColumn('id',
				array(
						'header'=> $this->__('ID'),
						'align' =>'right',
						'width' => '50px',
						'index' => 'id'
				)
		);
		 
		$this->addColumn('name',
				array(
						'header'=> $this->__('Name'),
						'index' => 'name'
				)
		);
		
		$this->addColumn('description',
				array(
						'header'=> $this->__('Description'),
						'index' => 'description'
				)
		);
		
		$this->addColumn('percentage',
				array(
						'header'=> $this->__('Percentage'),
						'index' => 'percentage'
				)
		);
		 
		return parent::_prepareColumns();
	}
	 
	public function getRowUrl($row)
	{
		// This is where our row data will link to
		//return $this->getUrl('*/*/edit', array('id' => $row->getId()));
		return false;
	}
}