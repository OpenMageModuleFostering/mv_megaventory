<?php


class Mv_Megaventory_Model_Import_Entity_Customer_Address extends Mage_ImportExport_Model_Import_Entity_Customer_Address
{

	protected function _importData()
	{
		$result = parent::_importData();
		
		//megaventory hook
		$customerHelper = Mage::helper('megaventory/customer');
		while ($bunch = $this->_dataSourceModel->getNextBunch()) {
			
			foreach ($bunch as $rowNum => $rowData) {
				$customerId = $this->_customer->getCustomerId(
						$rowData[Mage_ImportExport_Model_Import_Entity_Customer::COL_EMAIL],
						$rowData[Mage_ImportExport_Model_Import_Entity_Customer::COL_WEBSITE]
				);
				
				if ($customerId){
					$customer = Mage::getModel('customer/customer')->load($customerId);
					$customerHelper->addCustomer($customer);
				}
			}
		}
	}
	
	

}
