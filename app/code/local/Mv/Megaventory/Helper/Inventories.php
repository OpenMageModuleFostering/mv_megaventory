<?php

class Mv_Megaventory_Helper_Inventories extends Mage_Core_Helper_Abstract
{
	public function getInventories()
	{
		return Mage::getModel('megaventory/inventories')->getCollection()->load();
	}
	
	public function getInventoriesFromMegaventory($apikey = false, $apiurl = false, $enabled = -1)
	{
		if ($apikey  != false)
			$key = $apikey;
		else
			$key = Mage::getStoreConfig('megaventory/general/apikey');
		
		
		$data = array
		(
				'APIKEY' => $key
		);
			
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'InventoryLocationGet',0, $apiurl, $enabled);
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0')
			return false;
		
		try{
			$mvInventoryLocations = $json_result['mvInventoryLocations'];
		}
		catch (Exception $ex){
			return false;
		}
		
		return count($mvInventoryLocations);
	}
	
	public function syncrhonizeInventories($apikey = -1, $apiurl = -1)
	{
		if ($apikey  != -1)
			$key = $apikey;
		else
			$key = Mage::getStoreConfig('megaventory/general/apikey');
			
		$data = array
		(
				'APIKEY' => $key
		);
			
		$helper = Mage::helper('megaventory');
		
		try{
			$json_result = $helper->makeJsonRequest($data ,'InventoryLocationGet',0, $apiurl);
		}
		catch (Exception $ex){
			return false;
		}
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0')
			return false;
	
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		
		$mvInventoryLocations = $json_result['mvInventoryLocations'];
		
		
		$i = 0;
		$result = -1;
		foreach($mvInventoryLocations as $mvInventory)
		{
			$inventory = $this->checkIfInventoryExists($mvInventory);
			if ($inventory == false){
				$this->insertInventory($mvInventory);
			}
			else
			 	$this->updateInventory($inventory, $mvInventory);
			
			$mvIds[] = $mvInventory['InventoryLocationID'];
			$i++;
		}
		
		if (count($mvIds) > 0)
			$this->deleteNotExistentInventories($mvIds);
		
		/* 
		 * In transit location is handled only by Megaventory
		 * Inventory updates for inventory In Transit (hard coded id = -1)
		 * will be ignored
		 * 
		 * $inTransitInventory = array();
		$inTransitInventory['InventoryLocationID'] = -1;
		$inTransitInventory['InventoryLocationName'] = 'In Transit';
		$inTransitInventory['InventoryLocationAbbreviation'] = 'Trans';
		$inTransitInventory['InventoryLocationAddress'] = '';
		$this->insertInventory($inTransitInventory); */
		
		
		$this->updateAllStock();
		
		
		if ($i>0)
			return $i;

		return $result;
	}
	
	public function updateInventoryLocations($apikey = -1, $apiurl = -1)
	{
		if ($apikey  != -1)
			$key = $apikey;
		else
			$key = Mage::getStoreConfig('megaventory/general/apikey');
			
		$data = array
		(
				'APIKEY' => $key
		);
		
			
		$helper = Mage::helper('megaventory');
	
		try{
			$json_result = $helper->makeJsonRequest($data ,'InventoryLocationGet',0, $apiurl);
		}
		catch (Exception $ex){
			return -1;
		}
	
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0')
			return -1;
	
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
	
		$mvInventoryLocations = $json_result['mvInventoryLocations'];
	
	
		$i = 0;
		$result = -1;
		$mvIds = array();
		foreach($mvInventoryLocations as $mvInventory)
		{
			$inventory = $this->checkIfInventoryExists($mvInventory);
			if ($inventory == false){
				$this->insertInventory($mvInventory);
			}
			else
			 	$this->updateInventory($inventory, $mvInventory);
			
			$mvIds[] = $mvInventory['InventoryLocationID'];
			$i++;
		}
		
		if (count($mvIds) > 0)
			$this->deleteNotExistentInventories($mvIds);
	
		if ($i>0)
			return $i;
	
		return $result;
	}
	
	//called by synchronization module when initializing inventories
	//if inventories found in megaventory it assings the first one as default
	public function initializeInventoryLocations($apikey = -1, $apiurl = -1)
	{
		if ($apikey  != -1)
			$key = $apikey;
		else
			$key = Mage::getStoreConfig('megaventory/general/apikey');
			
		$data = array
		(
				'APIKEY' => $key
		);
	
			
		$helper = Mage::helper('megaventory');
	
		try{
			$json_result = $helper->makeJsonRequest($data ,'InventoryLocationGet',0, $apiurl);
		}
		catch (Exception $ex){
			return -1;
		}
	
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0')
			return -1;
	
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
	
		$mvInventoryLocations = $json_result['mvInventoryLocations'];
	
	
		$i = 0;
		$result = -1;
		$mvIds = array();
		foreach($mvInventoryLocations as $mvInventory)
		{
			if ($i == 0)
				$this->insertInventory($mvInventory,true);
			else
				$this->insertInventory($mvInventory);
							
			$i++;
		}
	
		if ($i>0)
			return $i;
	
		return $result;
	}
	
	public function createMainInventory(){
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'mvInventoryLocation' => array(
						'InventoryLocationID' => '0',
						'InventoryLocationName' => 'Main Inventory',
						'InventoryLocationAbbreviation' => 'Main',
						'InventoryLocationAddress' => '',
						'InventoryLocationCurrencyCode' => ''
						),
				'mvRecordAction' => 'Insert'
		);
		
		$helper = Mage::helper('megaventory');
		try{
			$json_result = $helper->makeJsonRequest($data ,'InventoryLocationUpdate',0);
		}
		catch (Exception $ex){
			return 'There was a problem connecting to your Megaventory account. Please try again.';
		}
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0')
			return $json_result['ResponseStatus']['Message'];
		
		$this->insertInventory($json_result['mvInventoryLocation'],true);
		
		
		return true;
	}
	
	public function makeDefaultInventory($inventoryId){
		if (isset($inventoryId)){
			$resource = Mage::getSingleton ( 'core/resource' );
			$write = $resource->getConnection ( 'core/write' );
			$tableName = $resource->getTableName('megaventory_inventories');
		
			$noDefault = 'update '.$tableName.' set isdefault = 0';
			$write->query($noDefault);
			$makeefault = 'update '.$tableName.' set isdefault = 1, counts_in_total_stock = 1 where id = '.$inventoryId;
			$write->query($makeefault);
		}
	}
	
	
	private function insertInventory($mvInventory,$default = false){
		$mvInventoryLocationID = $mvInventory['InventoryLocationID'];
		$mvInventoryLocationName = $mvInventory['InventoryLocationName'];
		$mvInventoryLocationAbbreviation = $mvInventory['InventoryLocationAbbreviation'];
		$mvInventoryLocationAddress = $mvInventory['InventoryLocationAddress'];
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$tableName = $resource->getTableName('megaventory_inventories');
		if ($default == false)
			$sql_insert = 'insert into '.$tableName.' (name, shortname, address,megaventory_id, counts_in_total_stock) values ("'.$mvInventoryLocationName.'","'.$mvInventoryLocationAbbreviation.'","'.$mvInventoryLocationAddress.'","'.$mvInventoryLocationID.'","0")';
		else
			$sql_insert = 'insert into '.$tableName.' (name, shortname, address,megaventory_id, isdefault) values ("'.$mvInventoryLocationName.'","'.$mvInventoryLocationAbbreviation.'","'.$mvInventoryLocationAddress.'","'.$mvInventoryLocationID.'","1")';
		$write->query($sql_insert);
	}
	
	private function updateInventory($inventory, $mvInventory){
		$inventory->setData('shortname',$mvInventory['InventoryLocationAbbreviation']);
		$inventory->setData('name',$mvInventory['InventoryLocationName']);
		$inventory->setData('address',$mvInventory['InventoryLocationAddress']);
		$inventory->setData('InventoryLocationAddress', $mvInventory['InventoryLocationAddress']);
		$inventory->save();
	
	}
	
	private function deleteNotExistentInventories($mvIds)
	{
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$tableName = $resource->getTableName('megaventory_inventories');
		
		$sqlDelete = 'delete from '.$tableName.' where megaventory_id not in ('.implode(',', $mvIds).')';
		$write->query($sqlDelete);
		
		$inventory = Mage::getModel('megaventory/inventories')->loadDefault();
		
		if (!$inventory ->getId() && count($mvIds) > 0) { //there is no default inventory
			$newDefaultInventory = Mage::getModel('megaventory/inventories')->load($mvIds[0],'megaventory_id');
			$newDefaultInventory->setData('isdefault','1');
			$newDefaultInventory->setData('counts_in_total_stock','1');
			$newDefaultInventory->save();
		}
	}
	
	private function checkIfInventoryExists($mvInventory){
		
		$inventory = Mage::getModel('megaventory/inventories')->load($mvInventory['InventoryLocationID'], 'megaventory_id');
		$id = $inventory->getData('id');
		if (!isset($id))
			return false;
		
		return $inventory;
	}
	
	public function getInventoryFromMegaventoryId($mvInventoryId){
		$inventory = Mage::getModel('megaventory/inventories')->load($mvInventoryId, 'megaventory_id');

		if ($inventory->getId() == false)
			return false;
		
		return $inventory;
	}
	
	
	public function updateInventoryProductStock($productId, $inventoryId, $stockData,$parentId = false){
		
		if (isset($productId) && isset($inventoryId))
		{
			$productStock = Mage::getModel('megaventory/productstocks')
			->loadInventoryProductstock($inventoryId, $productId);
			Mage::log('stock data qty = '.$stockData['stockqty'],null,'megaventory.log',true);
		
			$productStock->setProduct_id($productId);
			$productStock->setInventory_id($inventoryId);
			$productStock->setStockqty($stockData['stockqty']);
			$productStock->setStockqtyonhold($stockData['stockqtyonhold']);
			$productStock->setStockalarmqty($stockData['stockalarmqty']);
			$productStock->setStocknonshippedqty($stockData['stocknonshippedqty']);
			$productStock->setStocknonreceivedqty($stockData['stocknonreceivedqty']);
			$productStock->setStockwipcomponentqty($stockData['stockwipcomponentqty']);
			$productStock->setStocknonreceivedwoqty($stockData['stocknonreceivedwoqty']);
			$productStock->setStocknonallocatedwoqty($stockData['stocknonallocatedwoqty']);
			if ($parentId != false)
				$productStock->setParent_id($parentId);
			
			$productStock->save();
			
			return true;
		}
		
		return false;
	}
	
	public function updateInventoryProductAlertValue($productId, $inventoryId, $alertValue){
	
		if (isset($productId) && isset($inventoryId))
		{
			$productStock = Mage::getModel('megaventory/productstocks')
			->loadInventoryProductstock($inventoryId, $productId);
			
			$productStock->setProduct_id($productId);
			$productStock->setInventory_id($inventoryId);
			$productStock->setStockalarmqty($alertValue);
			$productStock->save();
			
			$stockItem = Mage::getModel ( 'cataloginventory/stock_item' )->loadByProduct($productId);
			
			$productStockCollection = Mage::getModel ( 'megaventory/productstocks' )->loadProductstocks ($productId);
			
			$totalAlertQuantity = 0;
			foreach ( $productStockCollection as $key => $productStock ) {
				$inventoryAlertQty = $productStock ['stockalarmqty'];
					
				$inventory = Mage::getModel ( 'megaventory/inventories' )->load ( $inventoryId );
				if ($inventory == false)
					continue;
					
				if ($inventory->getCounts_in_total_stock () == '1') {
					$totalAlertQuantity += $inventoryAlertQty;
				}
				Mage::log ( 'total stock after update = ' . $totalStock, null, 'api.log', true );
				Mage::log ( 'total alert quantity after update = ' . $totalAlertQuantity, null, 'api.log', true );
			}
			
			//update notify quantity
			$useConfigNotify = $stockItem->getData('use_config_notify_stock_qty');
			$configValue = Mage::getStoreConfig('cataloginventory/item_options/notify_stock_qty');
			
			if ($useConfigNotify == '1'){
				if (isset($configValue)){
					if ($configValue != $totalAlertQuantity){
						$stockItem->setData('use_config_notify_stock_qty',0);
					}
				}
			}
			else
			{
				if (isset($configValue)){
					if ($configValue == $totalAlertQuantity){
						$stockItem->setData('use_config_notify_stock_qty',1);
					}
				}
			}
			
			$stockItem->setData('notify_stock_qty',$totalAlertQuantity);
			$stockItem->save();
			//end of notify quantity
			
			return array(
					'totalAlertQuantity' => $totalAlertQuantity,
					'isConfig' => ($totalAlertQuantity == $configValue) ? true : false
					);
		}
	
		return array(
					'totalAlertQuantity' => 0,
					'isConfig' => false
					);
	}
	
	public function updateCountsInStock($inventoryId, $bCount){
		$inventory = Mage::getModel('megaventory/inventories')->load($inventoryId);
		$bCount == 'true' ? $countsInStock = '1' : $countsInStock = '0';
		$inventory->setCounts_in_total_stock($countsInStock);
		$inventory->save();
		
		//call to update global stock when checkbox checks/unchecks is commented out
		
		/* $megaventoryHelper = Mage::helper('megaventory');
		$key = Mage::getStoreConfig('megaventory/general/apikey');
		
		$inventoryCountsCollection = Mage::getModel('megaventory/inventories')->getCollection()
		->addFieldToFilter('counts_in_total_stock', array('eq' => '1'));
		
		$inventoryCounts = array();
		foreach ($inventoryCountsCollection as $inventoryCountsCollectionItem){
			$inventoryCounts[] = (int)($inventoryCountsCollectionItem->getData('megaventory_id'));
		}
		
		if (count($inventoryCounts) > 0) //if there is an inventory that counts
		{
			$data = array
			(
				'APIKEY' => $key,
				'InventoryLocationID' => $inventoryCounts,
				'ShowOnlyProductsWithPositiveQty' => false //set this to false to fetch ALL products and update magento quantities 
			);
			
			
			$json_result = $megaventoryHelper->makeJsonRequest($data ,'InventoryLocationStockGet',0);
			
			if ($json_result != false){
				$productStockList = $json_result['mvProductStockList'];
				$inventoryStockData = array();
				$configValue = Mage::getStoreConfig('cataloginventory/options/can_subtract');
				foreach ($productStockList as $productStockListItem){
					Mage::log('megaventory product id = '.$productStockListItem['productID'], null, 'megaventory.log', true);
					$pId = $this->getIdByMegaventoryId($productStockListItem['productID']);
					Mage::log('magento product id = '.$pId, null, 'megaventory.log', true);
					if (empty($pId) || $pId == false)
						continue;
					
					if ($configValue == '0') //no decrease value when order is placed
						$totalStock = $productStockListItem['StockPhysicalTotal'];
					else //decrease stock when order is placed
					{
						$totalStock = $productStockListItem['StockPhysicalTotal']-
						$productStockListItem['StockNonShippedTotal']-$productStockListItem['StockNonAllocatedWOsTotal'];
					}
					
					$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($pId);
					Mage::log('stock item id = '.$stockItem->getId().' total stock = '.$totalStock, null, 'megaventory.log', true);
					$stockItem->setQty($totalStock);
					if ($totalStock > $stockItem->getMinQty())
						$stockItem->setData('is_in_stock',1);
					
					$stockItem->save();
					
					if ($bCount == true){
						$warehouseStocks = $productStockListItem['mvStock'];
						foreach ($warehouseStocks as $warehouseStock)
						{
							if ($warehouseStock['warehouseID'] == $inventory['megaventory_id'])
							{
								$inventoryStockData['stockqty'] = $warehouseStock['StockPhysical'];
								$inventoryStockData['stockqtyonhold']= $warehouseStock['StockOnHold'];
								$inventoryStockData['stocknonshippedqty'] = $warehouseStock['StockNonShipped'];
								$inventoryStockData['stocknonallocatedwoqty'] = $warehouseStock['StockNonAllocatedWOs'];
								$inventoryStockData['stocknonreceivedqty'] = $warehouseStock['StockNonReceivedPOs'];
								$inventoryStockData['stockwipcomponentqty'] = 0;
								$inventoryStockData['stocknonreceivedwoqty'] = $warehouseStock['StockNonReceivedWOs'];
								$inventoryStockData['stockalarmqty'] = $warehouseStock['StockAlertLevel'];
								break;
							}
						}
						
						$this->updateInventoryProductStock($pId,$inventoryId,$inventoryStockData);
					}
				}
				if ($bCount == false)
				{
					$this->removeAllStockFromInventory($inventory->getId());
				}
			}
		}
		else //make all 0
		{
			$model = Mage::getModel('catalog/product');
			$productStockList = $model->getCollection()
			->addFieldToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
			->addAttributeToFilter(
					array(
							array('attribute' => 'type_id', 'eq' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE),
							array('attribute' => 'type_id', 'eq' => Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL)
					))
					->addAttributeToSort('type_id','ASC');
			
			foreach ($productStockList as $productStockListItem){
				$megaventoryProductId = $productStockListItem['mv_product_id'];
				$pId = $productStockListItem->getId();
				Mage::log('magento  product id = '.$pId, null, 'megaventory.log', true);
				Mage::log('megaventory product id = '.$megaventoryProductId, null, 'megaventory.log', true);
				if (empty($pId) || $pId == false || empty($megaventoryProductId) || $megaventoryProductId == false)
					continue;
					
				$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($pId);
				Mage::log('stock item id = '.$stockItem->getId().' total stock = 0', null, 'megaventory.log', true);
				$stockItem->setQty(0);
				$stockItem->save();
			}
			
			$this->removeAllStockFromInventory($inventory->getId());
		} */
		
	}
	
	public function updateAllStock()
	{
		
		$megaventoryHelper = Mage::helper('megaventory');
		$key = Mage::getStoreConfig('megaventory/general/apikey');
		
		$data = array
		(
				'APIKEY' => $key
		);
		
		$json_result = $megaventoryHelper->makeJsonRequest($data ,'InventoryLocationStockGet',0);
			
		if ($json_result != false){
			$productStockList = $json_result['mvProductStockList'];
			$inventoryStockData = array();
			$configValue = Mage::getStoreConfig('cataloginventory/options/can_subtract');
			foreach ($productStockList as $productStockListItem){
				Mage::log('megaventory product id = '.$productStockListItem['productID'], null, 'megaventory.log', true);
				$pId = $this->getIdByMegaventoryId($productStockListItem['productID']);
				Mage::log('magento product id = '.$pId, null, 'megaventory.log', true);
				if (empty($pId) || $pId == false)
					continue;
					
				$warehouseStocks = $productStockListItem['mvStock'];
				$totalStock = 0;
				foreach ($warehouseStocks as $warehouseStock)
				{
					$inventoryStockData['stockqty'] = $warehouseStock['StockPhysical'];
					$inventoryStockData['stockqtyonhold']= $warehouseStock['StockOnHold'];
					$inventoryStockData['stocknonshippedqty'] = $warehouseStock['StockNonShipped'];
					$inventoryStockData['stocknonallocatedwoqty'] = $warehouseStock['StockNonAllocatedWOs'];
					$inventoryStockData['stocknonreceivedqty'] = $warehouseStock['StockNonReceivedPOs'];
					$inventoryStockData['stockwipcomponentqty'] = 0;
					$inventoryStockData['stocknonreceivedwoqty'] = $warehouseStock['StockNonReceivedWOs'];
					$inventoryStockData['stockalarmqty'] = $warehouseStock['StockAlertLevel'];
					
					//warehouseID changed in megaventory API v2
					//we need to be compliant with both versions
					$locationId = $warehouseStock['warehouseID'];
					if (!isset($locationId)){ //v2 api
						$locationId = $warehouseStock['InventoryLocationID'];
					}
					$inventory = Mage::getModel('megaventory/inventories')->load($locationId, 'megaventory_id');
					$inventoryId = $inventory->getData('id');
					$this->updateInventoryProductStock($pId,$inventoryId,$inventoryStockData);
					
					$countsInTotalStock = $inventory->getCounts_in_total_stock();
					if ($countsInTotalStock == '1')
					{
						if ($configValue == '0') //no decrease value when order is placed
							$totalStock += $inventoryStockData['stockqty'];
						else //decrease stock when order is placed
						{
							$totalStock += $inventoryStockData['stockqty']-$inventoryStockData['stocknonshippedqty']-$inventoryStockData['stocknonallocatedwoqty'];
						}
					}
				}
				$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($pId);
				Mage::log('stock item id = '.$stockItem->getId().' total stock = '.$totalStock, null, 'megaventory.log', true);
				$stockItem->setQty($totalStock);
				if ($totalStock > $stockItem->getMinQty())
					$stockItem->setData('is_in_stock',1);
				$stockItem->save();
			}
		}
	}
	
	public function removeAllStockFromInventory($inventoryId)
	{
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$tableName = $resource->getTableName('megaventory_stock');
		$sql_delete = 'delete from '.$tableName.' where mv_inventory_id = '.$inventoryId;
		$write->query($sql_delete);
	}
	
	
	public function getIdByMegaventoryId($mvId)
	{
		$resource = Mage::getSingleton('core/resource');
		$adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
		$tableName = $resource->getTableName('catalog_product_entity');
		$select = $adapter->select()
		->from($tableName, 'entity_id')
		->where('mv_product_id = :mv_product_id');
	
		$bind = array(':mv_product_id' => $mvId);
	
		return $adapter->fetchOne($select, $bind);
	}
}
