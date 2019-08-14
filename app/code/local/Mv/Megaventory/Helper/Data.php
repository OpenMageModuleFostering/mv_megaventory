<?php

class Mv_Megaventory_Helper_Data extends Mage_Core_Helper_Abstract
{
	//static private $MEGAVENTORY_API_URL = 'https://api.megaventory.com/v1/json/syncreply/';
	//static private $MEGAVENTORY_API_URL = 'https://apitest.megaventory.com/json/syncreply/';
	static private $MEGAVENTORY_API_URL;//Mage::getStoreConfig('megaventory/general/apiurl');
	
	private $_progressMessage; 
	
	public function makeJsonRequest($data, $action, $magentoId = 0, $apiurl = -1, $enabled = -1){
		if (empty(self::$MEGAVENTORY_API_URL))
			self::$MEGAVENTORY_API_URL = Mage::getStoreConfig('megaventory/general/apiurl');
		
		if ($apiurl != -1)
			self::$MEGAVENTORY_API_URL = $apiurl;
		
		if ($enabled == -1)
			$megaventoryIntegration = Mage::getStoreConfig('megaventory/general/enabled');
		else
			$megaventoryIntegration = $enabled;
		
		if ($megaventoryIntegration == '1'){
			Mage::log('action = '.$action,null,'megaventory.log');
			$data_string = json_encode ( $data );
			Mage::log('data = '.$data_string,null,'megaventory.log');
			
			
			$ch = curl_init (self::$MEGAVENTORY_API_URL.$action);
			curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
			curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data_string );
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
			
			// letrim check in production if we need it
			curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
			// end of letrim
			
			curl_setopt ( $ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json', 'Content-Length: ' . strlen ( $data_string ) ) );
			$Jsonresult = curl_exec ( $ch );
			
			$curlError = curl_error ( $ch );
			
			/* if (isset($curlError)){
				Mage::log($curlError,null,'megaventory.log');
			} */
			
			$json_result = json_decode ( $Jsonresult, true );
			Mage::log($Jsonresult,null, 'megaventory.log');
			$test = json_last_error();
			if ($test != JSON_ERROR_NONE){
				$event = array(
							'code' => $action,
							'result' => 'json fail',
							'magento_id' => '0',
							'return_entity' => '0',
							'details' => $test,
							'data' => $data_string
							//'data' => serialize($data)
					);
					$this->log($event);
			} 
			else
			{
				$errorCode = $json_result['ResponseStatus']['ErrorCode'];
				if ($errorCode != '0'){//log errors
					/* Mage::log('error code = ' .$errorCode,null,'megaventory.log');
					Mage::log('message = ' .$json_result['ResponseStatus']['Message'],null,'megaventory.log'); */
					
					//do not log gets
					if (strpos($action,'Get') === false){
						$event = array(
									'code' => $action,
									'result' => 'fail',
									'magento_id' => $magentoId,
									'return_entity' => '0',
								    'details' => $json_result['ResponseStatus']['Message'],
									'data' => $data_string
									//'data' => serialize($data)
								);
							$this->log($event);
					}
				}
				else
				{
					//Mage::log('SUCCESS '.$action,null,'megaventory.log');
					
					//Mage::log('entity '.implode("|",array_shift(array_values($json_result))),null,'megaventory.log');
					
					//do not log gets
					if (strpos($action,'Get') === false)
					{
						$tmp = array_values($json_result);
						$tmp2 = array_shift($tmp);
						$return_entity = implode("|",$tmp2);
						$event = array(
								'code' => $action,
								'result' => 'success',
								'magento_id' => $magentoId,
								'return_entity' => $return_entity,
								'details' => 'no details',
								'data' => $data_string
								//'data' => serialize($data)
						);
						
						if (strpos($action,'Get') === false)
							
						$this->log($event); 
					}
					
					//Mage::log($json_result['mvProduct'],null,'megaventory.log');
				}
			}
			return $json_result;
		}
		else
		{
			return false;
		}
	}
	
	public function redoRequest()
	{
		
	}
	
	public function createCategoryName($category)
	{
		/*
		$categoryIds = $category->getPathIds();
		
		foreach($categoryIds as $categoryId)
		{
			$pCategory = Mage::getModel('catalog/category')->load($categoryId);
			$pName = $pCategory->getName();
			if (isset($pName) && $pName != NULL)
				$name .= $pName.'/';
		} */
		/* if (isset($pName) == false || $pName == NULL )
		 $name .= $category->getName(); */
		
		$path = $category->getPath();
		$name = '';
		$categoryIds = explode('/', $path);
		foreach($categoryIds as $categoryId)
		{
			$pCategory = Mage::getModel('catalog/category')->load($categoryId);
			$pName = $pCategory->getName();
			if (isset($pName) && $pName != NULL)
				$name .= $pName.'/';
		}
		
		$name = rtrim($name, "/");
		
		
		return $name;
	}
	
