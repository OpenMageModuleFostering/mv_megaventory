<?php
class Mv_Megaventory_Model_Services_Api extends Mage_Api_Model_Resource_Abstract {
	
	public function updateMegaventoryStock($productSKUs, $inventoryValues) {
		
		/*
		 * $megaventoryIntegation =
		 * Mage::getStoreConfig('megaventory/general/enabled'); if
		 * ($megaventoryIntegation == '0') return true;
		 */
		if (! Mv_Megaventory_Helper_Common::isMegaventoryEnabled ())
			return false;
		
		Varien_Profiler::enable ();
		Varien_Profiler::start ( 'updateMegaventoryStock' );
		Mage::log ( 'count prouduct ids = ' . count ( $productSKUs ), null, 'api.log', true );
		Mage::log ( 'count inventoryValues  = ' . count ( $inventoryValues ), null, 'api.log', true );
		
		if (count ( $productSKUs ) != count ( $inventoryValues )) {
			$this->_fault ( 'multi_update_not_match' );
			return false;
		}
		
		Mage::log ( 'update megaventory stock called', null, 'api.log', true );
		
		$inventoryValues = ( array ) $inventoryValues;
		$json = json_encode ( $inventoryValues );
		Mage::log ( 'json = ' . $json, null, 'api.log', true );
		
		$inventoryValues = json_decode ( $json, true );
		$totalStock = 0;
		$productIds = array ();
		foreach ( $productSKUs as $index => $productSKU ) {
			
			$megaventoryId = $inventoryValues [$index] ['inventory_id'];
			Mage::log ( '$inventoryId = ' . $megaventoryId, null, 'api.log', true );
			
			Mage::log ( '$productSKU = ' . $productSKU, null, 'api.log', true );
			$stockData = $inventoryValues [$index] ['stock_data'];
			$productId = Mage::getModel ( "catalog/product" )->getIdBySku ( $productSKU );
			Mage::log ( '$productId = ' . $productId, null, 'api.log', true );
			Mage::log ( '$stockData = ' . implode ( '|', $stockData ), null, 'api.log', true );
			
			$stockKeys = array_keys ( $stockData );
			Mage::log ( '$stockKeys = ' . implode ( '|', $stockKeys ), null, 'api.log', true );
			
			if ($productId) {
				
				Mage::log ( 'product id not empty??', null, 'api.log', true );
				
				$inventories = Mage::helper ( 'megaventory/inventories' );
				
				$inventory = $inventories->getInventoryFromMegaventoryId ( $megaventoryId );
				
				if ($inventory != false) {
					Mage::log ( '$local inventory id = ' . $inventory->getId (), null, 'api.log', true );
					$inventories->updateInventoryProductStock ( $productId, $inventory->getId (), $stockData );
					
					// auto update global stock in the same pass
					/*
					 * $localInventoryId = $inventory->getId(); $inventoryStock
					 * = $stockData['stockqty']; $inventoryNonShippedStock =
					 * $stockData['stocknonshippedqty'];
					 * $inventoryNonAllocatedStock =
					 * $stockData['stocknonallocatedwoqty']; if
					 * (Mage::getModel('megaventory/inventories')->load($localInventoryId)->getCounts_in_total_stock()
					 * == '1') { $configValue =
					 * Mage::getStoreConfig('cataloginventory/options/can_subtract');
					 * if ($configValue == '0') //no decrease value when order
					 * is placed $totalStock+=$inventoryStock; else //decrease
					 * stock when order is placed { $totalStock +=
					 * $inventoryStock-$inventoryNonShippedStock-$inventoryNonAllocatedStock;
					 * } }
					 */
					// end of auto update
					
					$productIds [] = $productId;
				}
			}
		}
		
		foreach ( $productIds as $pId ) {
			Mage::log ( 'product id in inventory stock = ' . $pId, null, 'api.log', true );
			$productStockCollection = Mage::getModel ( 'megaventory/productstocks' )->loadProductstocks ( $pId );
			$totalStock = 0;
			foreach ( $productStockCollection as $key => $productStock ) {
				$inventoryStock = $productStock ['stockqty'];
				$inventoryNonShippedStock = $productStock ['stocknonshippedqty'];
				$inventoryNonAllocatedWOStock = $productStock ['stocknonallocatedwoqty'];
				
				$inventoryId = $productStock ['inventory_id'];
				$inventory = Mage::getModel ( 'megaventory/inventories' )->load ( $inventoryId );
				if ($inventory == false)
					continue;
				
				Mage::log ( 'total stock before update = ' . $totalStock, null, 'api.log', true );
				if ($inventory->getCounts_in_total_stock () == '1') {
					$configValue = Mage::getStoreConfig ( 'cataloginventory/options/can_subtract' );
					if ($configValue == '0') // no decrease value when order is
					                         // placed
						$totalStock += $inventoryStock;
					else 					// decrease stock when order is placed
					{
						$totalStock += $inventoryStock - $inventoryNonShippedStock - $inventoryNonAllocatedWOStock;
					}
				}
				Mage::log ( 'total stock after update = ' . $totalStock, null, 'api.log', true );
			}
			$stockItem = Mage::getModel ( 'cataloginventory/stock_item' )->loadByProduct ( $pId );
			Mage::log ( 'stock item id = ' . $stockItem->getId (), null, 'api.log', true );
			$stockItem->setQty ( $totalStock );
			if ($totalStock > $stockItem->getMinQty ())
				$stockItem->setData ( 'is_in_stock', 1 );
			
			Varien_Profiler::start ( 'savestock' );
			$stockItem->save ();
			Varien_Profiler::stop ( 'savestock' );
			Mage::log ( 'save magento stock timer ' . Varien_Profiler::fetch ( 'savestock' ), null, 'api.log', true );
		}
		
		Varien_Profiler::stop ( 'updateMegaventoryStock' );
		Mage::log ( 'update magento stock timer ' . Varien_Profiler::fetch ( 'updateMegaventoryStock' ), null, 'api.log', true );
		Varien_Profiler::disable ();
		return true;
	}

