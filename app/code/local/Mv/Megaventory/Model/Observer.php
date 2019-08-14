<?php
class Mv_Megaventory_Model_Observer
{
  public function onLoginSuccess($observer)
	{
		$event = $observer->getEvent ();
		$user = $event->getUser();
		
		
		$apikey = Mage::getStoreConfig('megaventory/general/apikey');
		$apiurl = Mage::getStoreConfig('megaventory/general/apiurl');
		
		if (empty($apikey) || empty($apiurl))
			return;
		
		$adminSession = Mage::getSingleton('admin/session');
		$megaventoryHelper = Mage::helper('megaventory');
		$accountSettings = $megaventoryHelper->getMegaventoryAccountSettings('All');
		
		foreach ($accountSettings as $index => $accountSetting) {
			$settingName = $accountSetting['SettingName'];
			$settingValue = $accountSetting['SettingValue'];
			$adminSession->setData('mv_'.$settingName,$settingValue);
		}
		
	}
	
	//updates magento data asyncrhrously
	//receives input by polling megaventory integration updates API
	public function update()
	{
		Mage::log('update run',null,'mv_cron.log');
		
		$key = Mage::getStoreConfig('megaventory/general/apikey');
			
		$data = array
		(
				'APIKEY' => $key
		);
			
		$helper = Mage::helper('megaventory');
		
		try{
			$json_result = $helper->makeJsonRequest($data ,'IntegrationUpdateGet',0);
		}
		catch (Exception $ex){
			Mage::log('exception',null,'mv_cron.log');
			return;
		}
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0'){
			Mage::log('error',null,'mv_cron.log');
			Mage::log($errorCode,null,'mv_cron.log');
			Mage::log($json_result['ResponseStatus']['Message'],null,'mv_cron.log');
			return;
		}
		
		$mvIntegrationUpdates = $json_result['mvIntegrationUpdates'];
		
