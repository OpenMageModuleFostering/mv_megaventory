<?php

class Mv_Megaventory_Block_Adminhtml_Updates_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		 
		// Set some defaults for our grid
		$this->setDefaultSort('id');
		$this->setId('mv_megaventory_updates_grid');
		$this->setDefaultFilter('id');
		$this->setDefaultSort('id');
		$this->setDefaultDir('asc');
		$this->setSaveParametersInSession(true);
		$this->setFilterVisibility(false);
		$this->setPagerVisibility(false);
	}
	 
	protected function _getCollectionClass()
	{
		// This is the model we are using for the grid
		return 'megaventory/inventories_collection';
	}
	 
	protected function _prepareCollection()
    {
        $collection = $this->_getUpdates();

        $this->setCollection($collection);
    }
	 
	protected function _prepareColumns()
	{
		// Add the columns that should appear in the grid
		$this->addColumn('IntegrationUpdateID',
				array(
						'header'=> $this->__('ID'),
						'align' =>'right',
						'width' => '50px',
						'index' => 'IntegrationUpdateID',
						'filter' => false,
						'sortable' => false
				)
		);
		 
		$this->addColumn('Entity',
				array(
						'header'=> $this->__('Entity'),
						'index' => 'Entity',
						'filter' => false,
						'sortable' => false
				)
		);
		
		$this->addColumn('Action',
				array(
						'header'=> $this->__('Action'),
						'index' => 'Action',
						'filter' => false,
						'sortable' => false
				)
		);
		
		$this->addColumn('EntityIDs',
				array(
						'header'=> $this->__('EntityIDs'),
						'index' => 'EntityIDs',
						'filter' => false,
						'sortable' => false
				)
		);

		$this->addColumn('Tries',
				array(
						'header'=> $this->__('Tries'),
						'index' => 'Tries',
						'filter' => false,
						'sortable' => false
				)
		);
		
		$this->addColumn('IntegrationUpdateDateTime',
				array(
						'header'=> $this->__('Update Timestamp (M-D-Y format)'),
						'index' => 'IntegrationUpdateDateTime',
						'filter' => false,
						'sortable' => false
				)
		);
		
		/* $this->addColumn('JsonData',
				array(
						'header'=> $this->__('JsonData'),
						'index' => 'JsonData',
						'filter' => false,
						'sortable' => false
				)
		); */
		
		 
		return parent::_prepareColumns();
	}
	 
	
	protected function _getUpdates()
	{
		$collection = new Varien_Data_Collection();
		
		$key = Mage::getStoreConfig('megaventory/general/apikey');
			
		$magentoId = Mage::getStoreConfig('megaventory/general/magentoid');
		if (!isset($magentoId))
			$magentoId = "magento";
			
		$data = array
		(
				'APIKEY' => $key,
				'query' => 'mv.Application = "'.$magentoId.'"'
		);
			
		$helper = Mage::helper('megaventory');
		
		try{
			$json_result = $helper->makeJsonRequest($data ,'IntegrationUpdateGet',0);
		}
		catch (Exception $ex){
			Mage::log('exception',0,'mv_cron.log',true);
			return;
		}
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode == '0'){
			
			$mvIntegrationUpdates = $json_result['mvIntegrationUpdates'];
			
			foreach($mvIntegrationUpdates as $mvIntegrationUpdate)
			{
				$item = new Varien_Object();
				$arraykeys = array_keys($mvIntegrationUpdate);
				foreach ($arraykeys as $arraykey)
				{
					if ($arraykey == 'IntegrationUpdateDateTime'){
						$updateDT = $mvIntegrationUpdate[$arraykey];
						$updateDT = substr($updateDT, 6, 19);
						$seconds = $updateDT / 1000;
						date("d-m-Y", $seconds);
						$mvIntegrationUpdate[$arraykey] = date("m-d-Y H:i:s T", $seconds);
					}
					$item->setData($arraykey,$mvIntegrationUpdate[$arraykey]);
				}
				$collection->addItem($item);
			}
		}
		
		return $collection;
	}
}