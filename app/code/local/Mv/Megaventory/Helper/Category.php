<?php

class Mv_Megaventory_Helper_Category extends Mage_Core_Helper_Abstract
{
	
	public function synchCategories()
	{
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey')
		);
			
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'ProductCategoryGet',0);
		
		$mvCategories = $json_result[mvProductCategories];
		
		$i = 0;
		
		foreach($mvCategories as $mvCategory)
		{
			$mvProductCategoryName = $mvCategory['ProductCategoryName'];
			$mvProductCategoryID = $mvCategory['ProductCategoryID'];
			$magentoCategoryID = $mvCategory['ProductCategoryDescription'];
			if (!empty($magentoCategoryID) && !empty($mvProductCategoryID))
			{	
				$this->updateCategory($magentoCategoryID, $mvProductCategoryID);
				$i++;	
			}
		}
		
		return $i-1;
				
	}
	
	public function updateCategory($categoryId, $mvCategoryId){
		
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$table = $resource->getTableName ( 'catalog/category' );
		$sql_insert = "update ".$table." set mv_productcategory_id = ".$mvCategoryId." where entity_id = ".$categoryId;
		$write->query($sql_insert);
	}

	public function importCategoriesToMegaventory($megaventoryHelper,$page = 1,$imported = 0)
	{
		$collection = Mage::getModel('catalog/category')->getCollection()
		->addAttributeToSelect('level');
		$collection->addAttributeToSelect('path');
		$collection->addAttributeToSelect('entity_id');
		$collection->addAttributeToSelect('name');
		$collection->getSelect()->order('level');
        $collection->setPageSize(30);
        $collection->setCurPage($page);
		$totalCollectionSize = $collection->getSize();
        
		$isLastPage = false;
        if ((int)($totalCollectionSize/30) == $page-1)
        	$isLastPage = true;
		
		
		$total = $imported;// + ($page-1)*10;
		foreach($collection as $category) {
			try{
				$inserted = $this->insertSingleCategory($category);
				if ($inserted == 0 || $inserted == 1){ //no errors
					$total++;
					$message = $total.'/'.$totalCollectionSize;
					$megaventoryHelper->sendProgress(21, $message, $page, 'categories', false);
				}
			}
			catch(Exception $ex){
				$event = array(
						'code' => 'Category Insert',
						'result' => '',
						'magento_id' => $category->getId(),
						'return_entity' => '0',
						'details' => $ex->getMessage(),
						'data' => ''
				);
				Mage::helper('megaventory')->log($event);
			}
		}
		if ($isLastPage){
			//$megaventoryHelper->sendProgress(22, $total.'/'.$totalCollectionSize.' categories synchronized'.Mage::registry('tickImage'), '0', 'categories',true);
			$message = $total.'/'.$totalCollectionSize.' categories imported'.Mage::registry('tickImage');
			if ($total != $totalCollectionSize){
				$dif = $totalCollectionSize-$total;
				$logUrl = Mage::helper("adminhtml")->getUrl("megaventory/index/log");
				if ($dif == 1)
					$message .= '<br>'.$dif.' category was not imported. Check <a href="'.$logUrl.'" target="_blank">Megaventory Log</a> for details'.Mage::registry('errorImage');
				else
					$message .= '<br>'.$dif.' categories were not imported. Check <a href="'.$logUrl.'" target="_blank">Megaventory Log</a> for details'.Mage::registry('errorImage');
			}
			$megaventoryHelper->sendProgress(21, $message, $page, 'categories', true);
			
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
		//return $total;
	}
	
	public function createCategoryName($category)
	{
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
	
	public function insertSingleCategory($category)
	{
		$helper = Mage::helper('megaventory');
		$megaventoryId = '0';
		$descr = $category->getDescription();
		$name = $this->createCategoryName($category);
		
		//default magento sample data get a 'root' category
		//with no name. we insert it in Mv using the 
		//the special [No Name] title
		if (empty($name)){
			$name = '[No Name]';
			//return -1; 
		}
		
		if (isset($descr) && $descr != NULL)
			$description = $descr;
		else
			$description = '';
		
		$data = array (
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'mvProductCategory' => array (
						'ProductCategoryID' => $megaventoryId,
						'ProductCategoryName' => $name,
						'ProductCategoryDescription' => $description ),
				'mvRecordAction' => 'Insert' );
		
		$json_result = $helper->makeJsonRequest($data, 'ProductCategoryUpdate',$category->getId());
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode == '0'){//no errors
			$this->updateCategory($category->getId(), $json_result ['mvProductCategory'] ['ProductCategoryID']);
		}
		else
		{
			$entityId = $json_result['entityID'];//if category exists just sync them
			if (!empty($entityId) && $entityId > 0){
				if (strpos( $json_result['ResponseStatus']['Message'], 'in the past and was deleted') !== false) {
					$result = array(
							'mvCategoryId' => $json_result['entityID'],
							'errorcode' => 'isdeleted'
					);
					return $result;
				}
				else
				{
					$this->updateCategory($category->getId(), $entityId);
					return 1;
				}
			}
		}
		
		return $errorCode;
	}
	
}
