<?php
class Mv_Megaventory_Model_Category_Observer {
	
	public function onCategorySave($observer) {
		
		$event = $observer->getEvent ();
		$category = $event->getCategory ();
		
		$id = $category->getData ( 'mv_productcategory_id' );
		
		
		
		if (isset($id) && $id != NULL) // it is an update
		{
			$action = 'Update';
		}
		else {
			$id = '0';
			$action = 'Insert';
		}
		
		
		if ($category->getIsActive())
		{
		
			$helper = Mage::helper('megaventory');
			
			if (strcmp ( $action, "Insert" ) == 0)
			{
				$name = $helper->createCategoryName($category);
				$descr = $category->getDescription ();
				
				if (isset($descr) && $descr != NULL)
					$description = $descr;
				else
					$description = '';
				
				$data = array (
						'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'), 
						'mvProductCategory' => array (
								'ProductCategoryID' => $id, 
								'ProductCategoryName' => $name, 
								'ProductCategoryDescription' => $description ), 
						'mvRecordAction' => $action );
				
				$json_result = $helper->makeJsonRequest($data, 'ProductCategoryUpdate',$category->getId());
				
				$errorCode = $json_result['ResponseStatus']['ErrorCode'];
				if ($errorCode == '0'){//no errors
					$this->updateCategory($category->getId(), $entityId);
				}
				else
				{
					$entityId = $json_result['entityID'];//if category exists just sync them
					if (!empty($entityId) && $entityId > 0){
						$this->updateCategory($category->getId(), $entityId);
					}
				}
					
			}
			else //we must also update all children
			{
				$this->updateCategoriesRecursively($category);
			}
		}
		else //if user disables a category 
			//we should delete the megaventory category
		{
			//no update just delete the current category not
			//its children
			if (strcmp ( $action, "Update" ) == 0)
			{
				$this->deleteCategory($category);
			}
		}
	
	}
	
	public function onCategoryMove($observer){
		$event = $observer->getEvent();
		$category = $event->getCategory();
		$parent = $event->getParent();
		$categoryId = $event->getData('category_id');
		$prevParentId = $event->getData('prev_parent_id');
		$newParentId = $event->getData('parent_id');
		
		$this->updateCategoriesRecursively($parent);
		
		
	}
	
	public function onCategoryDelete($observer) {
		$event = $observer->getEvent ();
		$category = $event->getCategory ();
		
		$this->deleteCategoriesRecursively($category);
	}
	
	private function deleteCategory($category, $updateMagentoId = true){
	
		$megaventoryCategoryId = $category->getData('mv_productcategory_id');
	
	
		if (isset($megaventoryCategoryId))
		{
			$data = array (
					'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
					'ProductCategoryIDToDelete' => $megaventoryCategoryId,
					'mvCategoryDeleteAction' => 'LeaveProductsOrphan');
	
	
			$helper = Mage::helper('megaventory');
			$json_result = $helper->makeJsonRequest($data, 'ProductCategoryDelete',$category->getEntityId());
			
			$errorCode = $json_result['ResponseStatus']['ErrorCode'];
			if ($errorCode == '0' && $updateMagentoId == true){//no errors
				$resource = Mage::getSingleton ( 'core/resource' );
				$write = $resource->getConnection ( 'core/write' );
				$table = $resource->getTableName ( 'catalog/category' );
				$sql_insert = "update ".$table." set mv_productcategory_id = NULL where entity_id = '".$category->getEntityId()."' ";
				$write->query($sql_insert);
			}
			
		}
	
	}
	
	private function deleteCategoriesRecursively($category){
		$children = $category->getChildrenCategories();
		$hasChildren = $children && $children->count();
		if ( $hasChildren ) {
			foreach($children as $tmpCategory)
			{
				$this->deleteCategoriesRecursively($tmpCategory);
			}
		}
		
		$megaventoryCategoryId = $category->getData('mv_productcategory_id');
		
		
		if (isset($megaventoryCategoryId))
		{
			$data = array (
					'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
					'ProductCategoryIDToDelete' => $megaventoryCategoryId,
					'mvCategoryDeleteAction' => 'LeaveProductsOrphan');
		
		
			$helper = Mage::helper('megaventory');
			$json_result = $helper->makeJsonRequest($data, 'ProductCategoryDelete',$category->getEntityId());
		}
			
		
	}
	
	private function updateCategoriesRecursively($category){
	
		$megaventoryCategoryId = $category->getData('mv_productcategory_id');
		
		if (isset($megaventoryCategoryId))
		{
			$helper = Mage::helper('megaventory');
			$name = $helper->createCategoryName($category);
			$descr = $category->getDescription ();
				
				if (isset($descr) && $descr != NULL)
					$description = $descr;
				else
					$description = '';
				
			$data = array (
					'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
					'mvProductCategory' => array (
							'ProductCategoryID' => $megaventoryCategoryId,
							'ProductCategoryName' => $name,
							'ProductCategoryDescription' => $description ),
					'mvRecordAction' => 'Update' );
				
			$json_result = $helper->makeJsonRequest($data, 'ProductCategoryUpdate',$category->getEntityId());
		
		}
		
		$children = $this->getChildrenCategories($category);
		$hasChildren = $children && $children->count();
		if ( $hasChildren ) {
			foreach($children as $tmpCategory)
			{
				$this->updateCategoriesRecursively($tmpCategory);
			}
		}
	
	
	}
	
	public function getChildrenCategories($category)
	{
		$collection = $category->getCollection();
		/* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */
		$collection->addAttributeToSelect('url_key')
		->addAttributeToSelect('name')
		->addAttributeToSelect('all_children')
		->addAttributeToSelect('is_anchor')
		->addAttributeToSelect('description')
		->addAttributeToFilter('is_active', 1)
		->addIdFilter($category->getChildren())
		->setOrder('position', 'ASC')
		->joinUrlRewrite()
		->load();
		return $collection;
	}
	
	private function updateCategory($categoryId, $mvCategoryId)
	{
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$table = $resource->getTableName ( 'catalog/category' );
		$sql_insert = "update " . $table . " set mv_productcategory_id = '" . $mvCategoryId . "' where entity_id = '" . $categoryId . "' ";
		$write->query ( $sql_insert );
	}
}
?>