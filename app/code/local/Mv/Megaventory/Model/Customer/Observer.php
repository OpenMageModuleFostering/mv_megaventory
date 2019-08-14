<?php
class Mv_Megaventory_Model_Customer_Observer {
	
	public function onCustomerSave($observer) {
		$event = $observer->getEvent();
		$customer = $event->getCustomer();
		
		$customerHelper = Mage::helper('megaventory/customer');
		
		$customerHelper->addCustomer($customer);
		
	}
	
	public function onCustomerDelete($observer) {
		$event = $observer->getEvent();
		$customer = $event->getCustomer();
	
		$customerHelper = Mage::helper('megaventory/customer');
	
		$customerHelper->deleteCustomer($customer);
	
	}
	
	public function onCustomerAddressSave($observer) {
		$event = $observer->getEvent();
		$customerAddress = $event->getCustomer_address();
	
		$customerHelper = Mage::helper('megaventory/customer');
	
		$customerHelper->addCustomerAddress($customerAddress);
	
	}

}
?>