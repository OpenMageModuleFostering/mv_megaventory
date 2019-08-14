<?php
class Mv_Megaventory_Model_Customer_Observer {
	
	public function onCustomerSave($observer) {
		$event = $observer->getEvent();
		$customer = $event->getCustomer();
		
		$customerHelper = Mage::helper('megaventory/customer');
		
		$result = $customerHelper->addCustomer($customer);
		
		if ($result == 0){
			$logUrl = Mage::helper("adminhtml")->getUrl("megaventory/index/log");
			Mage::getSingleton('core/session')->addError('Customer '.$customer->getId().' did not updated in Megaventory. Please review <a href="'.$logUrl.'" target="_blank">Megaventory Log</a> for details');
		}

		if (is_array($result)){
			//$logUrl = Mage::helper("adminhtml")->getUrl("megaventory/index/log");
			Mage::getSingleton('core/session')->addError('Customer '.$customer->getName().' is flagged as deleted in Megaventory. Presse <a onclick="MegaventoryManager.undeleteEntity(\'' . Mage::helper("adminhtml")->getUrl('megaventory/index/undeleteEntity')  .'\','.$result['mvCustomerId'].',\'supplierclient\')" href="javascript:void(0);">here</a> if you want to automatically undelete it');
		}
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