	public function log($event){
		if ($event['result'] != 'success'){
			$resource = Mage::getSingleton ( 'core/resource' );
			$write = $resource->getConnection ( 'core/write' );
			$tableName = $resource->getTableName('megaventory_log');
			$query = 'insert into '.$tableName.'  (code, result, magento_id, return_entity, details, data) values (:code, :result, :magento_id, :return_entity, :details, :data)';
			
			$write->query($query, $event);
		}
	}
		
	public function sendProgress($gid, $message, $progress, $step, $addToReport = false)
	{
		//static $id = 1;
		static $headingid = 1;
		static $detailid = 1;
		static $flag = 0;
		static $lastStep = '';
		
		$id = $gid;
		
		$session = Mage::getSingleton('admin/session');
		
		
		if ($addToReport != false)
			$this->_progressMessage .= '<br/>'.$message;
	
		$d = array('message' => $message , 'progress' => $progress, 'step' => $step);
		$messageData = serialize($d);
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$tableName = $resource->getTableName('megaventory_progress');
	
		if ($lastStep != $step && (empty($progress) || $progress == 1)){
				$sqlCmd = 'insert into '.$tableName.' (id, messagedata,type) values ('.$id.',\''.$messageData.'\',\'heading\')';
				$headingid = $id;
				$id++;
				$flag = 0;
			$lastStep = $step;
		}
		else
		{
			if ($flag == 0 && $progress == 1){
				$sqlCmd = 'insert into '.$tableName.' (id,messagedata,type) values ('.$id.',\''.$messageData.'\',\'details\')';
				$detailid = $id;
				$id++;
				$flag = 1;
			}
			else{
				if ($progress > 1)
					$detailid = $gid;
				
				$sqlCmd = 'update '.$tableName.' set messagedata = \''.$messageData.'\' where type = \'details\' and id = '.$detailid;
			}
				
		}
		
		$write->query($sqlCmd);
		
	}
	
	
	public function getProgressMessage(){
		return $this->_progressMessage;
	}
	
	public function resetMegaventoryData(){
		
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		
		$deleteInventories = 'delete from '.$resource->getTableName('megaventory_inventories');
		$write->query($deleteInventories);
		$deleteTaxes = 'delete from '.$resource->getTableName('megaventory_taxes');
		$write->query($deleteTaxes);
		$deleteCurrencies = 'delete from '.$resource->getTableName('megaventory_currencies');
		$write->query($deleteCurrencies);
		$deleteStock = 'delete from '.$resource->getTableName('megaventory_stock');
		$write->query($deleteStock);
		$deleteLog = 'delete from '.$resource->getTableName('megaventory_log');
		$write->query($deleteLog);
		$deleteProgress = 'delete from '.$resource->getTableName('megaventory_progress');
		$write->query($deleteProgress);
		
		$updateCustomer = 'update '.$resource->getTableName('customer_entity').' set mv_supplierclient_id = NULL';
		$write->query($updateCustomer);
		$updateCategory= 'update '.$resource->getTableName('catalog_category_entity').' set mv_productcategory_id = NULL';
		$write->query($updateCategory);
		$updateProduct = 'update '.$resource->getTableName('catalog_product_entity').' set mv_product_id = NULL';
		$write->query($updateProduct);
		
		$updateSalesFlatOrder = 'update '.$resource->getTableName('sales_flat_order').' set mv_salesorder_id = NULL, mv_inventory_id = 0';
		$write->query($updateSalesFlatOrder);
		$updateSalesFlatOrderGrid = 'update '.$resource->getTableName('sales/order_grid').' set mv_inventory_id = 0';
		$write->query($updateSalesFlatOrderGrid);
	}
	
	public function getMegaventoryAccountSettings($settingName=false,$apikey = false, $apiurl = -1){
		if ($apikey != false)
			$key = $apikey;
		else
			$key = Mage::getStoreConfig('megaventory/general/apikey');
		
		$data = array
		(
				'APIKEY' => $key,
				'SettingName' => ($settingName === false) ? 'All' : $settingName
		);
			
		
		$json_result = $this->makeJsonRequest($data ,'AccountSettingsGet',0,$apiurl);
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0')
			return false;
		
		
		return $json_result['mvAccountSettings'];
	}
}
