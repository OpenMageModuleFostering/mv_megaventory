<?php

class Mv_Megaventory_Helper_Customer extends Mage_Core_Helper_Abstract
{

	public function synchCustomers()
	{
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				//'query'=> 'mv.SupplierClientEmail like "%let%"'
				'query'=> 'mv.SupplierClientType = 2'
		);
			
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'SupplierClientGet',0);
	
		$mvCustomers = $json_result['mvSupplierClients'];
	
		$i = 0;
	
		foreach($mvCustomers as $mvCustomer)
		{
			$mvCustomerId =  $mvCustomer['SupplierClientID'];
			$magentoCustomerId = $mvCustomer['SupplierClientComments'];
			Mage::log('mv customer id = '.$mvCustomerId);
			Mage::log('magento id = '.$magentoCustomerId);
				
			if (isset($magentoCustomerId) && is_numeric($magentoCustomerId))
				$this->updateCustomer($magentoCustomerId, $mvCustomerId);
			$i++;
		}
	
		return $i;
	}
	
	public function importCustomersToMegaventory($megaventoryHelper,$page = 1,$imported = 0)
	{
		$collection = Mage::getModel("customer/customer")->getCollection()
		->addAttributeToSelect("*");
		
		$collection->setPageSize(100);
		$collection->setCurPage($page);
		$totalCollectionSize = $collection->getSize();
		$isLastPage = false;
		if ((int)($totalCollectionSize/100) == $page-1)
			$isLastPage = true;
		
		$total = $imported;
		foreach ($collection as $customer)
		{
			try{
				$inserted = $this->insertSingleCustomer($customer);
				if ($inserted == 0 || $inserted == 1) //no errors
				{
					$total++;
					$message = $total.'/'.$totalCollectionSize;
					$megaventoryHelper->sendProgress(8, $message, '0', 'clients');
				}
			}
			catch(Exception $ex){
			}
		}
		
		if ($isLastPage){
			$megaventoryHelper->sendProgress(8, $total.'/'.$totalCollectionSize.' customers syncrhonized'.Mage::registry('tickImage'), '0', 'clients', true);
			return false;
		}
		else{
			$result =
			array(
					'nextpage' => $page+1,
					'imported' => $total
			);
			return $result;
		}
	}
	
	public function insertSingleCustomer($customer){
		
		$billingaddress = Mage::getModel('customer/address');
		$shippingaddress = Mage::getModel('customer/address');
		
		$fullname = $customer["lastname"]." ".$customer["firstname"];
		$email = $customer['email'];
			
		$taxvat = $customer['taxvat'];
		$billingaddress->load($customer->default_billing);
		$billing = $billingaddress ->getData();
		if (isset($billing)){
			$phone1 = $billing['telephone'];
			$fax = $billing['fax'];
			
			$country = Mage::app()->getLocale()->getCountryTranslation($billing['country_id']);
			$billingAddress =  $billing['street'].','.$billing['city'].','.$billing['postcode'].','.$country;
			$billingAddress=  preg_replace( "/\r|\n/", "", $billingAddress );
		}
		else {
		$phone1 = '';
		$fax = '';
		$billingAddress = '';
		}
			
		$shippingaddress->load($customer->default_shipping);
		$shipping = $shippingaddress->getData();
			
		if (isset($shipping)){
		$country = Mage::app()->getLocale()->getCountryTranslation($shipping['country_id']);
		$shippingAddress =  $shipping['street'].','.$shipping['city'].','.$shipping['postcode'].','.$country;
		$shippingAddress = preg_replace( "/\r|\n/", "", $shippingAddress );
		}
		else
			$shippingAddress =  '';
		
		$group = Mage::getModel('customer/group')->load($customer['group_id']);
		$comments = 'Group : '.$group['customer_group_code'];
		
		$data = array (
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'mvSupplierClient' => array (
						'SupplierClientID' => 0,
						'SupplierClientType' => '2',
						'SupplierClientName' => $fullname,
						'SupplierClientBillingAddress' => $billingAddress,
						'SupplierClientShippingAddress1' => $shippingAddress,
						'SupplierClientShippingAddress2' => '',
						'SupplierClientPhone1' => $phone1,
						'SupplierClientPhone2' => '',
						'SupplierClientFax' => $fax,
						'SupplierClientIM' => '',
						'SupplierClientEmail' => $email,
						'SupplierClientTaxID' => $taxvat,
						'SupplierClientComments' => $comments,
				),
				'mvRecordAction' => 'Insert' );
			
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'SupplierClientUpdate',0);
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		
		if ($errorCode == 0) //no errors
		{
			$this->updateCustomer($customer->getId(), $json_result ['mvSupplierClient'] ['SupplierClientID']);
		} 
		else
		{
			$entityId = $json_result['entityID'];
			if (!empty($entityId) && $entityId > 0) //if customer exists just sync them
			{
				$this->updateCustomer($customer->getId(), $entityId);
				return 1;
			}
		}
		
		return $errorCode;
	}
	
	public function addDefaultGuestCustomer($megaventoryHelper){
		$APIKEY = Mage::getStoreConfig('megaventory/general/apikey');
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'), 
					'mvSupplierClient' => array 
						('SupplierClientID' => 0, 
						'SupplierClientType' => '2', 
						'SupplierClientName' => 'Magento Guest', 
						'mvContacts' => array ('ContactIsPrimary' => "False" ), 
						'SupplierClientBillingAddress' => '', 
						'SupplierClientShippingAddress1' => '', 
						'SupplierClientShippingAddress2' => '', 
						'SupplierClientPhone1' => '', 
						'SupplierClientPhone2' => '', 
						'SupplierClientFax' => '', 
						'SupplierClientIM' => '', 
						'SupplierClientEmail' => '', 
						'SupplierClientTaxID' => '', 
						'SupplierClientComments' => ''
						), 
					'mvRecordAction' => 'Insert'
		);
			
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'SupplierClientUpdate',0);
		
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode == 0){
			$defaultGuestId = $json_result['mvSupplierClient']['SupplierClientID'];
			Mage::getConfig()->saveConfig('megaventory/general/defaultguestid',$defaultGuestId);
			$megaventoryHelper->sendProgress(13, 'Default Guest account added successfully!', '0', 'defaultguest',true);
		}
		else
		{
			$entityId = $json_result['entityID'];
			if (!empty($entityId) && $entityId > 0){
				$defaultGuestId = $entityId;
				Mage::getConfig()->saveConfig('megaventory/general/defaultguestid',$defaultGuestId);
				$megaventoryHelper->sendProgress(13, 'Default Guest account added successfully!', '0', 'defaultguest',true);
			}
			else
				$megaventoryHelper->sendProgress(13, 'There was an error creating Default Guest account', '0', 'defaultguest',true);
				
		}
		return $errorCode;
	}
	
	
	public function updateCustomer($magentoCustomerId, $mvCustomerId){
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$table = $resource->getTableName ( 'customer/entity' );
		$sql_insert = "update ".$table." set mv_supplierclient_id = ".$mvCustomerId." where entity_id = ".$magentoCustomerId;
		Mage::log('update customer sql = '.$sql_insert, null, 'megaventory.log');
		$write->query($sql_insert);
	}
	
	
	public function addCustomer($customer)
	{
		
		$megaVentoryId = $customer->getData('mv_supplierclient_id');
		
		if ($customer->getFirstname()) {
			$firstName =  $customer->getFirstname(). " ";
		} else {
			$firstName = "";
		}
		if ($customer->getLastname()) {
			$lastName = $customer->getLastname();
		} else {
			$lastName = "";
		}
		
		if ($firstName == null && $lastName == null){
			$name = 'guest';
			$clientComments = '';
			
			$mvCustomerId = Mage::getStoreConfig('megaventory/general/defaultguestid');//hard coded guest customer
			
			return $mvCustomerId;
		}
		else {
			$name = $firstName.$lastName;
			
			//get customer store
			$createdAt = $customer->getCreated_at();
			$webSiteId = $customer->getWebsite_id();
			$storeId = $customer->getStore_id();
			$storeViewName = $customer->getCreated_in();
			$clientComments = ''.'created at:'.$createdAt.',website:'.$webSiteId.',store:'.$storeId.',storeview:'.$storeViewName;
		
		
			if(isset($megaVentoryId) && $megaVentoryId!=NULL) //it is an update
			{
				$mvCustomerId = $megaVentoryId;
				$mvRecordAction = 'Update';
			}
			else //it is an insert
			{
				$mvCustomerId = '0';
				$mvRecordAction = 'Insert';
			}
		
			$supplierClientBillingAddress = '';
			$supplierClientShippingAddress1 = '';
			$supplierClientShippingAddress2 = '';
			$supplierClientPhone1 = '';
			$supplierClientFax = '';
			$primaryBillingAddress = $customer->getPrimaryBillingAddress();
			$primaryShippingAddress = $customer->getPrimaryShippingAddress();
			
			
			foreach ($customer->getAddressesCollection() as $address) {
				$flag = false;
				
				$telephone = $address->getTelephone();
				
				$fax = $address->getFax();
				$addressId = $address->getEntity_id(); 
				
				$isDefaultBilling = $address->getData('is_default_billing');
				if ((isset($isDefaultBilling) && $isDefaultBilling) || ($primaryBillingAddress && $primaryBillingAddress->getId() == $addressId))
				{
					$supplierClientBillingAddress = $address->format('oneline');
					$supplierClientPhone1 = $telephone;
					$supplierClientFax = $fax;
					$flag = true;
				}
				
				$isDefaultShipping = $address->getData('is_default_shipping');
				if ((isset($isDefaultShipping) && $isDefaultShipping) || ($primaryShippingAddress && $primaryShippingAddress->getId() == $addressId)){
					$supplierClientShippingAddress1 = $address->format('oneline');
					$flag = true;
				}

				if (!$flag)
					$supplierClientShippingAddress2 = $address->format('oneline');
				
			} 
		
		
		
		
			$data = array (
					'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'), 
					'mvSupplierClient' => array 
										('SupplierClientID' => $mvCustomerId, 
										'SupplierClientType' => '2', 
										'SupplierClientName' => $name, 
										'mvContacts' => array ('ContactIsPrimary' => "False" ), 
										'SupplierClientBillingAddress' => $supplierClientBillingAddress, 
										'SupplierClientShippingAddress1' => $supplierClientShippingAddress1, 
										'SupplierClientShippingAddress2' => $supplierClientShippingAddress2, 
										'SupplierClientPhone1' => $supplierClientPhone1, 
										'SupplierClientPhone2' => "", 
										'SupplierClientFax' => $supplierClientFax, 
										'SupplierClientIM' => "", 
										'SupplierClientEmail' => $customer->getEmail(), 
										'SupplierClientTaxID' => "", 
										'SupplierClientComments' => $clientComments
										), 
					'mvRecordAction' => $mvRecordAction,
					'mvGrantPermissionsToAllUsers' => true);
			
			
			$helper = Mage::helper('megaventory');
			$json_result = $helper->makeJsonRequest($data, 'SupplierClientUpdate', $customer->getId());
			
			$errorCode = $json_result['ResponseStatus']['ErrorCode'];
			if ($errorCode == '0'){//no errors
				if (strcmp('Insert', $mvRecordAction) == 0){
					$this->updateCustomer($customer->getId(), $json_result ['mvSupplierClient'] ['SupplierClientID']);
					return $json_result ['mvSupplierClient'] ['SupplierClientID'];
				}
				return $json_result['entityID'];
			}
			else
			{
				$entityId = $json_result['entityID']; 
				if (!empty($entityId) && $entityId > 0){
					if (strpos( $json_result['ResponseStatus']['Message'], 'and was since deleted') !== false) {
						$result = array(
								'mvCustomerId' => $json_result['entityID'],
								'errorcode' => 'isdeleted'
						);
						return $result;
					}
					else
					{
						$this->updateCustomer($customer->getId(), $entityId);
						$data['mvSupplierClient']['SupplierClientID'] = $entityId;
						$data['mvRecordAction'] = 'Update';
						$json_result = $helper->makeJsonRequest($data ,'SupplierClientUpdate',$customer->getId());
						return $entityId;
					}
				}
			}
		}
		return 0;
	}
	
	public function deleteCustomer($customer)
	{
		$megaVentoryId = $customer->getData('mv_supplierclient_id');
		
		if(isset($megaVentoryId) && $megaVentoryId!=NULL) //it is an update
		{
		
			$data = array('APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
					'SupplierClientIDToDelete'=>$megaVentoryId,
					'SupplierClientDeleteAction'=> 'DefaultAction',
			);
		
			$helper = Mage::helper('megaventory');
			$json_result = $helper->makeJsonRequest($data, 'SupplierClientDelete',$customer->getId());
			
			$errorCode = $json_result['ResponseStatus']['ErrorCode'];
			if ($errorCode == '0'){//no errors
				$resource = Mage::getSingleton ( 'core/resource' );
				$write = $resource->getConnection ( 'core/write' );
				$tableName = $resource->getTableName('customer_entity');
				$sql_insert = "update ".$tableName." set mv_supplierclient_id = NULL where entity_id = '" . $customer->getId() . "' ";
				$write->query ( $sql_insert );
			}
			
		}
	}
	
	public function addCustomerAddress($customerAddress)
	{
		
		$customer = Mage::getModel('customer/customer')->load($customerAddress->getParent_id());
		
		$megaVentoryId = $customer->getData('mv_supplierclient_id');
		
	}
	
}
