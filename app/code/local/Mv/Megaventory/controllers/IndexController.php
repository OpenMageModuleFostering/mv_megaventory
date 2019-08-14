<?php
class Mv_Megaventory_IndexController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$this->loadLayout();

        
		$block = $this->getLayout()
		->createBlock('mv_megaventory/adminhtml_megaventorysettings', 'megaventorysettings')
		->setTemplate('megaventory/megaventory.phtml');
		$this->_addContent($block);
		
        $this->_setActiveMenu('megaventory_menu');
        
        
        $this->renderLayout();

	}
	
	public function updatesAction()
	{
		$this->loadLayout();
	
		/* $syncModel = Mage::getModel('megaventory/observer');
		
		$syncModel->update();  */
	
		$this->renderLayout();
	
	}
	
	public function processupdatesAction()
	{
		$this->loadLayout();
	
		$syncModel = Mage::getModel('megaventory/observer');
	
		$syncModel->update();
	
		$this->renderLayout();
	
	}
	
	public function saveSettingsAction()
	{
		$this->loadLayout();
		
		
		$config = Mage::getConfig();
		$apikey = $this->getRequest()->getPost('megaventory_apikey');
		$config->saveConfig('megaventory/general/apikey',$apikey);
		$apiurl = $this->getRequest()->getPost('megaventory_apiurl');
		$config->saveConfig('megaventory/general/apiurl',$apiurl);
		
		//unfortunately
		$config->reinit();
		
		$block = $this->getLayout()
		->createBlock('mv_megaventory/adminhtml_megaventorysettings', 'megaventorysettings')
		->setTemplate('megaventory/megaventory.phtml');
		$this->_addContent($block);
	
		$this->_setActiveMenu('megaventory_menu');
	
		$this->renderLayout();
	}
	
	public function setShippingAndDiscountSKUsAction()
	{
		$shippingSKU = $this->getRequest()->getPost('shippingSKU');
		$discountSKU = $this->getRequest()->getPost('discountSKU');
		$config = Mage::getConfig();
		$config->saveConfig('megaventory/general/shippingproductsku',$shippingSKU);
		$config->saveConfig('megaventory/general/discountproductsku',$discountSKU);
	
		//unfortunately
		$config->reinit();
	
		die();
	}
	
	public function checkConnectivityAction()
	{
		$apikey = $this->getRequest()->getPost('megaventory_apikey');
		$apiurl = $this->getRequest()->getPost('megaventory_apiurl');
		$enabled = $this->getRequest()->getPost('megaventory_enabled');
		
		
		$config = Mage::getConfig();
		$config->saveConfig('megaventory/general/enabled',$enabled);
		$config->saveConfig('megaventory/general/apikey',$apikey);
		$config->saveConfig('megaventory/general/apiurl',$apiurl);
		$config->reinit();
		if ($enabled == 0){
			$result = array('connectivity'=>'notenabled');
			echo json_encode($result) . PHP_EOL;
			die();
		}
		
		
		$megaventoryHelper = Mage::helper('megaventory');
		$accountSettings = $megaventoryHelper->getMegaventoryAccountSettings();
		
		if ($accountSettings === false) //connectivity problem
		{
			$result = array('connectivity'=>'notok', 'message' => 'There is a problem with your megaventory credentials!', 'type' => 'fromMagento');
			echo json_encode($result) . PHP_EOL;
			die();
		}
		else
		{
			$adminSession = Mage::getSingleton('admin/session');
			$message = '';
			foreach ($accountSettings as $index => $accountSetting) {
				$settingName = $accountSetting['SettingName'];
				$settingValue = $accountSetting['SettingValue'];
				if ($settingName == 'isMagentoModuleEnabled' && $settingValue == false)
					$message .= 'Magento module is not enabled.';
				if ($settingName == 'MagentoEndPointURL'){
					$host = parse_url($settingValue,PHP_URL_HOST);
					$magentoHost = $_SERVER['HTTP_HOST'];
					if ($host != $magentoHost)
						$message .= sprintf('Domain in Magento Endpoint URL (%s) is not set to current domain.',$host);
				}
				if ($settingName == 'MagentoUserName'){
					if (empty($settingValue))
						$message .= 'Magento username is not set.';
					else
					{
						$user = Mage::getModel('api/user')->loadByUsername($settingValue);
						if (!$user->getId()){
							$message .= sprintf('Magento username %s does not exist.',$settingValue);
						}
					}
				}
				if ($settingName == 'isOrdersModuleEnabled' && $settingValue == false)
					$message .= 'Orders module is not enabled.';
				if ($settingName == 'isWorksModuleEnabled ' && $settingValue == false)
					$message .= 'Works module is not enabled.';
				
				$adminSession->setData('mv_'.$settingName,$settingValue);
			}
			if (strlen($message) > 0){
				$result = array('connectivity'=>'notok', 'message' => 'There are problems in your Megaventory configuration.'.$message, 'type' => 'fromMagento');
				echo json_encode($result) . PHP_EOL;
				die();
			}
		}
		
		$result = array('connectivity'=>'ok');
		echo json_encode($result) . PHP_EOL;
		
		die();
	}
	
	/*
	 $supplier = $this->getRequest()->getParam('supplierattribute');
	
	if (!empty($supplier))
	{
	$attributeId = Mage::getResourceModel('eav/entity_attribute')
	->getIdByCode('catalog_product',$supplier);
		
	if ($attributeId == false){
	$megaventoryHelper->sendProgress(time(),'No Suppliers found with this attribute code. Proceeding to next step', '0', 'suppliers');
	}
	else {
	Mage::getConfig()->saveConfig('megaventory/general/defaultsupplierattribute',$supplier);
	
	$totalSteps += 1;
	$megaventoryHelper->sendProgress(time(), 'Step '.$step.'/'.$totalSteps.'<br/>Importing Suppliers to Megaventory', '0', 'suppliers');
	$supplierHelper = Mage::helper('megaventory/suppliers');
	$supplierHelper->importSuppliersToMegaventory($attributeId,$megaventoryHelper);
	$step++;
	}
	} */
	
	//add role
	/* $role = Mage::getModel('api/roles')->load(false);
	 $role = $role
	->setName('megaventory')
	->setPid(false)
	->setRoleType('G')
	->save();
	$resource = array("all");
	Mage::getModel("api/rules")
	->setRoleId($role->getId())
	->setResources($resource)
	->saveRel();
	$rid = $role->getId();
	
	//add user
	$user = Mage::getModel('api/user')->load(false);
	$user->setUsername('megaventory');
	$user->setFirstname('Mega');
	$user->setLastname('Ventory');
	$user->setEmail('info@megaventory.com');
	$user->setApi_key('megaventory24');
	$user->setApi_key_confirmation('megaventory24');
	$user->setIs_active('1');
	try{
	$user->save();
	$uRoles = array('0'=>$rid);
	$user->setRoleIds($uRoles)
	->setRoleUserId($user->getUserId())
	->saveRelations();
	}
	catch(Exception $e){} */
	
	public function syncDataAction()
	{
		
		session_write_close();
		
		$megaventoryIntegration = Mage::getStoreConfig('megaventory/general/enabled');
		if ($megaventoryIntegration == '0')
		{
			$result = array('message'=>'not_enabled');
			echo json_encode($result) . PHP_EOL;
			die();
		}
		
		$megaventoryHelper = Mage::helper('megaventory');
		
		
		static $step = 1;
		//$totalSteps = 5;
		$totalSteps = 4;
		
		
		$serverTime = '';//time();
		$tickImg = '<img src="'.Mage::getDesign()->getSkinUrl('images/megaventory/accept.png').'" style="position:relative;top:1px;left:4px;"/>';
		$errorImg = '<img src="'.Mage::getDesign()->getSkinUrl('images/megaventory/exclamation.png').'" style="position:relative;top:1px;left:4px;"/>';
		Mage::register('tickImage', $tickImg);
		Mage::register('errorImage', $errorImg);
		
		$result = array();
		$syncStep = $this->getRequest()->get('step');
		$page = $this->getRequest()->get('page');
		$imported = $this->getRequest()->get('imported');
		if (empty($imported))
			$imported = 0;
		
		$inventoriesHelper = Mage::helper('megaventory/inventories');
		$categoryHelper = Mage::helper('megaventory/category');
		$productHelper = Mage::helper('megaventory/product');
		$customerHelper = Mage::helper('megaventory/customer');
		$currenciesHelper = Mage::helper('megaventory/currencies');
		$taxesHelper = Mage::helper('megaventory/taxes');
		
		if ($syncStep == 'inventories'){
			//first reset and then do it all from the beginning
			$megaventoryHelper->resetMegaventoryData();
			
			
			$megaventoryHelper->sendProgress(1, '<strong>Step 1/'.$totalSteps.'</strong> Getting Inventory Locations from Megaventory', '0', 'inventories', false);
			
			$count = $inventoriesHelper->initializeInventoryLocations();
			if ($count == '-1'){//no inventories
				$createdMessage = $inventoriesHelper->createMainInventory();
				if ($createdMessage !== true)
				{
					$megaventoryHelper->sendProgress(2, 'No Inventory Location found in Megaventory. Creating Main Inventory Location. Failed!'.Mage::registry('errorImage'), '0', 'inventories-2', false);
					$megaventoryHelper->sendProgress(3, $createdMessage, '0', 'inventories-3', false);
					$result['nextstep'] = 'error';
					echo json_encode($result);
					die();
				}
				$megaventoryHelper->sendProgress(2, 'No Inventory Location found in Megaventory. Creating Main Inventory Location'.Mage::registry('tickImage'), '0', 'inventories-2', false);
			}
			else{
				if (!$count == 1)
					$megaventoryHelper->sendProgress(2, $count.' Inventory Location imported from Megaventory'.Mage::registry('tickImage'), '0', 'inventories', true);
				else
					$megaventoryHelper->sendProgress(2, $count.' Inventory Locations imported from Megaventory'.Mage::registry('tickImage'), '0', 'inventories', true);
			}
			$step++;
			$result['currentstep'] = $syncStep;
			$result['nextstep'] = 'supporting';
			$result['nextpage'] = '1';
			$result['imported'] = 0;
			echo json_encode($result);
		}
		else if ($syncStep == 'supporting'){
			$megaventoryHelper->sendProgress(10, '<br><strong>Step 2/'.$totalSteps.'</strong> Adding Supporting Entities to Megaventory', '0', 'entities',false);
			$createdMessage = $productHelper->addShippingProduct($megaventoryHelper);
			if ($createdMessage !== true)
			{
				$megaventoryHelper->sendProgress(11, 'There was a problem inserting Shipping Product in Megaventory!'.Mage::registry('errorImage'), '0', 'shippingproduct', false);
				$megaventoryHelper->sendProgress(12, $createdMessage, '0', 'shippingproduct', false);
				$result['nextstep'] = 'error';
				echo json_encode($result);
				die();
			}
			$megaventoryHelper->sendProgress(11, 'Shipping Product synchronized successfully!', '0', 'shippingproduct',true);
			
			$createdMessage = $productHelper->addDiscountProduct($megaventoryHelper);
			if ($createdMessage !== true)
			{
				$megaventoryHelper->sendProgress(12, 'There was a problem inserting Discount Product in Megaventory!'.Mage::registry('errorImage'), '0', 'discountproduct', false);
				$megaventoryHelper->sendProgress(13, $createdMessage, '0', 'discountproduct', false);
				$result['nextstep'] = 'error';
				echo json_encode($result);
				die();
			}
			$megaventoryHelper->sendProgress(12, 'Discount Product synchronized successfully!', '0', 'discountproduct',true);
			$customerHelper->addDefaultGuestCustomer($megaventoryHelper);
			$currenciesHelper->addMagentoCurrencies($megaventoryHelper);
			$taxesHelper->synchronizeTaxes($megaventoryHelper);
			$result['currentstep'] = $syncStep;
			$result['nextstep'] = 'categories';
			$result['nextpage'] = 1;
			$result['imported'] = 0;
			echo json_encode($result);
		}
		else if ($syncStep == 'categories'){
			if ($page == '1')
				$megaventoryHelper->sendProgress(20, '<br><strong>Step 3/'.$totalSteps.'</strong> Synchronizing Categories..', '0', 'categories', false);
			
			$import = $categoryHelper->importCategoriesToMegaventory($megaventoryHelper,$page, $imported);
			
			
			if ($import === false){
				$result['currentstep'] = $syncStep;
				$result['nextstep'] = 'products';
				$result['nextpage'] = '1';
				$result['imported'] = 0;
			}
			else
			{	
				$result['currentstep'] = $syncStep;
				$result['nextstep'] = 'categories';
				$result['nextpage'] = $import['nextpage'];
				$result['imported'] = $import['imported'];
			}
			echo json_encode($result);
		}
		else if ($syncStep == 'products'){
			if ($page == '1')
				$megaventoryHelper->sendProgress(30, '<br><strong>Step 4/'.$totalSteps.'</strong> Synchronizing Products..', '0', 'products' ,false);
			
			$import = $productHelper->importProductsToMegaventory($megaventoryHelper,$page,$imported);
			
			
			if ($import === false){
				$result['currentstep'] = $syncStep;
				$result['nextstep'] = 'finishing';
				$result['nextpage'] = '1';
				$result['imported'] = 0;
			}
			else
			{
				$result['currentstep'] = $syncStep;
				$result['nextstep'] = 'products';
				$result['nextpage'] = $import['nextpage'];
				$result['imported'] = $import['imported'];
			}
			echo json_encode($result);
		}
		/* else if ($syncStep == 'customers'){
			if ($page == '1')
				$megaventoryHelper->sendProgress(7, '<br><strong>Step 4/'.$totalSteps.'</strong> Synchronizing Customers..', '0', 'clients',false);
			
			$import = $customerHelper->importCustomersToMegaventory($megaventoryHelper,$page,$imported);
			
			
			if ($import === false){
				$result['currentstep'] = $syncStep;
				$result['nextstep'] = 'supporting';
				$result['nextpage'] = 1;
				$result['imported'] = 0;
			}
			else
			{
				$result['currentstep'] = $syncStep;
				$result['nextstep'] = 'customers';
				$result['nextpage'] = $import['nextpage'];
				$result['imported'] = $import['imported'];
			}
			echo json_encode($result);
		} */
		else if ($syncStep == 'finishing'){
			$syncTimestamp = time();
			$megaventoryHelper->sendProgress(40, '<br>Synchronization finished successfully at '.date(DATE_RFC2822,$syncTimestamp), '0', 'finish',true);
			$megaventoryHelper->sendProgress(41, 'Saving Set up data for later reference!', '0', 'saveddata',false);
			
			$megaventoryHelper->sendProgress(42, 'Done!'.Mage::registry('tickImage'), '0', 'done',false);
			
			Mage::getConfig()->saveConfig('megaventory/general/synctimestamp', $syncTimestamp);
			Mage::getConfig()->saveConfig('megaventory/general/setupreport', $megaventoryHelper->getProgressMessage());
			Mage::getConfig()->reinit();
			
			$apikey = Mage::getStoreConfig('megaventory/general/apikey');
			$apiurl = Mage::getStoreConfig('megaventory/general/apiurl');
			
			if (!empty($apikey) && !empty($apiurl))
			{	
				
				$adminSession = Mage::getSingleton('admin/session');
				$megaventoryHelper = Mage::helper('megaventory');
				$accountSettings = $megaventoryHelper->getMegaventoryAccountSettings('All');
				
				foreach ($accountSettings as $index => $accountSetting) {
					$settingName = $accountSetting['SettingName'];
					$settingValue = $accountSetting['SettingValue'];
					$adminSession->setData('mv_'.$settingName,$settingValue);
				}
			}
			
			$resource = Mage::getSingleton ( 'core/resource' );
			$read = $resource->getConnection ( 'core/read' );
			$tableName = $resource->getTableName('megaventory_progress');
			$lastMessageSql = 'SELECT id FROM '.$tableName.' ORDER BY id asc LIMIT 1';
			
			while(true)
			{
				$lastMessage = $read->fetchOne($lastMessageSql);
				if ($lastMessage == false)
					break;
					
				sleep(2);
			}
			Mage::unregister('tickImage');
			$result['nextstep'] = 'finish';
			echo json_encode($result);
		}
		else if ($syncStep == 'error'){
			//$syncTimestamp = time();
			$megaventoryHelper->sendProgress(90, '<br>Synchronization did not finish succesfully.', '0', 'finisherror',true);
			$megaventoryHelper->sendProgress(100, 'Please refresh page and try again!', '0', 'done',false);
			$resource = Mage::getSingleton ( 'core/resource' );
			$read = $resource->getConnection ( 'core/read' );
			$tableName = $resource->getTableName('megaventory_progress');
			$lastMessageSql = 'SELECT id FROM '.$tableName.' ORDER BY id asc LIMIT 1';
				
			while(true)
			{
				$lastMessage = $read->fetchOne($lastMessageSql);
				if ($lastMessage == false)
					break;
					
				sleep(2);
			}
			Mage::unregister('tickImage');
			Mage::unregister('errorImage');
			$result['nextstep'] = 'finisherror';
			echo json_encode($result);
		}
		
		die();
	}
		
	public function resetSetupAction()
	{
		Mage::getConfig()->deleteConfig('megaventory/general/synctimestamp/');
		Mage::getConfig()->deleteConfig('megaventory/general/shippingproductsku');
		Mage::getConfig()->deleteConfig('megaventory/general/discountproductsku');
		Mage::getConfig()->deleteConfig('megaventory/general/defaultguestid');
		Mage::getConfig()->deleteConfig('megaventory/general/setupreport');
		Mage::getConfig()->deleteConfig('megaventory/general/ordersynchronization');
		Mage::getConfig()->deleteConfig('megaventory/feed/last_update');
		Mage::getConfig()->reinit();
		
		
		$helper = Mage::helper('megaventory');
		
		$helper->resetMegaventoryData();
		
		die();
	}
	
	public function getProgressAction()
	{
		try{
			$resource = Mage::getSingleton ( 'core/resource' );
			$write = $resource->getConnection ( 'core/write' );
			$read = $resource->getConnection ( 'core/read' );
			$tableName = $resource->getTableName('megaventory_progress');
			$lastlastMessagesSql = 'SELECT id, messagedata FROM '.$tableName.' ORDER BY id asc';
			
			$deleteMessages = 'delete FROM '.$tableName;
				
			$rows = $read->fetchAll($lastlastMessagesSql);
			if (count($rows) > 0){
				$message = '';
				$laststep = '';
				foreach ($rows as $row){
					$data = unserialize($row['messagedata']);
					$laststep = $data['step'];
					$message .= '<br/>'.$data['message'];
				}
				
				$data['message'] = $message;	
				echo json_encode($data);
				
				if ($laststep == 'done'){
					$config = Mage::getConfig();
					$config->saveConfig('megaventory/general/syncreport',$message);
					$config->reinit();
					
					$write->query($deleteMessages);
				}
					
			}
			
		}
		catch (Exception $e) {
		}
	
		die();
	}
	
	public function exportStockAction(){
		$inventoryId = $this->getRequest()->getParam('inventory');
		if (isset($inventoryId)){
			$inventory = Mage::getModel('megaventory/inventories')->load($inventoryId);
			$inventoryName = $inventory->getName().' ('.$inventory->getShortname().')';
			
			$productHelper = Mage::helper('megaventory/product');
			$productHelper->exportStock($inventoryName);
			
			$result = array(
					'filePath'=>'var/export/InitialQuantities.csv', 
					'message'=>'No inventory found'
					);
		}
		else
		{
			$result = array('message'=>'No inventory found');
		}
		
		echo json_encode($result) . PHP_EOL;
		die();
	}
		
	
	public function getInventoriesAction()
	{
		$inventoriesHelper = Mage::helper('megaventory/inventories');
		$inventories = $inventoriesHelper->getInventories();
		
		foreach ($inventories as $inventory){
			$options .= '<option value="'.$inventory->getId().'">'.$inventory->getName().'</option>\n';
		}
		
		$result = array('options'=> $options);
		echo json_encode($result) . PHP_EOL;
		
		die();
	}
	
	public function synchronizeOrderAction()
	{
		$orderId = $this->getRequest()->getPost('orderId');
		
		$order = Mage::getModel('sales/order')->load($orderId);
		
		if ($order->getId())
		{
			$quote = Mage::getModel('sales/quote');
			$website = Mage::getModel('core/website')->load($order->getStore()->getWebsite_id());
			$quote->setData('website',$website);
			$quote = $quote->load($order->getQuote_id());

			if ($quote->getId())
				Mage::helper('megaventory/order')->addOrder($order,$quote);
		}
		
		die();
	}
	
	public function logAction()
	{
		//Mage::helper('megaventory/currencies')->addMagentoCurrencies(Mage::helper('megaventory'));
		$this->loadLayout();
	
		$this->renderLayout();
	}
	
	public function redoLogAction()
	{
		$logId = $this->getRequest()->getPost('logId');
		
		if (isset($logId)){
			$log = Mage::getModel('megaventory/megaventorylog')->load($logId);
			if (isset($log['data'])){
				$data = json_decode($log['data'],true);
				//use the current apikey and not the one in json request
				$data['APIKEY'] = Mage::getStoreConfig('megaventory/general/apikey');
				$helper = Mage::helper('megaventory');
				
				$json_result = $helper->makeJsonRequest($data,$log['code'],$log['magento_id']);
				$errorCode = $json_result['ResponseStatus']['ErrorCode'];
				
				$magentoId = $log['magento_id'];
				Mage::log('magento id = '.$magentoId,null,'megaventory.log');
				
				//if we don't have magento id just return
				if ($magentoId == 0 || !isset($magentoId) || $magentoId == false)
					return;
				
				if ($log['code'] == 'SalesOrderUpdate')
				{
					$orderAdded = Mage::getModel('sales/order')->load($magentoId);
					if ($errorCode == '0'){//no errors
						$mvInventoryId = $data['mvSalesOrder']['SalesOrderInventoryLocationID'];
						$inventory = Mage::helper('megaventory/inventories')->getInventoryFromMegaventoryId($mvInventoryId);
						if ($inventory){
							$orderAdded->setData('mv_salesorder_id',$json_result['result']['SalesOrderNo']);
							$orderAdded->setData('mv_inventory_id',$inventory->getData('id'));
							$orderAdded->save();
						}
						
						//delete this error log
						$log->delete();
					}
				}
				else if ($log['code'] == 'SupplierClientUpdate')
				{
					$errorCode = $json_result['ResponseStatus']['ErrorCode'];
					if ($errorCode == '0'){//no errors
						Mage::log('action = '.$data['mvRecordAction'],null,'megaventory.log');
						if (strcmp('Insert', $data['mvRecordAction']) == 0){
							Mage::log('mv id = '.$json_result ['mvSupplierClient'] ['SupplierClientID'],null,'megaventory.log');
							Mage::helper('megaventory/customer')->updateCustomer($magentoId, $json_result ['mvSupplierClient'] ['SupplierClientID']);
						}
						//delete this error log
						$log->delete();
					}
				}
				else if ($log['code'] == 'ProductUpdate')
				{
					if ($errorCode == '0'){//no errors
						
						if (strcmp('Insert', $data['mvRecordAction']) == 0){
							Mage::log('mv id = '.$json_result['mvProduct']['ProductID'],null,'megaventory.log');
							Mage::helper('megaventory/product')->updateProduct($magentoId,$json_result['mvProduct']['ProductID']);
						}
						
						//delete this error log
						$log->delete();
					}
				}
				else if ($log['code'] == 'ProductCategoryUpdate')
				{
					if ($errorCode == '0'){//no errors
						if (strcmp('Insert', $data['mvRecordAction']) == 0){
							Mage::log('mv id = '. $json_result ['mvProductCategory'] ['ProductCategoryID'],null,'megaventory.log');
							Mage::helper('megaventory/category')->updateCategory($magentoId, $json_result ['mvProductCategory'] ['ProductCategoryID']);
						}
						//delete this error log
						$log->delete();
					}
				}
			}
		}
	}
	
	public function deleteLogAction()
	{
		$logId = $this->getRequest()->getParam('id');
		
		if (isset($logId)){
			$log = Mage::getModel('megaventory/megaventorylog')->load($logId);
			$log->delete();
		}
		
		$this->_redirect('*/*/log');
	}
	
	public function massLogDeleteAction()
	{
		$logIds = $this->getRequest()->getParam('log');
        if (!is_array($logIds)) {
            $this->_getSession()->addError($this->__('Please select log entries'));
        }
        else {
            try {
                foreach ($logIds as $logId) {
                    $log = Mage::getSingleton('megaventory/megaventorylog')->load($logId);
                    $log->delete();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) have been deleted.', count($logIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/log');
	
	}
	
	public function inventoriesAction()
	{
		$this->loadLayout();
	
		$this->renderLayout();
	}
	
	public function importInventoriesAction()
	{
		$this->loadLayout();
	
		$inventoriesHelper = Mage::helper('megaventory/inventories');
		$inventoriesHelper->syncrhonizeInventories();
	
		$block = $this->getLayout()
		->createBlock('mv_megaventory/adminhtml_megaventorysettings', 'megaventorysettings')
		->setTemplate('megaventory/megaventory.phtml');
		$this->_addContent($block);
		
		$this->_setActiveMenu('megaventory_menu');
		
		$this->renderLayout();
	}
	
	public function updateInventoryLocationsAction()
	{
		$this->loadLayout();
	
		$inventoriesHelper = Mage::helper('megaventory/inventories');
		$inventoriesHelper->updateInventoryLocations();
	
		$block = $this->getLayout()
		->createBlock('mv_megaventory/adminhtml_megaventorysettings', 'megaventorysettings')
		->setTemplate('megaventory/megaventory.phtml');
		$this->_addContent($block);
	
		$this->_setActiveMenu('megaventory_menu');
	
		$this->renderLayout();
	}
	
	public function makeDefaultInventoryAction()
	{
	
		$inventoryId = $this->getRequest()->getParam('id');
		$inventoriesHelper = Mage::helper('megaventory/inventories');
		$inventoriesHelper->makeDefaultInventory($inventoryId);
	
	
		$this->loadLayout();
		$block = $this->getLayout()
		->createBlock('mv_megaventory/adminhtml_megaventorysettings', 'megaventorysettings')
		->setTemplate('megaventory/megaventory.phtml');
		$this->_addContent($block);
		
        $this->_setActiveMenu('megaventory_menu');
        
        
        $this->renderLayout();
        
	}
	
	public function updateCountsInStockAction()
	{
		$inventoryId = $this->getRequest()->getParam('inventoryId');
		$bCount = $this->getRequest()->getParam('value');
	
		$inventoriesHelper = Mage::helper('megaventory/inventories')->updateCountsInStock($inventoryId, $bCount);
		
		die();
	}
	
	public function updateOrderSynchronizationAction()
	{
		$bCount = $this->getRequest()->getParam('value');
		$bCount == 'true' ? $orderSynchronization = '1' : $orderSynchronization = '0';
		$config = Mage::getConfig();
		$config->saveConfig('megaventory/general/ordersynchronization',$orderSynchronization);
		$config->reinit();
	
		die();
	}
	
	public function taxesAction()
	{
		$this->loadLayout();
	
		$this->renderLayout();
	}
	
	public function synchronizeTaxesAction()
	{
		$taxesHelper = Mage::helper('megaventory/taxes');
		$taxesHelper->synchronizeTaxes();
		
		$this->loadLayout();
		$block = $this->getLayout()
		->createBlock('mv_megaventory/adminhtml_megaventorysettings', 'megaventorysettings')
		->setTemplate('megaventory/megaventory.phtml');
		$this->_addContent($block);
		
		$this->_setActiveMenu('megaventory_menu');
		
		$this->renderLayout();
	}
	
	public function productsAction()
	{
		$this->loadLayout();
	
		//create a text block with the name of "example-block"
	
		$productHelper = Mage::helper('megaventory/product');
		//$syncedProducts = $productHelper->synchProducts();
		$syncedProducts = $productHelper->importProductsToMegaventory();
	
		$block = $this->getLayout()
		->createBlock('core/text', 'megaventory-sync')
		->setText('<h1>Synced '.$syncedProducts.' products</h1>');
	
		$this->_addContent($block);
	
		$this->renderLayout();
	}
	
	public function clientsAction()
	{
		$this->loadLayout();
	
		$clientHelper = Mage::helper('megaventory/customer');
		$syncedCustomers = $clientHelper->synchCustomers();
	
		$block = $this->getLayout()
		->createBlock('core/text', 'megaventory-sync')
		->setText('<h1>Synced '.$syncedCustomers.' customers</h1>');
	
		$this->_addContent($block);
	
		$this->renderLayout();
	}
	
	public function exportProductsAction()
	{
		$exportProfileId = Mage::getStoreConfig('megaventory/general/exportproductsprofileid');
		$this->_initProfile($exportProfileId);
		$this->loadLayout();
		
		#$this->_setActiveMenu('system/convert');
		
		#$this->_addContent(
		#    $this->getLayout()->createBlock('adminhtml/system_convert_profile_run')
		#);
		$html = $this->getLayout()->createBlock('adminhtml/system_convert_profile_run')->toHtml();
		/* $this->getResponse()->setBody($html);
		$body = $this->getResponse()->getBody();
		$this->getResponse()->sendResponse(); */
		
		
		$toBeReplacedString = 'to_be_replaced';
		$replaceString = '<h3>Download <a href="/var/export/Megaventory_ProductsW.csv">file</a></h3>';
		$html = str_replace($toBeReplacedString, $replaceString, $html);
		
		$block = $this->getLayout()
		->createBlock('core/text', 'megaventory-sync')
		->setText($html);
		
		$this->_addContent($block);
		
		$this->renderLayout();
	}
	
	/* public function exportStockAction()
	{
		$exportProfileId = Mage::getStoreConfig('megaventory/general/exportstockprofileid');
		$this->_initProfile($exportProfileId);
		$this->loadLayout();
	
		
		$html = $this->getLayout()->createBlock('adminhtml/system_convert_profile_run')->toHtml();
		
		
		$toBeReplacedString = 'to_be_replaced';
		$replaceString = '<h3>Download <a href="/var/export/InitialQuantities.csv">file</a></h3>';
		$html = str_replace($toBeReplacedString, $replaceString, $html);
	
		$block = $this->getLayout()
		->createBlock('core/text', 'megaventory-sync')
		->setText($html);

		$this->_addContent($block);

		$this->renderLayout();
	} */
	
	public function exportClientsAction()
	{
		$exportProfileId = Mage::getStoreConfig('megaventory/general/exportclientsprofileid');
		
		$this->_initProfile($exportProfileId);
		$this->loadLayout();
	
		#$this->_setActiveMenu('system/convert');
	
		#$this->_addContent(
				#    $this->getLayout()->createBlock('adminhtml/system_convert_profile_run')
				#);
		$html = $this->getLayout()->createBlock('adminhtml/system_convert_profile_run')->toHtml();
		/* $this->getResponse()->setBody($html);
			$body = $this->getResponse()->getBody();
		$this->getResponse()->sendResponse(); */
	
		$toBeReplacedString = 'to_be_replaced';
		$replaceString = '<h3>Download <a href="/var/export/Megaventory_Clients.csv">file</a></h3>';
		$html = str_replace($toBeReplacedString, $replaceString, $html);
	
		$block = $this->getLayout()
		->createBlock('core/text', 'megaventory-sync')
				->setText($html);
	
				$this->_addContent($block);
	
				$this->renderLayout();
	}
	
	public function suppliersAction()
	{
		$this->loadLayout();
	
	
		$attributeCode = Mage::getStoreConfig('megaventory/general/defaultsupplierattribute');
		if (empty($attributeCode))
			$attributeCode = 'supplier';
		
		$attributeId = Mage::getResourceModel('eav/entity_attribute')
		->getIdByCode('catalog_product',$attributeCode);
		$attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
		$attributeOptions = $attribute ->getSource()->getAllOptions();
		
		$filePath = 'var' . DS . 'export';
		$this->openToExport($filePath, 'Megaventory_Suppliers.csv');
		
		$header = array(
				label => 'Supplier Name',
				notused1 => 'Billing Address',
				notused2 => 'Shipping Address',
				notused3 => 'Shipping Address 2',
				notused4 => 'Supplier Comments',
				notused5 => 'Phone',
				notused6 => 'Phone 2',
				notused7 => 'Tax ID',
				notused8 => 'e-mail',
				notused9 => 'Also my Client?',
				);
		
		$this->saveCsvHeader($header);
		
		foreach ($attributeOptions as $attributeOption){
			$optionValue = $attributeOption[0];
			$optionLabel = $attributeOption[1];
		
			$this->saveCsvContent($header, $attributeOption);
		}
		$this->closeFile();
		
		$storeUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$block = $this->getLayout()
		->createBlock('core/text', 'megaventory-sync')
		->setText('<h1>Suppliers Exported!!</h1><br/>Download <a href="'.$storeUrl.'var/export/Megaventory_Suppliers.csv">file</a>');
		
		$this->_addContent($block);
	
		$this->renderLayout();
	}
	
	public function categoriesAction()
	{
		$this->loadLayout();
	
		$categoryHelper = Mage::helper('megaventory/category');
		$imported = $categoryHelper->importCategoriesToMegaventory();
	
		/* $collection = Mage::getModel('catalog/category')->getCollection()
		->addAttributeToSelect('level');
		$collection->addAttributeToSelect('path');
		$collection->addAttributeToSelect('entity_id');
		$collection->addAttributeToSelect('name');
		$collection->getSelect()->order('level');
	
		$filePath = 'var' . DS . 'export';
		$this->openToExport($filePath, 'Megaventory_Categories.csv');
	
		$header = array(
				path => 'Category Name',
				entity_id => 'Category Description',
				name => 'NotUsed'
		);
	
		$this->saveCsvHeader($header);
	
		foreach($collection as $row) {
			$this->setCategory($row);
			$this->saveCsvContent($header, $row->getData());
		}
		
		$this->closeFile();
		
		
		$storeUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$block = $this->getLayout()
		->createBlock('core/text', 'megaventory-sync')
		->setText('<h1>Categories Exported!!</h1><br/>Download <a href="'.$storeUrl.'/var/export/Megaventory_Categories.csv">file</a>');
		*/
		
		$block = $this->getLayout()
		->createBlock('core/text', 'megaventory-sync')
		->setText('<h1>Imported '.$imported.' categories</h1>');
		$this->_addContent($block);
	
		$this->renderLayout();
	}
	
	
	protected $_categories = array();
	public function setCategory($row) {
		$this->_categories[$row->getId()] = array('name' => $row->getName());
	}
	
	protected $_resource;
	protected $_fileSize;
	private function openToExport($path, $file) {
		$baseDir = Mage::getBaseDir();
		$this->_resource = new Varien_Io_File();
		$filepath = $this->_resource->getCleanPath($baseDir . DS . trim($path, DS));
		$this->_resource->checkAndCreateFolder($filepath);
		$realPath = realpath($filepath);
	
		if ($realPath === false) {
			$message = $this->__('The destination folder "%s" does not exist or there is no access to create it.', $path);
			Mage::throwException($message);
		}
		elseif (!is_dir($realPath)) {
			$message = $this->__('Destination folder "%s" is not a directory.', $realPath);
			Mage::throwException($message);
		}
		else {
			if (!is_writeable($realPath)) {
				$message = $this->__('Destination folder "%s" is not writable.', $realPath);
				Mage::throwException($message);
			}
			else {
				$filepath = rtrim($realPath, DS);
			}
		}
		try {
			$this->_resource->open(array('path' => $filepath));
			$this->_resource->streamOpen($file, 'w+');
			$this->_fileSize = 0;
		} catch (Exception $e) {
			$message = Mage::helper('dataflow')->__('An error occurred while opening file: "%s".', $e->getMessage());
			Mage::throwException($message);
		}
	}
	
	private function saveCsvHeader($data) {
		$str = '';
		foreach ($data as $value) {
			//$header = (isset($value['label'])) ? $value['label'] : $value['field'];
			if ($value != 'NotUsed')
			$str .= '"'.$value.'"' . ',';
		}
		$string = substr($str, 0, -1) . "\n";
		$this->saveToFile($string);
	}
	
	private function saveCsvContent($data, $model) {
		$search = array('\"');
		$replace = array('""');
	
		$str = '';
		foreach ($data as $head => $value) {
			/* $str1 = $model->getData($value['field']);
			if (isset($value['function'])) {
				$str1 = $this->$value['function']($str1);
			}
			$csvstr = str_replace($search, $replace, addslashes($str1)); */
			if ($value != 'NotUsed'){
				$tmpStr = $model[$head];
				if ($head == 'path'){
					$tmpStr = $this->getFullPath($tmpStr);
				}
				$str .= '"'.$tmpStr.'"' . ',';
			}
		}
		$string = substr($str, 0, -1) . "\n";
		$this->saveToFile($string);
	}
	
	private function getFullPath($val) {
		$paths = explode('/', $val);
		$pathnames = array();
	
		for ($i=0; $i<=count($paths)-1; $i++) {
			if ($name = $this->_categories[$paths[$i]]) $pathnames[] = $name['name'];
		}
		return implode('/', $pathnames);
	}
	
	private function saveToFile($str) {
		$this->_fileSize += strlen($str);
		$this->_resource->streamWrite($str);
	}
	
	private function closeFile() {
		$this->_resource->streamClose();
	}
	
	protected function _initProfile($profileId)
	{
		$this->_title($this->__('System'))
		->_title($this->__('Import and Export'))
		->_title($this->__('Profiles'));
	
		$profile = Mage::getModel('dataflow/profile');
	
		if ($profileId) {
			$profile->load($profileId);
			if (!$profile->getId()) {
				Mage::getSingleton('adminhtml/session')->addError('The profile you are trying to save no longer exists');
				$this->_redirect('*/*');
				return false;
			}
		}
	
		Mage::register('current_convert_profile', $profile);
	
		return $this;
	}
}