	public function megaventoryAddTrack($shipmentIncrementId, $carrier, $title, $trackNumber,$notify)
    {
		Mage::log('shipment increment id = '.$shipmentIncrementId,0,'api.log',true);
		Mage::log('carrier = '.$carrier,0,'api.log',true);
		Mage::log('title = '.$title,0,'api.log',true);
		Mage::log('track number = '.$trackNumber,0,'api.log',true);
		Mage::log('notify = '.$notify,0,'api.log',true);
    	
    	
    	$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentIncrementId);


        if (!$shipment->getId()) {
            $this->_fault('not_exists');
            return false;
        }

        $carriers = $this->_getCarriers($shipment);

        if (!isset($carriers[$carrier])) {
            $this->_fault('data_invalid', Mage::helper('sales')->__('Invalid carrier specified.'));
        }

        $track = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($trackNumber)
                    ->setCarrierCode($carrier)
                    ->setTitle($title);

        $shipment->addTrack($track);

        try {
            $shipment->save();
            $track->save();
            if ($notify == 1){
            	$shipment->sendEmail(true);
				Mage::log('notify = '.$notify,0,'api.log',true);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
            return false;
        }

        return $track->getId();
    }
    
    protected function _getCarriers($object)
    {
    	$carriers = array();
    	$carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers(
    			$object->getStoreId()
    	);
    
    	$carriers['custom'] = Mage::helper('sales')->__('Custom Value');
    	foreach ($carrierInstances as $code => $carrier) {
    		if ($carrier->isTrackingAvailable()) {
    			$carriers[$code] = $carrier->getConfigData('title');
    		}
    	}
    
    	return $carriers;
    }
}