		foreach($mvIntegrationUpdates as $mvIntegrationUpdate)
		{
			$result = false;
			Mage::log('integration update id : '.$mvIntegrationUpdate['IntegrationUpdateID'],null,'mv_cron.log');
			Mage::log('action : '.$mvIntegrationUpdate['Action'],null,'mv_cron.log');
			Mage::log('entity : '.$mvIntegrationUpdate['Entity'],null,'mv_cron.log');
			Mage::log('entity ids : '.$mvIntegrationUpdate['EntityIDs'],null,'mv_cron.log');
			$entityIDs = explode('##$', $mvIntegrationUpdate['EntityIDs']);
			Mage::log('tries : '.$mvIntegrationUpdate['Tries'],null,'mv_cron.log');
			$mvIntegrationUpdateId = $mvIntegrationUpdate['IntegrationUpdateID'];
			$tries = $mvIntegrationUpdate['Tries'];
			
			//delete if failed more than 10 times
			if ($tries > 10){
				$this->deleteUpdate($mvIntegrationUpdateId);
			}
		
			if ($mvIntegrationUpdate['Entity'] == 'product'){
				$product = json_decode($mvIntegrationUpdate['JsonData'],true);
				
				$productHelper = Mage::helper('megaventory/product');
				
				if ($mvIntegrationUpdate['Action'] == 'update'){
					
				}
				else if ($mvIntegrationUpdate['Action'] == 'delete'){
					
				}
				else if ($mvIntegrationUpdate['Action'] == 'insert'){
					
				}
			}
			else if ($mvIntegrationUpdate['Entity'] == 'sales_order'){
				try{
					$orderApi = new Mage_Sales_Model_Order_Api();
					$shipmentApi = new Mage_Sales_Model_Order_Shipment_Api();
					$invoiceApi = new Mage_Sales_Model_Order_Invoice_Api();
					$mvApi = new Mv_Megaventory_Model_Services_Api();
					for ($i = 0; $i < count($entityIDs);$i++){
						$orderIncrementId = $entityIDs[$i];
						if ($mvIntegrationUpdate['Action'] == 'complete'){
							$result = $orderApi->addComment($orderIncrementId, 'complete', '', false);
							Mage::log('result : '.$result,null,'mv_cron.log');
							if ($result){
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
							else
							{
								if ($tries > 10)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else{
									$mvIntegrationUpdate['Tries'] = $tries+1;
									$mvIntegrationUpdate['payload'] = $result;
									$this->updateIntegrationUpdate($mvIntegrationUpdate);
								}
							}
						}
						else if ($mvIntegrationUpdate['Action'] == 'cancel'){
							$result = $orderApi->cancel($orderIncrementId);
							Mage::log('result : '.$result,null,'mv_cron.log');
							if ($result){
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
							else
							{
								if ($tries > 10)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else{
									$mvIntegrationUpdate['Tries'] = $tries+1;
									$mvIntegrationUpdate['payload'] = $result;
									$this->updateIntegrationUpdate($mvIntegrationUpdate);
								}
							}
						}
						else if ($mvIntegrationUpdate['Action'] == 'invoice'){
							$invoiceIncrementId = $invoiceApi->create($orderIncrementId, null);
							
							$invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoiceIncrementId);
								
							if ($invoice->canCapture()) {
								$invoiceApi->capture($invoiceIncrementId);
								Mage::log('captured invoice : '.$invoiceIncrementId,null,'mv_cron.log');
							}
							
							$result = $invoiceIncrementId;
							Mage::log('result : '.$result,null,'mv_cron.log');
							if ($result) 
								$this->deleteUpdate($mvIntegrationUpdateId);
							else
							{
								if ($tries > 10)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else{
									$mvIntegrationUpdate['Tries'] = $tries+1;
									$mvIntegrationUpdate['payload'] = $result;
									$this->updateIntegrationUpdate($mvIntegrationUpdate);
								}
							}
						}
						else if ($mvIntegrationUpdate['Action'] == 'ship'){
							$jsonData = $mvIntegrationUpdate['JsonData'];
							$extraShippingInformation = json_decode($jsonData, true);
							if ($extraShippingInformation['Notify'] == '1') //then also send a shipment email
								$shipmentIncrementId = $shipmentApi->create($orderIncrementId,null,'',true,false);
							else								
								$shipmentIncrementId = $shipmentApi->create($orderIncrementId);
							
							$result = $shipmentIncrementId;
							Mage::log('result : '.$result,null,'mv_cron.log');
							if ($result){
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
							else
							{
								if ($tries > 10)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else{
									$mvIntegrationUpdate['Tries'] = $tries+1;
									$mvIntegrationUpdate['payload'] = $result;
									$this->updateIntegrationUpdate($mvIntegrationUpdate);
								}
							}
						}
						else if ($mvIntegrationUpdate['Action'] == 'track'){
							$jsonData = $mvIntegrationUpdate['JsonData'];
							
							$result = false;
								
							$shipmentIncrementId= "";
							$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
							$orderId = $order->getId();
								
							$filters = array(
									"order_id" => $orderId
							);
							$shipments = $shipmentApi->items($filters);
							
							if (count($shipments) > 0 )
							{
								$shipment = $shipments[0];
								$shipmentIncrementId = $shipment['increment_id'];
								Mage::log('order has shipment with id : '.$shipmentIncrementId,null,'mv_cron.log');
							}
							
							if ($jsonData && !empty($shipmentIncrementId))
							{
								$trackingInformation = json_decode($jsonData, true);
								$result = $mvApi->megaventoryAddTrack($shipmentIncrementId, 'custom', $trackingInformation['ShippingProviderName'],
										$trackingInformation['TrackNumber'],$trackingInformation['Notify']);
							}
							Mage::log('result : '.$result,null,'mv_cron.log');
							if ($result){
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
							else
							{
								if ($tries > 10)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else{
									$mvIntegrationUpdate['Tries'] = $tries+1;
									$mvIntegrationUpdate['payload'] = $result;
									$this->updateIntegrationUpdate($mvIntegrationUpdate);
								}
							}
						}
						else if ($mvIntegrationUpdate['Action'] == 'partially_process'){
							$result = $orderApi->addComment($orderIncrementId, 'processing', '', false);
							Mage::log('result : '.$result,null,'mv_cron.log');
							if ($result) {
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
							else
							{
								if ($tries > 10)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else{
									$mvIntegrationUpdate['Tries'] = $tries+1;
									$mvIntegrationUpdate['payload'] = $result;
									$this->updateIntegrationUpdate($mvIntegrationUpdate);
								}
							}
						}
					}
				}
				catch (Exception $e){
					Mage::log('exception message: '.$e->getMessage(),null,'mv_cron.log');
					$mvIntegrationUpdate['Tries'] = $tries+1;
					$this->updateIntegrationUpdate($mvIntegrationUpdate);
				}
			}
			else if ($mvIntegrationUpdate['Entity'] == 'stock'){
				try{
					$mvApi = new Mv_Megaventory_Model_Services_Api();
					$inventoryValues = json_decode($mvIntegrationUpdate['JsonData']);
					$result = $mvApi->updateMegaventoryStock($entityIDs, $inventoryValues);
					Mage::log('result : '.$result,null,'mv_cron.log');
					
					if ($result)
						$this->deleteUpdate($mvIntegrationUpdateId);
					else
					{
						$mvIntegrationUpdate['Tries'] = $tries+1;
						$mvIntegrationUpdate['payload'] = $result;
						$this->updateIntegrationUpdate($mvIntegrationUpdate);
					}
				}
				catch (Exception $e){
					Mage::log('exception message: '.$e->getMessage(),null,'mv_cron.log');
					$mvIntegrationUpdate['Tries'] = $tries+1;
					$this->updateIntegrationUpdate($mvIntegrationUpdate);
				}
			}
			else { //NOT HANDLED UPDATE SHOULD BE DELETED
				try{
					$this->deleteUpdate($mvIntegrationUpdateId);
				}
				catch (Exception $e){
					Mage::log('exception message: '.$e->getMessage(),null,'mv_cron.log');
				}
			}
		}
	}
	
	//double checks order synchronization
	//runs every 10 minutes and checks last half hour orders
	//to resynchronize (if required) not synched orders
	public function checkOrderSynchronization()
	{
		Mage::log('check orders run',null,'mv_cron.log');

		$orderSynchronization = Mage::getStoreConfig('megaventory/general/ordersynchronization');
		if (empty($orderSynchronization) || $orderSynchronization === '0'){
			Mage::log('order synchronization is disabled',null,'mv_cron.log');
			return;
		}
		
		/* Format our dates */
		$fromDate = date('Y-m-d H:i:s',strtotime("-30 minutes"));
		$toDate = date('Y-m-d H:i:s', strtotime("now"));
		

		Mage::log('from date '.$fromDate,null,'mv_cron.log');
		Mage::log('to date '.$toDate,null,'mv_cron.log');
		
		/* Get the collection */
		$orders = Mage::getModel('sales/order')->getCollection()
		->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))
		->addAttributeToFilter('status', array('in' => array('pending','processing')));
		
		foreach ($orders as $order){
			$order = Mage::getModel('sales/order')->load($order->getId());
			Mage::log('order : '.$order->getIncrementId(),null,'mv_cron.log');
			if ($order->getData('mv_inventory_id') == false){
				Mage::log('not syncrhonized order : '.$order->getIncrementId(),null,'mv_cron.log');
				$quote = Mage::getModel('sales/quote');
				$website = Mage::getModel('core/website')->load($order->getStore()->getWebsite_id());
				$quote->setData('website',$website);
				$quote = $quote->load($order->getQuote_id());
				
				if ($quote->getId())
					Mage::helper('megaventory/order')->addOrder($order,$quote);
			}
		}
		
		Mage::log('check orders finished',null,'mv_cron.log');
		
	}
	
	private function deleteUpdate($mvIntegrationUpdateId){

		Mage::log('delete update id : '.$mvIntegrationUpdateId,null,'mv_cron.log');
		$key = Mage::getStoreConfig('megaventory/general/apikey');
		
		$data = array
		(
				'APIKEY' => $key,
				'IntegrationUpdateIDToDelete' => $mvIntegrationUpdateId
		);
		
		$helper = Mage::helper('megaventory');
		
		$json_result = $helper->makeJsonRequest($data ,'IntegrationUpdateDelete',0);
	}
	
	private function updateIntegrationUpdate($mvIntegrationUpdate){
	
		Mage::log('update update id : '.$mvIntegrationUpdateId,null,'mv_cron.log');
		$key = Mage::getStoreConfig('megaventory/general/apikey');
	
		$data = array
		(
				'APIKEY' => $key,
				'mvIntegrationUpdate' => $mvIntegrationUpdate,
				'mvRecordAction' => 'Update'
		);
	
		$helper = Mage::helper('megaventory');
	
		$json_result = $helper->makeJsonRequest($data ,'IntegrationUpdateUpdate',0);
	}
}
?>