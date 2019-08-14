<?php
class Mv_Megaventory_Model_Sales_Observer {
	
	public function onQuoteSubmitSuccess($observer) {
		$orderSynchronization = Mage::getStoreConfig('megaventory/general/ordersynchronization');
		if (empty($orderSynchronization) || $orderSynchronization === '0')
			return;
		
		$event = $observer->getEvent ();
		$order = $event->getOrder();
		$quote = $event->getQuote();
		
		$orderHelper = Mage::helper('megaventory/order');
		$orderHelper->addOrder($order,$quote);
	}
	
	public function onOrderSave($observer) {
		/* $orderSynchronization = Mage::getStoreConfig('megaventory/general/ordersynchronization');
		if (empty($orderSynchronization) || $orderSynchronization === '0')
			return; */
		
		$event = $observer->getEvent ();
		$order = $event->getOrder();
		
		$orderHelper = Mage::helper('megaventory/order');
		
		//under certain conditions
		//this code is called twice and therefore we get an 'extra' api call
		//to cancel an order that is already cancelled in megaventory
		//the following commented out code remedies this BUT has problems
		//when order is cancelled programmatically for instance when cancelling
		//orders via a payment gateway system (i.e Moneybookers) 
		if ($order->getState() == 'canceled')
		{
			
			/* if(version_compare(Mage::getVersion(), '1.5.0', '>=')) {
				$statusHistory = $order->getAllStatusHistory();
				$lastItem = count($statusHistory)-2;
				
				$lastStatus = $statusHistory[$lastItem];
				
				$statusCollection = Mage::getResourceModel('sales/order_status_collection')
				->joinStates();
				$state = '';
				foreach ($statusCollection as $status){
					if ($status->getStatus() == $lastStatus->getStatus())
					{
						$state = $status->getState();
						break;
					}
				}
				
				if ($state !== Mage_Sales_Model_Order::STATE_CANCELED)
					$orderHelper->cancelOrder($order);
			}
			else
			{
				Mage::log('order canceled',null,'megaventory.log',true);
				$statusHistory = $order->getAllStatusHistory();
				$lastItem = count($statusHistory)-2;
				
				Mage::log('status history = '.count($statusHistory),null,'megaventory.log');
				Mage::log('last item index = '.$lastItem,null,'megaventory.log');
				$lastStatus = $statusHistory[$lastItem];
				Mage::log('last status = '.$lastStatus->getStatus(),null,'megaventory.log');
				
				if ($lastStatus->getStatus() !== Mage_Sales_Model_Order::STATE_CANCELED)
					$orderHelper->cancelOrder($order);
			} */
			
			
			$orderHelper->cancelOrder($order);
		}
	}
	
	public function onOrderAddComment($observer)
	{
		$orderSynchronization = Mage::getStoreConfig('megaventory/general/ordersynchronization');
		if (empty($orderSynchronization) || $orderSynchronization === '0')
			return;
		
		$statusHistory = $observer->getStatus_history();
		
		$comment = $statusHistory->getComment();
	
		if (!empty($comment)){
			$registryComment = Mage::registry('mvcustomercomment');
			Mage::unregister('mvcustomercomment');
			$registryComment .= $comment;
			Mage::register('mvcustomercomment', $registryComment);
		}
	
	}
}
?>