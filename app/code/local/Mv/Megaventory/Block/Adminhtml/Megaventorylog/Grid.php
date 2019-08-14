<?php

class Mv_Megaventory_Block_Adminhtml_Megaventorylog_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		 
		// Set some defaults for our grid
		$this->setDefaultSort('log_id');
		$this->setId('mv_megaventory_megaventorylog_grid');
		$this->setDefaultFilter('log_id');
		$this->setDefaultSort('log_id');
		$this->setDefaultDir('asc');
		$this->setSaveParametersInSession(true);
	}
	 
	protected function _getCollectionClass()
	{
		// This is the model we are using for the grid
		return 'megaventory/megaventorylog_collection';
	}
	 
	protected function _prepareCollection()
	{
		// Get and set our collection for the grid
		$collection = Mage::getResourceModel($this->_getCollectionClass());
		$this->setCollection($collection);
		 
		return parent::_prepareCollection();
	}
	
	
	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('entity_id');
		$this->getMassactionBlock()->setFormFieldName('log');
	
		$this->getMassactionBlock()->addItem('delete', array(
				'label'=> Mage::helper('catalog')->__('Delete'),
				'url'  => $this->getUrl('*/*/massLogDelete'),
				'confirm' => Mage::helper('catalog')->__('Are you sure?')
		));
	
		return $this;
	}
	 
	protected function _prepareColumns()
	{
		// Add the columns that should appear in the grid
		$this->addColumn('log_id',
				array(
						'header'=> $this->__('ID'),
						'align' =>'right',
						'width' => '50px',
						'index' => 'log_id'
				)
		);
		 
		$this->addColumn('code',
				array(
						'header'=> $this->__('Code'),
						'index' => 'code'
				)
		);
		
		$this->addColumn('result',
				array(
						'header'=> $this->__('Result'),
						'index' => 'result',
		                'type'  => 'options',
		                'options' => array(success=>'success',error=>'error'),
				)
		);
		
		$this->addColumn('magento_id',
				array(
						'header'=> $this->__('Magento ID'),
						'index' => 'magento_id'
				)
		);
		
		$this->addColumn('details',
				array(
						'header'=> $this->__('Details'),
						'index' => 'details'
				)
		);
		
		$this->addColumn('return_entity',
				array(
						'header'=> $this->__('Return'),
                    	'sortable'  => false,
						'index' => 'return_entity'
				)
		);
		
		
		/* $this->addColumn('data',
				array(
						'header'=> $this->__('Data'),
						'sortable'  => false,
						'index' => 'data'
				)
		); */
		
		$this->addColumn('timestamp',
				array(
						'header'=> $this->__('Timestamp'),
						'index' => 'timestamp',
						'type' => 'datetime',
				)
		);
		
		$this->addColumn('action', array(
				'header' => Mage::helper('catalog')->__('Action'),
				'width' => '100px',
				'align' => 'center',
				'type' => 'action',
				'filter' => false,
				'sortable' => false,
				'renderer' => 'Mv_Megaventory_Block_Adminhtml_Renderer_Action',
		));
		 
		return parent::_prepareColumns();
	}
	 
	public function getRowUrl($row)
	{
		// This is where our row data will link to
		//return $this->getUrl('*/*/edit', array('id' => $row->getId()));
		return false;
	}
}