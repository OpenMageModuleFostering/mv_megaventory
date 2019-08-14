<?php

class Mv_Megaventory_Block_Adminhtml_Inventories_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		 
		// Set some defaults for our grid
		$this->setDefaultSort('id');
		$this->setId('mv_megaventory_inventories_grid');
		$this->setDefaultFilter('id');
		$this->setDefaultSort('id');
		$this->setDefaultDir('asc');
		$this->setSaveParametersInSession(true);
	}
	 
	protected function _getCollectionClass()
	{
		// This is the model we are using for the grid
		return 'megaventory/inventories_collection';
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
		 
		$this->addColumn('shortname',
				array(
						'header'=> $this->__('Short Name'),
						'index' => 'shortname'
				)
		);
		
		$this->addColumn('name',
				array(
						'header'=> $this->__('Name'),
						'index' => 'name'
				)
		);
		
		$this->addColumn('address',
				array(
						'header'=> $this->__('Address'),
						'index' => 'address'
				)
		);

		$this->addColumn('counts_in_stock',
				array(
						'header'=> 'Counts in global stock',
						'index' => 'counts_in_stock',
						'renderer' => new Mv_Megaventory_Block_Adminhtml_Renderer_Countsinstock(),
						'filter' => false,
            			'align' => 'center',
						'sortable' => false
				)
		);
		
		$this->addColumn('default',
				array(
						'header'=> $this->__('Default'),
						'index' => 'default',
						'renderer' => new Mv_Megaventory_Block_Adminhtml_Renderer_Boolean(),
						'filter' => false,
						'sortable' => false
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