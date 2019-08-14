<?php

class Mv_Megaventory_Helper_Product extends Mage_Core_Helper_Abstract
{
	
	public function getProduct($mvProductId)
	{
		$APIKEY = Mage::getStoreConfig('megaventory/general/apikey');
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'query' => 'mv.ProductID = '.$mvProductId
		);
		
		
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'ProductGet',0);
		
		if ($json_result['ResponseStatus']['ErrorCode'] == 0){
			return $json_result['mvProducts'][0];
		}
		return -1;
 	}
	
	public function addProduct($product)
	{
		$productType = $product->getType_id();
		if ($productType == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE || $productType == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL)
		{
			
			$productId = $product->getEntityId();
			$product = Mage::getModel('catalog/product')->load($productId);
			$megaVentoryId = $product->getData('mv_product_id');
			$name = $product['name'];
			$sku = $product['sku'];
			if (isset($product['weight']) == false)
				$weight = '0';
			else
				$weight = $product['weight'];
			
			$price = '0';
			if (!empty($product['price']))
				$price = $product['price'];
			
			
			$orderHelper = Mage::helper('megaventory/order');
			$finalPriceNoTax = $orderHelper->getPrice($product, $product->getFinalPrice());
			
			$cost = '0';
			if (!empty($product['cost']))
				$cost = $product['cost'];
			
			$helper = Mage::helper('megaventory');
			
			//pass supplier on the fly
			$mvSupplierId = '';
			
			$supplierAttributeCode =  Mage::getStoreConfig('megaventory/general/supplierattributecode');
			
			if (isset($supplierAttributeCode)){
				$attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product',$supplierAttributeCode);
				$frontendInput = $attribute->getFrontendInput();
	
				$magentoSupplierId = $product->getData($supplierAttributeCode);
				
				if ($frontendInput == 'text')
					$supplierName = $magentoSupplierId;
				else if ($frontendInput == 'select')
					$supplierName = $product->getAttributeText($supplierAttributeCode);
				
				if (isset($magentoSupplierId) && ($frontendInput == 'text' || $frontendInput == 'select')){
					
					Mage::log('supplier name = '.$supplierName,null,'megaventory.log');
				
					if ($supplierName){
						
						$supplierData = array
						(
								'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
								'query'=> 'mv.SupplierClientName = "'.$supplierName.'"'
						);
					
						$json_result = $helper->makeJsonRequest($supplierData ,'SupplierClientGet');
						$errorCode = $json_result['ResponseStatus']['ErrorCode'];
						if ($errorCode == '0'){//no errors
							
							//supplier exists
							if (count($json_result['mvSupplierClients']) > 0){
								$mvSupplierId = $json_result['mvSupplierClients'][0]['SupplierClientID'];
							}
							else //supplier is new
							{
								$supplierData = array
								(
										'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
										'mvSupplierClient' => array (
												'SupplierClientID' => 0,
												'SupplierClientType' => '1',
												'SupplierClientName' => $supplierName,
												'SupplierClientBillingAddress' => '',
												'SupplierClientShippingAddress1' => '',
												'SupplierClientShippingAddress2' => '',
												'SupplierClientPhone1' => '',
												'SupplierClientPhone2' => '',
												'SupplierClientFax' => '',
												'SupplierClientIM' => '',
												'SupplierClientEmail' => '',
												'SupplierClientTaxID' => '',
												'SupplierClientComments' => '',
						 						),
										'mvRecordAction' => 'Insert' 
								);
				
								$json_result = $helper->makeJsonRequest($supplierData, 'SupplierClientUpdate',0);
								
								$errorCode = $json_result['ResponseStatus']['ErrorCode'];
								if ($errorCode == '0'){//no errors
									$mvSupplierId = $json_result['mvSupplierClient']['SupplierClientID'];
								}
							}
						}
					
						Mage::log('mv supplier id = '.$mvSupplierId,null,'megaventory.log');
					}
				}
			}
			
			$version = '';
			$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable') ->getParentIdsByChild($productId);
			if (isset($parentIds) && isset($parentIds[0]))
			{ 
				$parentProduct = Mage::getModel('catalog/product')->load($parentIds[0]);
				if (isset($parentProduct) && $parentProduct->getType_id() == 'configurable'){
					$simpleProduct = $product;
					$product = $parentProduct;
					
					$productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
					$attributeOptions = array();
					$attributeValues = array();
						
					foreach ($productAttributeOptions as $productAttribute) {
						//$attributeOptions[$productAttribute['attribute_code']] = $productAttribute['store_label'];
						
						foreach ($productAttribute['values'] as $attribute) {
							if ($attribute['value_index'] == $simpleProduct[$productAttribute['attribute_code']])
							{
								$attributeValues[$productAttribute['store_label']] = $attribute['label'];
								break;
							}
							//$attributeOptions[$productAttribute['label']][$attribute['value_index']] = $attribute['store_label'];
						}
					}
					foreach ($attributeValues as $Key => $Value)
						$version .= $Key . ':' . $Value . ';';
				}
			}
			
			$shortDescription = '';
			if (isset($product['short_description'])){
				$shortDescription = $product['short_description'];
				if (strlen($shortDescription) > 400)
					$shortDescription = mb_substr($shortDescription,0,400, "utf-8");
					//$shortDescription = substr($shortDescription, 0, 399);
			}
			$description = '';
			if (isset($product['description'])) 
			{
				$description = $product['description'];				
				if (strlen($description) > 400)
					$description = mb_substr($description,0,400, "utf-8");
					//$description = substr($description, 0, 399);
			}
			
			try{
				$image = $product->getImageUrl();
				if (strlen($image) > 200)
					$image = '';
			}
			catch(Exception $e)
			{
				$image = '';
			}
		
			if(isset($megaVentoryId) && $megaVentoryId!=NULL) //it is an update
			{
				$mvProductId = $megaVentoryId;
				$mvRecordAction = 'Update';
			}
			else //it is an insert
			{
				$mvProductId = '0';
				$mvRecordAction = 'Insert';
			}
		
			$categoryIds = $product->getCategoryIds();
			$mvCategoryId = '0';
			if (is_array($categoryIds)) {
				//randomly choose the first category
				if (isset($categoryIds[0])){
					$category = Mage::getModel('catalog/category')->load($categoryIds[0]);
					$categoryId = $category->getEntityId();
			
					$mvCategoryId = $category->getData('mv_productcategory_id');
		
					//if user adds a product that belongs to an unsynced category
					//megaventory then insert it to megaventory as orphan
					if (isset($mvCategoryId) == false || $mvCategoryId == NULL){
						$mvCategoryId = '0';
					}
				}
			}
		
		
			$attributeSetName = Mage::getModel('eav/entity_attribute_set')->load($product->getAttributeSetId())->getAttributeSetName();
			//$attributeSetName = 'attribute set:'.$attributeSetName;
			$productType = 'product type:'.$product->getType_id();
			//$productComments = $attributeSetName.','.$productType;
			//prepare data
			$APIKEY = Mage::getStoreConfig('megaventory/general/apikey');
			$data = array
			(
					'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
					'mvProduct'=> array
					(
							'ProductID' => $mvProductId,
							'ProductType' => "BuyFromSupplier",
							'ProductSKU' => $sku, 
							'ProductEAN' => '', //$product['ean'],
							'ProductDescription' => $name,
							'ProductVersion' => $version, //$product['version'],
							'ProductLongDescription' => $shortDescription,
							'ProductCategoryID' => $mvCategoryId,
							'ProductUnitOfMeasurement'=>'Unit(s)',
							'ProductSellingPrice'=>$finalPriceNoTax,
							'ProductPurchasePrice'=>$cost,
							'ProductWeight'=>$weight,
							'ProductLength'=>'0',
							'ProductBreadth'=>'0',
							'ProductHeight'=>'0',
							'ProductImageURL'=>$image,
							'ProductComments'=>'',
							'ProductCustomField1'=>$attributeSetName,
							'ProductCustomField2'=>'',
							'ProductCustomField3'=>'',
							'ProductMainSupplierID'=>$mvSupplierId,
							'ProductMainSupplierPrice'=>'0',
							'ProductMainSupplierSKU'=>'',
							'ProductMainSupplierDescription'=>'',
							
					),
					'mvRecordAction'=>$mvRecordAction
			);
			
			$json_result = $helper->makeJsonRequest($data ,'ProductUpdate',$productId);
		
		
			$errorCode = $json_result['ResponseStatus']['ErrorCode'];
			if ($errorCode == '0'){//no errors
				if (strcmp('Insert', $mvRecordAction) == 0){
					$this->updateProduct($productId,$json_result['mvProduct']['ProductID']);
				}
				
				return $json_result['mvProduct']['ProductID'];
			}
			else
			{
				$entityId = $json_result['entityID'];
				if (!empty($entityId) && $entityId > 0){ 
					if (strpos( $json_result['ResponseStatus']['Message'], 'and was since deleted') !== false) {
						$result = array(
								'mvProductId' => $json_result['entityID'],
								'errorcode' => 'isdeleted'
						);
						return $result;
					}
					else
					{
						$this->updateProduct($productId,$entityId);
						$data['mvProduct']['ProductID'] = $entityId;
						$data['mvRecordAction'] = 'Update';
						$json_result = $helper->makeJsonRequest($data ,'ProductUpdate',$productId);
					}
				}
			}
		}
		return 0;
	}
	
	public function addBundleProduct($product,$bundleCode,$options){
		$productId = $product->getEntityId();
		$product = Mage::getModel('catalog/product')->load($productId);
		
		
		$name = $product['name'];
		//$sku = $product['sku'].'_'.$bundleCode;
		//$sku = $bundleCode;
		$sku = 'bom_'.$this->generateRandomString();
		
		if (isset($product['weight']) == false)
			$weight = '0';
		else
			$weight = $product['weight'];
			
		$price = '0';
		if (!empty($product['price']))
			$price = $product['price'];
		
		$cost = '0';
		if (!empty($product['cost']))
			$cost = $product['cost'];
			
			
		$version = '';
		
			
		$shortDescription = '';
		if (isset($product['short_description'])){
			$shortDescription = $product['short_description'];
			if (strlen($shortDescription) > 400)
				$shortDescription = mb_substr($shortDescription,0,400, "utf-8");
				//$shortDescription = substr($shortDescription, 0, 400);
		}
		$description = '';
		if (isset($product['description']))
		{
			$description = $product['description'];
			if (strlen($description) > 400)
				$description = mb_substr($description,0,400, "utf-8");
				//$description = substr($description, 0, 400);
		}
		
		
		try{
			$image = $product->getImageUrl();
			if (strlen($image) > 200)
				$image = '';
		}
		catch(Exception $e)
		{
			$image = '';
		}
		
		$mvProductId = '0';
		$mvRecordAction = 'Insert';
		
		$categoryIds = $product->getCategoryIds();
		Mage::log('after categories',null,'megaventory.log');
		$mvCategoryId = '0';
		if (is_array($categoryIds)) {
			//randomly choose the first category
			if (isset($categoryIds[0])){
				$category = Mage::getModel('catalog/category')->load($categoryIds[0]);
				$categoryId = $category->getEntityId();
				
				Mage::log('category id = '.$categoryId,null,'megaventory.log');
				$mvCategoryId = $category->getData('mv_productcategory_id');
				
				Mage::log('mv category id = '.$mvCategoryId,null,'megaventory.log');
				//if user adds a product that belongs to an unsynced category
				//megaventory then insert it to megaventory as orphan
				if (isset($mvCategoryId) == false || $mvCategoryId == NULL){
					$mvCategoryId = '0';
				}
			}
		}
		
		Mage::log('before bundle insert ',null,'megaventory.log');
		
		$attributeSetName = Mage::getModel('eav/entity_attribute_set')->load($product->getAttributeSetId())->getAttributeSetName();
		//$attributeSetName = 'attribute set:'.$attributeSetName;
		$productType = 'product type:'.$product->getType_id();
		
		Mage::log('before bundle insert ',null,'megaventory.log');
		//$productComments = $attributeSetName.','.$productType;
		//prepare data
		$APIKEY = Mage::getStoreConfig('megaventory/general/apikey');
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'mvProduct'=> array
				(
						'ProductID' => $mvProductId,
						'ProductType' => "ManufactureFromWorkOrder",
						'ProductSKU' => $sku,
						'ProductEAN' => '', //$product['ean'],
						'ProductDescription' => $name,
						'ProductVersion' => $version, //$product['version'],
						'ProductLongDescription' => $shortDescription,
						'ProductCategoryID' => $mvCategoryId,
						'ProductUnitOfMeasurement'=>'Unit(s)',
						'ProductSellingPrice'=>$price,
						'ProductPurchasePrice'=>$cost,
						'ProductWeight'=>$weight,
						'ProductLength'=>'0',
						'ProductBreadth'=>'0',
						'ProductHeight'=>'0',
						'ProductImageURL'=>$image,
						'ProductComments'=>'bundle product',
						'ProductCustomField1'=>$attributeSetName,
						'ProductCustomField2'=>'',
						'ProductCustomField3'=>'',
						'ProductMainSupplierID'=>'0',
						'ProductMainSupplierPrice'=>'0',
						'ProductMainSupplierSKU'=>'',
						'ProductMainSupplierDescription'=>'',
							
				),
				'mvRecordAction'=>$mvRecordAction
		);
			
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'ProductUpdate',$productId);
		
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode == '0'){//no errors
			
			$mvRawMaterials = array();
			foreach ($options as $option){
				
				$rawProduct = $option['product'];
			
				$mvRawMaterialItem = array(
						'ProductSKU' => $rawProduct->getSku(),
						'RawMaterialQuantity' => $option['qty']
						);
						
				$mvRawMaterials[] = $mvRawMaterialItem;
			}
			
			$bomData = array();
			//$data['mvProduct']['mvRawMaterials'] = $mvRawMaterials;
			
			unset($data['APIKEY']);
			unset($data['mvRecordAction']);
			$bomData['APIKEY'] = Mage::getStoreConfig('megaventory/general/apikey');
			$bomData['mvRecordAction'] = 'Update';
			$bomData['mvProductBOM']['ProductSKU'] = $sku;
			$bomData['mvProductBOM']['mvRawMaterials'] = $mvRawMaterials;
				
			
			$json_result = $helper->makeJsonRequest($bomData ,'ProductBOMUpdate',$productId);
			
			$errorCode = $json_result['ResponseStatus']['ErrorCode'];
			if ($errorCode == '0'){//no errors
				$this->updateMegaventoryBOMs($productId, $json_result['mvProductBOM']['ProductID'], $bundleCode, $sku);
				
				//add newly created bom also as simple product in magento
				$this->addBomAsSimpleProduct($product,$sku,$bundleCode,$json_result['mvProductBOM']['ProductID']);
				
				return $sku;
			}
		}
		return -1;
	}
	
	public function bundleProductExists($bundleCode){
		//check local bom table
		return Mage::getModel('megaventory/bom')->loadByBOMCode($bundleCode);
		
	}
	
	public function deleteProduct($product)
	{
		$productId = $product->getId();
		$megaVentoryId = $product->getData('mv_product_id');
	
		if(isset($megaVentoryId) && $megaVentoryId!=NULL)
		{
			$data = array
			(
					'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
					'ProductIDToDelete'=> $megaVentoryId
			);
			
			$helper = Mage::helper('megaventory');
			$json_result = $helper->makeJsonRequest($data ,'ProductDelete',$productId);
	
			$errorCode = $json_result['ResponseStatus']['ErrorCode'];
			if ($errorCode == '0'){//no errors
				//make null the back end reference
				$this->updateProduct($productId,'null');
	
			}
		}
	}
	
	public function synchProducts()
	{
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'ProductMainSupplierID>'=> 235
		);
			
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'ProductGet',0);
		
		$mvProducts = $json_result[mvProducts];
		
		$i = 0;
		
		foreach($mvProducts as $mvProduct)
		{
			$mvProductID = $mvProduct['ProductID'];
			$mvProductSKU = $mvProduct['ProductSKU'];
			$mvProductSupplier = $mvProduct['ProductMainSupplierID'];
			if (isset($mvProductID))
			{
				$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$mvProductSKU);
				if ($product!=false){
					$productId = $product->getEntityId();
					$this->updateProduct($productId,$mvProductID);
				}
			}
			$i++;
		}
		
		return $i;
	}
	
	public function importProductsToMegaventory($megaventoryHelper,$page = 1,$imported = 0){
		
		/* if ($supplier!=false){
			$supplierAttributeId = Mage::getResourceModel('eav/entity_attribute')
			->getIdByCode('catalog_product',$supplier);
		} */
		
		$model = Mage::getModel('catalog/product');
		$simple_products = $model->getCollection()
		->addAttributeToSelect('name')
		->addAttributeToSelect('description')
		->addAttributeToSelect('price')
		->addAttributeToSelect('cost')
		->addFieldToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
		->addAttributeToFilter(
				array(
						array('attribute' => 'type_id', 'eq' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE),
						array('attribute' => 'type_id', 'eq' => Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL)
				))
		->addAttributeToSort('entity_id','ASC')
		->addAttributeToSort('type_id','ASC');
		
		
		$simple_products->setPageSize(20);
		$simple_products->setCurPage($page);
		$totalCollectionSize = $simple_products->getSize();
		$isLastPage = false;
		if ((int)($totalCollectionSize/20) == $page-1)
			$isLastPage = true;
		/* if (isset($supplierAttributeId) && $supplierAttributeId != false)
			$simple_products->addAttributeToSelect($supplier); */
		
		
		$total = $imported;// + ($page-1)*10;
		foreach($simple_products as $product) {
			try{
				$inserted = $this->insertSingleProduct($product);
				if ($inserted == 0 || $inserted == 1)
				{
					$total++;
					$message = $total.'/'.$totalCollectionSize;
					$megaventoryHelper->sendProgress(31, $message, $page, 'products', false);
				}
			}
			catch(Exception $ex){
				Mage::log($ex->getMessage(),null,'api.log',true);
				$event = array(
						'code' => 'Product Insert',
						'result' => '',
						'magento_id' => $product->getId(),
						'return_entity' => '0',
						'details' => $ex->getMessage(),
						'data' => ''
				);
				Mage::helper('megaventory')->log($event);
				continue;
			}
		}
		if ($isLastPage){
			$message = $total.'/'.$totalCollectionSize.' products imported'.Mage::registry('tickImage');
			if ($total != $totalCollectionSize){
				$logUrl = Mage::helper("adminhtml")->getUrl("megaventory/index/log");
				$message .= '<br>'.$totalCollectionSize-$total.' product(s) were not imported. Check <a href="'.$logUrl.'" target="_blank">Megaventory Log</a> for details'.Mage::registry('errorImage');
			}
			$megaventoryHelper->sendProgress(31, $message, $page, 'products', true);
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
	
	public function insertSingleProduct($product)
	{
		$productId = $product->getEntityId();
		Mage::log('product id = '.$productId,null,'megaventory.log');
		$product = Mage::getModel('catalog/product')->load($productId);
		
		$megaVentoryId = $product->getData('mv_product_id');
		$name = $product['name'];
		$sku = $product['sku'];
		if (isset($product['weight']) == false)
			$weight = '0';
		else
			$weight = $product['weight'];
			
		if (isset($product['price']) == false)
			$price = '0';
		else
			$price = $product['price'];
		$orderHelper = Mage::helper('megaventory/order');
		$finalPriceNoTax = $orderHelper->getPrice($product, $product->getFinalPrice());
		

		Mage::log('final price = '.$finalPriceNoTax,null,'megaventory.log');
		
		if (isset($product['cost']) == false)
			$cost = '0';
		else
			$cost = $product['cost'];
			
		//pass supplier on the fly
		$helper = Mage::helper('megaventory');
		$mvSupplierId = '';
			
		$supplierAttributeCode =  Mage::getStoreConfig('megaventory/general/supplierattributecode');
			
		if (isset($supplierAttributeCode)){
			$attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product',$supplierAttributeCode);
			$frontendInput = $attribute->getFrontendInput();
		
			$magentoSupplierId = $product->getData($supplierAttributeCode);
		
			if ($frontendInput == 'text')
				$supplierName = $magentoSupplierId;
			else if ($frontendInput == 'select')
				$supplierName = $product->getAttributeText($supplierAttributeCode);
		
			if (isset($magentoSupplierId) && ($frontendInput == 'text' || $frontendInput == 'select')){
					
				Mage::log('supplier name = '.$supplierName,null,'megaventory.log');
		
				if ($supplierName){
		
					$supplierData = array
					(
							'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
							'query'=> 'mv.SupplierClientName = "'.$supplierName.'"'
					);
						
					$json_result = $helper->makeJsonRequest($supplierData ,'SupplierClientGet');
					$errorCode = $json_result['ResponseStatus']['ErrorCode'];
					if ($errorCode == '0'){//no errors
							
						//supplier exists
						if (count($json_result['mvSupplierClients']) > 0){
							$mvSupplierId = $json_result['mvSupplierClients'][0]['SupplierClientID'];
						}
						else //supplier is new
						{
							$supplierData = array
							(
									'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
									'mvSupplierClient' => array (
											'SupplierClientID' => 0,
											'SupplierClientType' => '1',
											'SupplierClientName' => $supplierName,
											'SupplierClientBillingAddress' => '',
											'SupplierClientShippingAddress1' => '',
											'SupplierClientShippingAddress2' => '',
											'SupplierClientPhone1' => '',
											'SupplierClientPhone2' => '',
											'SupplierClientFax' => '',
											'SupplierClientIM' => '',
											'SupplierClientEmail' => '',
											'SupplierClientTaxID' => '',
											'SupplierClientComments' => '',
									),
									'mvRecordAction' => 'Insert'
							);
		
							$json_result = $helper->makeJsonRequest($supplierData, 'SupplierClientUpdate',0);
		
							$errorCode = $json_result['ResponseStatus']['ErrorCode'];
							if ($errorCode == '0'){//no errors
								$mvSupplierId = $json_result['mvSupplierClient']['SupplierClientID'];
							}
						}
					}
						
					Mage::log('mv supplier id = '.$mvSupplierId,null,'megaventory.log');
				}
			}
		}
		
		$version = '';
		$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable') ->getParentIdsByChild($productId);
		if (isset($parentIds) && isset($parentIds[0]))
		{
			$parentProduct = Mage::getModel('catalog/product')->load($parentIds[0]);
			if (isset($parentProduct) && $parentProduct->getType_id() == 'configurable'){
				$simpleProduct = $product;
				$product = $parentProduct;
					
					
				$productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
				$attributeOptions = array();
				$attributeValues = array();
		
				foreach ($productAttributeOptions as $productAttribute) {
					//$attributeOptions[$productAttribute['attribute_code']] = $productAttribute['store_label'];
					$attributeValue = Mage::getResourceModel('catalog/product')->getAttributeRawValue($simpleProduct->getId(), $productAttribute['attribute_code']);
					foreach ($productAttribute['values'] as $attribute) {
						if ($attribute['value_index'] == $attributeValue)
						{
							$attributeValues[$productAttribute['store_label']] = $attribute['label'];
							break;
						}
					}
				}
				foreach ($attributeValues as $Key => $Value)
					$version .= $Key . ':' . $Value . ';';
			}
		}
			
		$shortDescription = '';
		if (isset($product['short_description'])){
			$shortDescription = $product['short_description'];
			if (strlen($shortDescription) > 400)
				$shortDescription = mb_substr($shortDescription,0,400, "utf-8");
				//$shortDescription = substr($shortDescription, 0, 400);
		}
		$description = '';
		if (isset($product['description']))
		{
			$description = $product['description'];
			if (strlen($description) > 400)
				$description = mb_substr($description,0,400, "utf-8");
				//$description = substr($description, 0, 400);
		}
		
		try{
			$image = $product->getImageUrl();
			if (strlen($image) > 200)
				$image = '';
		}
		catch(Exception $ex)
		{
			$image = '';
		}
		$mvProductId = '0';
		$mvRecordAction = 'Insert';
		
		$categoryIds = $product->getCategoryIds();
		$mvCategoryId = '0';
		if (is_array($categoryIds)) {
			//randomly choose the first category
			if (isset($categoryIds[0])){
				$category = Mage::getModel('catalog/category')->load($categoryIds[0]);
				$categoryId = $category->getEntityId();
					
				$mvCategoryId = $category->getData('mv_productcategory_id');
		
				//if user adds a product that belongs to an unsynced category
				//megaventory then insert it to megaventory as orphan
				if (isset($mvCategoryId) == false || $mvCategoryId == NULL){
					$mvCategoryId = '0';
				}
			}
		}
		
		
		$attributeSetName = Mage::getModel('eav/entity_attribute_set')->load($product->getAttributeSetId())->getAttributeSetName();
		$productType = 'product type:'.$product->getType_id();
		//prepare data
		$APIKEY = Mage::getStoreConfig('megaventory/general/apikey');
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'mvProduct'=> array
				(
						'ProductID' => $mvProductId,
						'ProductType' => "BuyFromSupplier",
						'ProductSKU' => $sku,
						'ProductEAN' => '', //$product['ean'],
						'ProductDescription' => $name,
						'ProductVersion' => $version, //$product['version'],
						'ProductLongDescription' => $shortDescription,
						'ProductCategoryID' => $mvCategoryId,
						'ProductUnitOfMeasurement'=>'Unit(s)',
						'ProductSellingPrice'=>$finalPriceNoTax,
						'ProductPurchasePrice'=>$cost,
						'ProductWeight'=>$weight,
						'ProductLength'=>'0',
						'ProductBreadth'=>'0',
						'ProductHeight'=>'0',
						'ProductImageURL'=>$image,
						'ProductComments'=>'',
						'ProductCustomField1'=>$attributeSetName,
						'ProductCustomField2'=>'',
						'ProductCustomField3'=>'',
						'ProductMainSupplierID'=> $mvSupplierId,
						'ProductMainSupplierPrice'=>'0',
						'ProductMainSupplierSKU'=>'',
						'ProductMainSupplierDescription'=>'',
							
				),
				'mvRecordAction'=>$mvRecordAction
		);
			
		$json_result = $helper->makeJsonRequest($data ,'ProductUpdate',$productId);
		
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode == '0'){//no errors
			if (strcmp('Insert', $mvRecordAction) == 0){
				$this->updateProduct($productId,$json_result['mvProduct']['ProductID']);
				$mvProductId = $json_result['mvProduct']['ProductID'];
				
				//update alert level
				/* $stockItem = $product->getStock_item();
				$quantity = '0';
				$alertLevel = 0;
				
				if (isset($stockItem)){
					$useConfigNotify = $stockItem->getData('use_config_notify_stock_qty');
					if ($useConfigNotify == '1'){
						//get config value
						$configValue = Mage::getStoreConfig('cataloginventory/item_options/notify_stock_qty');
						if (isset($configValue))
							$alertLevel = $configValue;
						else
							$alertLevel = 0;
					}
					else{
						$alertLevel = $stockItem->getData('notify_stock_qty');
					}
				}
				
				$inventory = Mage::getModel('megaventory/inventories')->loadDefault();
				
				$alertData = array
				(
						'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
						'mvProductStockAlertsAndSublocationsList'=> array
						(
								'productID' => $mvProductId,
								'mvInventoryLocationStockAlertAndSublocations' => array(
										'InventoryLocationID' => $inventory->getData('megaventory_id'),
										'StockAlertLevel' => $alertLevel
								)
									
						)
				);
				
				$helper->makeJsonRequest($alertData ,'InventoryLocationStockAlertAndSublocationsUpdate');
				
				$productStock = Mage::getModel('megaventory/productstocks')
				->loadInventoryProductstock($inventory->getId(), $product->getId());
					
				$productStock->setProduct_id($productId);
				$productStock->setInventory_id($inventory->getId());
				$productStock->setStockalarmqty($alertLevel);
				$productStock->save(); */
				
			}
		}
		else
		{
			$entityId = $json_result['entityID']; //if product exists just sync them
			if (!empty($entityId) && $entityId > 0){
				$this->updateProduct($productId,$entityId);
				$mvProductId = $entityId;
				
				//update alert level
				/* $stockItem = $product->getStock_item();
				$quantity = '0';
				$alertLevel = 0;
				
				if (isset($stockItem)){
					$useConfigNotify = $stockItem->getData('use_config_notify_stock_qty');
					if ($useConfigNotify == '1'){
						//get config value
						$configValue = Mage::getStoreConfig('cataloginventory/item_options/notify_stock_qty');
						if (isset($configValue))
							$alertLevel = $configValue;
						else
							$alertLevel = 0;
					}
					else{
						$alertLevel = $stockItem->getData('notify_stock_qty');
					}
				}
				
				$inventory = Mage::getModel('megaventory/inventories')->loadDefault();
				
				$alertData = array
				(
						'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
						'mvProductStockAlertsAndSublocationsList'=> array
						(
								'productID' => $mvProductId,
								'mvInventoryLocationStockAlertAndSublocations' => array(
										'InventoryLocationID' => $inventory->getData('megaventory_id'),
										'StockAlertLevel' => $alertLevel
								)
									
						)
				);
				
				$helper->makeJsonRequest($alertData ,'InventoryLocationStockAlertAndSublocationsUpdate');
				
				$productStock = Mage::getModel('megaventory/productstocks')
				->loadInventoryProductstock($inventory->getId(), $product->getId());
					
				$productStock->setProduct_id($productId);
				$productStock->setInventory_id($inventory->getId());
				$productStock->setStockalarmqty($alertLevel);
				$productStock->save(); */
				
				
				return 1;
			}
		}
		
		
		
		return $errorCode;
	}
	
	public function addShippingProduct($megaventoryHelper)
	{
		$shippingSKU = Mage::getStoreConfig('megaventory/general/shippingproductsku');
		if (empty($shippingSKU))
			$shippingSKU = Mv_Megaventory_Helper_Common::SHIPPINGSKU;
		
		$APIKEY = Mage::getStoreConfig('megaventory/general/apikey');
		$data = array
		(
				'APIKEY' => $APIKEY,
				'mvProduct'=> array
				(
						'ProductID' => '0',
						'ProductType' => 'BuyFromSupplier',
						'ProductSKU' => $shippingSKU,
						'ProductEAN' => '',
						'ProductDescription' => 'Default Magento Shipping',
						'ProductVersion' => '',
						'ProductLongDescription' => '',
						'ProductCategoryID' => '0',
						'ProductUnitOfMeasurement'=>'Unit(s)',
						'ProductSellingPrice'=> '0',
						'ProductPurchasePrice'=> '0',
						'ProductWeight'=>'0',
						'ProductLength'=>'0',
						'ProductBreadth'=>'0',
						'ProductHeight'=>'0',
						'ProductImageURL'=> '',
						'ProductComments'=>'',
						'ProductCustomField1'=>'',
						'ProductCustomField2'=>'',
						'ProductCustomField3'=>'',
						'ProductMainSupplierID'=>'0',
						'ProductMainSupplierPrice'=>'0',
						'ProductMainSupplierSKU'=>'',
						'ProductMainSupplierDescription'=>''
				),
				'mvRecordAction'=>'Insert'
		);
			
		$helper = Mage::helper('megaventory');
		try{
			$json_result = $helper->makeJsonRequest($data ,'ProductUpdate',0);
		}
		catch (Exception $ex){
			return 'There was a problem connecting to your Megaventory account. Please try again.';
		}
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0'){
			if (empty($json_result['entityID']))
				return $json_result['ResponseStatus']['Message'];
			
			return true;
		}
		else
		{
			//Mage::getConfig()->saveConfig('megaventory/general/shippingproductsku','shipping_service_01');
			return true;
		}
	}
	
	public function addDiscountProduct($megaventoryHelper)
	{
		$discountSKU = Mage::getStoreConfig('megaventory/general/discountproductsku');
		if (empty($discountSKU))
			$discountSKU = Mv_Megaventory_Helper_Common::DISCOUNTSKU;
		
		$APIKEY = Mage::getStoreConfig('megaventory/general/apikey');
		$data = array
		(
				'APIKEY' => $APIKEY,
				'mvProduct'=> array
				(
						'ProductID' => '0',
						'ProductType' => 'BuyFromSupplier',
						'ProductSKU' => $discountSKU,
						'ProductEAN' => '',
						'ProductDescription' => 'Magento Discount',
						'ProductVersion' => '',
						'ProductLongDescription' => '',
						'ProductCategoryID' => '0',
						'ProductUnitOfMeasurement'=>'Unit(s)',
						'ProductSellingPrice'=> '0',
						'ProductPurchasePrice'=> '0',
						'ProductWeight'=>'0',
						'ProductLength'=>'0',
						'ProductBreadth'=>'0',
						'ProductHeight'=>'0',
						'ProductImageURL'=> '',
						'ProductComments'=>'',
						'ProductCustomField1'=>'',
						'ProductCustomField2'=>'',
						'ProductCustomField3'=>'',
						'ProductMainSupplierID'=>'0',
						'ProductMainSupplierPrice'=>'0',
						'ProductMainSupplierSKU'=>'',
						'ProductMainSupplierDescription'=>''
				),
				'mvRecordAction'=>'Insert'
		);
			
		$helper = Mage::helper('megaventory');
		try{
			$json_result = $helper->makeJsonRequest($data ,'ProductUpdate',0);
		}
		catch (Exception $ex){
			return 'There was a problem connecting to your Megaventory account. Please try again.';
		}
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0'){
			if (empty($json_result['entityID']))
				return $json_result['ResponseStatus']['Message'];
			
			return true;
		}
		else
		{
			//Mage::getConfig()->saveConfig('megaventory/general/discountproductsku','discount_01');
			return true;
		}
	}
	
	public function exportStock($inventoryName){
		$model = Mage::getModel('catalog/product');
		$simple_products = $model->getCollection()
		->addAttributeToSelect('name')
		->addAttributeToSelect('description')
		->addAttributeToSelect('cost')
		->addAttributeToSelect('qty')
		->addFieldToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
		->addAttributeToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
		->joinField(
		    'qty',
		    'cataloginventory/stock_item',
		    'qty',
		    'product_id=entity_id',
		    '{{table}}.stock_id=1',
		    'left'
		) ;
		
		$ioConfig = array(
				'path' => 'var/export'
		);
		$file = new Varien_Io_File();
		$file->setAllowCreateFolders(true);
		$file->open($ioConfig);
		$file->streamOpen('InitialQuantities.csv', 'w+');
		
		$baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
		
		$header = array(
				'0' => 'Product Category',
				'1'	=> 'Product Description',
				'2' => 'SKU',
				'3'	=> 'EAN',
				'4' => 'Unit of Stock',
				'5' => 'Quantity - '.$inventoryName,
				'6' => 'Unit Cost (average cost) ('.$baseCurrencyCode.')',
				'7' => 'Remarks',
				);
		$file->streamWriteCsv($header,',','"');
		
		//$i = 0;
		foreach($simple_products as $product) {
			try{
				//ignore products which are not synchronized
				if (empty($product['mv_product_id']))
					continue;
				
				$sku = trim($product['sku']);
				if (isset($product['qty']) && $product['qty'] > 0)
					$qty = $product['qty'];
				else
					continue;
				
				$row  = array
				(
					'0' => '',
					'1'	=> '',
					'2' => $sku,
					'3'	=> '',
					'4' => '',
					'5' => $qty,
					'6' => isset($product['cost']) ? $product['cost'] : '0',
					'7' => '',
				);
				$file->streamWriteCsv($row,',','"');
				/* $i++;
				if ($i==5)
					break; */
			}
			catch(Exception $ex){
				Mage::log($ex->getMessage(),null,'api.log',true);
			}
		}
		
		//add very large initial quantities for virtual products
		$virtual_products = $model->getCollection()
		->addAttributeToSelect('name')
		->addAttributeToSelect('description')
		->addAttributeToSelect('cost')
		->addAttributeToSelect('qty')
		->addFieldToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
		->addAttributeToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL);
		
		
		
		foreach($virtual_products as $product) {
			try{
				//$row = '"","","'.$product['sku'].'","","","'.$product['qty'].'","'.$product['cost'].'",""';
				$sku = trim($product['sku']);
				if (isset($product['qty']) && $product['qty'] > 0)
					$qty = $product['qty'];
				else
					$qty = '0';
				$row  = array
				(
						'0' => '',
						'1'	=> '',
						'2' => $sku,
						'3'	=> '',
						'4' => '',
						'5' => '1000000',
						'6' => isset($product['cost']) ? $product['cost'] : '0',
						'7' => '',
				);
				$file->streamWriteCsv($row,',','"');
			}
			catch(Exception $ex){
				Mage::log($ex->getMessage(),null,'api.log',true);
			}
		}
		
		$shippingSKU = Mage::getStoreConfig('megaventory/general/shippingproductsku');
		if (empty($shippingSKU))
			$shippingSKU = Mv_Megaventory_Helper_Common::SHIPPINGSKU;
		
		$discountSKU = Mage::getStoreConfig('megaventory/general/discountproductsku');
		if (empty($discountSKU))
			$discountSKU = Mv_Megaventory_Helper_Common::DISCOUNTSKU;
		
		$row  = array
		(
				'0' => '',
				'1'	=> '',
				'2' => $shippingSKU,
				'3'	=> '',
				'4' => '',
				'5' => '1000000',
				'6' => '0',
				'7' => '',
		);
		$file->streamWriteCsv($row,',','"');
		
		$row  = array
		(
				'0' => '',
				'1'	=> '',
				'2' => $discountSKU,
				'3'	=> '',
				'4' => '',
				'5' => '1000000',
				'6' => '0',
				'7' => '',
		);
		$file->streamWriteCsv($row,',','"');
		
		$file->streamClose();
		$file->close();
	}
	
	public function getBundleOptions($item)
	{
		//$item = Mage::getModel('sales/quote_item')->load($orderItem->getQuote_item_id());
		$options = array();
		$product = $item->getProduct();
	
		/**
		 * @var Mage_Bundle_Model_Product_Type
		 */
		$typeInstance = $product->getTypeInstance(true);
	
		// get bundle options
		$optionsQuoteItemOption = $item->getOptionByCode('bundle_option_ids');
		$bundleOptionsIds = $optionsQuoteItemOption ? unserialize($optionsQuoteItemOption->getValue()) : array();
		if ($bundleOptionsIds) {
			/**
			 * @var Mage_Bundle_Model_Mysql4_Option_Collection
			 */
			$optionsCollection = $typeInstance->getOptionsByIds($bundleOptionsIds, $product);
	
			// get and add bundle selections collection
			$selectionsQuoteItemOption = $item->getOptionByCode('bundle_selection_ids');
	
			$bundleSelectionIds = unserialize($selectionsQuoteItemOption->getValue());
	
			if (!empty($bundleSelectionIds)) {
				$selectionsCollection = $typeInstance->getSelectionsByIds(
						unserialize($selectionsQuoteItemOption->getValue()),
						$product
				);
	
				$bundleOptions = $optionsCollection->appendSelections($selectionsCollection, true);
				foreach ($bundleOptions as $bundleOption) {
					if ($bundleOption->getSelections()) {
	
						$bundleSelections = $bundleOption->getSelections();
	
						$option = array();
						foreach ($bundleSelections as $bundleSelection) {
							$qty = $this->getSelectionQty($product, $bundleSelection->getSelectionId()) * 1;
							Mage::log('bundle qty ='.$qty,null,'megaventory.log',true);
							$price = $this->getSelectionFinalPrice($item, $bundleSelection);
							Mage::log('bundle price ='.$price,null,'megaventory.log',true);
							$option['qty'] = $qty;
							$option['price'] = $price;
							$option['product'] = $bundleSelection;
							
							$options[$bundleSelection->getProductId()] = $option; 
						}
					}
				}
			}
		}
	
		return $options;
	}
	
	public function getSelectionQty($product, $selectionId)
	{
		$selectionQty = $product->getCustomOption('selection_qty_' . $selectionId);
		if ($selectionQty) {
			return $selectionQty->getValue();
		}
		return 0;
	}
	
	//public function getSelectionFinalPrice(Mage_Catalog_Model_Product_Configuration_Item_Interface $item,
	public function getSelectionFinalPrice($item,
			$selectionProduct)
	{
		$selectionProduct->unsetData('final_price');
		$product = $item->getProduct();
		//Mage::log('got product',null,'megaventory.log',true);
		$priceModel = $product->getPriceModel();
		return $priceModel->getSelectionFinalPrice(
				$item->getProduct(),
				$selectionProduct,
				$item->getQty() * 1,
				$this->getSelectionQty($item->getProduct(), $selectionProduct->getSelectionId()),
				false,
				true
		);
		/* if (version_compare(Mage::getVersion(), '1.5.0','<')){
			$test = $priceModel->getSelectionFinalPrice(
	 				$item->getProduct(),
	 				$selectionProduct,
	 				$item->getQty() * 1,
	 				$this->getSelectionQty($item->getProduct(), $selectionProduct->getSelectionId()),
	 				false,
	 				true
	 		);
			Mage::log('test = '.$test,null,'megaventory.log',true);
			
			return $test;
		}
		else {
			return $item->getProduct()->getPriceModel()->getSelectionFinalTotalPrice(
					$item->getProduct(),
					$selectionProduct,
					$item->getQty() * 1,
					$this->getSelectionQty($item->getProduct(), $selectionProduct->getSelectionId()),
					false,
					true
			);
		} */
	}
	
	public function addBomAsSimpleProduct($parentProduct, $sku, $bundleCode,$megaventoryId){
		$product = new Mage_Catalog_Model_Product();
		// Build the product
		$product->setSku($sku);
		$product->setAttributeSetId($parentProduct->getAttributeSetId());
		$product->setTypeId('simple');
		$product->setName($parentProduct->getName()." -- ".$bundleCode);
		$product->setCategoryIds($parentProduct->getCategoryIds()); 
		$product->setWebsiteIDs($parentProduct->getWebsiteIds()); 
		$product->setDescription('Automatically created BOM product');
		$product->setShortDescription('Automatically created BOM product');
		$product->setPrice(0); # Set some price
		# Custom created and assigned attributes
		$product->setHeight('0');
		$product->setWidth('0');
		$product->setDepth('0');
		//Default Magento attribute
		$product->setWeight(0);
		$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
		$product->setStatus(1);//enabled
		$product->setTaxClassId(1); 
		$product->setStockData(array(
				'is_in_stock' => 1,
				'qty' => 1
		));
		$product->setCreatedAt(strtotime('now'));
		
		try {
			$product = $product->save();
			
			//add megaventory product id
			$this->updateProduct($product->getId(), $megaventoryId);
			
			//add initial megaventory stock
			$inventoriesHelper = Mage::helper('megaventory/inventories'); 
			$inventories = $inventoriesHelper->getInventories();
			$stockData = array('stockqty'=>0,'stockqtyonhold'=>0,'stockalarmqty'=>0,'stocknonshippedqty'=>0,
					'stocknonreceivedqty'=>0,'stockwipcomponentqty'=>0,'stocknonreceivedwoqty'=>0,'stocknonallocatedwoqty'=>0);
			foreach ($inventories as $inventory){
				$inventoriesHelper->updateInventoryProductStock($product->getId(),$inventory->getId(),$stockData);
			}
		}
		catch (Exception $ex) {
			//Handle the error
		}
	}
	
	public function generateRandomString($length = 10) {
		return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
	}
	
	public function updateMegaventoryBOMs($magentoId, $megaventoryId, $autoCode,$megaventorySKU){
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$tableName = $resource->getTableName('megaventory_bom');
		$sql_insert = "insert into ".$tableName." (magento_product_id,megaventory_id,auto_code,megaventory_sku) values (".$magentoId.",".$megaventoryId.",'".$autoCode."','".$megaventorySKU."')";
		$write->query($sql_insert);
	}
	
	public function updateProduct($productId, $mvProductId){
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$table = $resource->getTableName ( 'catalog/product' );
		$sql_insert = "update ".$table." set mv_product_id = ".$mvProductId." where entity_id = ".$productId;
		$write->query($sql_insert);
	}
	
	public function undeleteProduct($mvProductId){
		$data = array(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'ProductIDToUndelete' => $mvProductId
		);
		
		$helper = Mage::helper('megaventory');
		
		$helper->makeJsonRequest($data, 'ProductUndelete');
	}
}
