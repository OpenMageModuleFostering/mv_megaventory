<?php

class Mv_Megaventory_Helper_Order extends Mage_Core_Helper_Abstract
{
	/**
	 * Price conversion constant for positive
	 */
	const PRICE_CONVERSION_PLUS = 1;
	
	/**
	 * Price conversion constat for negative
	 */
	const PRICE_CONVERSION_MINUS = 2;
	
	public function addOrder($order,$quote)
	{
		$increment_id = $order->getIncrementId();
		$currency = $order->getOrderCurrency();
		$orderCurrencyCode = $currency->getCurrency_code();
		$baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
		
		$tmpCurrency = Mage::getModel('megaventory/currencies')->load($orderCurrencyCode,'code');
		if ($tmpCurrency->getId() == false){ //currency does not exist in local table
			Mage::helper('megaventory/currencies')->addSingleCurrency($orderCurrencyCode);
		}
		
		
		$billingAddress = $order->getBillingAddress();
		$billingAddressString = $billingAddress->format('oneline');
		$billingEmail = $billingAddress->getEmail();
		$billingTelephone = $billingAddress->getTelephone();
		$billingAddressString = trim($billingAddressString);
		if (!empty($billingTelephone))
			$billingAddressString .= ','.$billingTelephone;
		
		$shippingAddress = $order->getShippingAddress();
		$shippingAddressString = $shippingAddress->format('oneline');
		$shippingEmail = $shippingAddress->getEmail();
		$shippingTelephone = $shippingAddress->getTelephone();
		$shippingAddressString = trim($shippingAddressString);
		if (!empty($shippingTelephone))
			$shippingAddressString .= ','.$shippingTelephone;
		
				
		$orderDate = $order->getUpdated_at(); 
		//$customer = $order->getCustomer();
		$customer = Mage::getModel('customer/customer')->load($order->getCustomer_id());
		
		
		$megaVentoryCustomerId = 0;
		
		//if customer is guest
		if ($order->getCustomer_group_id() == '0')
		{
			$defaultGuestId = Mage::getStoreConfig('megaventory/general/defaultguestid');
			if (isset($defaultGuestId))
				$megaVentoryCustomerId = $defaultGuestId;
		}
		else
		{
			if (isset($customer)){
				$megaVentoryCustomerId = $customer->getData('mv_supplierclient_id');
				
				if(isset($megaVentoryCustomerId)==false || empty($megaVentoryCustomerId)) //customer not exists
				{
					$customerHelper = Mage::helper('megaventory/customer');
					$megaVentoryCustomerId = $customerHelper->addCustomer($customer);
				}
			}
		} 
		
		$history = $order->getAllStatusHistory();
		$comments = '';
		$customerComment = Mage::registry('mvcustomercomment');
		if (!empty($customerComment)){
			$comments .= $customerComment.',';
			Mage::unregister('mvcustomercomment');
		}
		
		Mage::log('customer comments = '.$comments,null,'megaventory.log',true);
		$items = $quote->getAllItems();
		$salesOrderDetails = array();
		
		$taxesHelper = Mage::helper('megaventory/taxes');
		
		
		$inventory = Mage::getModel('megaventory/inventories')->loadDefault();
		
		
		foreach ($items as $productItem) {
			$product = $productItem->getProduct();
			$product = Mage::getModel("catalog/product")->load($product->getId());
			Mage::log('product model class = '.get_class($product),null,'megaventory.log');
			
			$productType = $product->getType_id();
			if ($productType == 'bundle'){
				$productHelper = Mage::helper('megaventory/product');
				$options = $productHelper->getBundleOptions($productItem);
				
				//keep bundle products to avoid duplicates
				$bundles = array_fill_keys(array_keys($options),'1');
				$bundleCode = '';
				foreach ($options as $key => $value){
					$bundleCode .= $value['qty'].'x'.$key.'_';
				}
				if (strlen($bundleCode) > 1)
					$bundleCode = substr_replace($bundleCode ,"",-1);
				
				$bundleMegaventory = $productHelper->bundleProductExists($bundleCode);
				
				$bundleSKU = '-1';
				
				//add bundle product if not exists
				if (!$bundleMegaventory->hasId()){
					Mage::log('add bundle product = '.$bundleCode,null,'megaventory.log',true);
					$bundleSKU = $productHelper->addBundleProduct($product,$bundleCode,$options);
				}
				else{
					Mage::log('bundle product exists = '.$bundleCode,null,'megaventory.log',true);
					$bundleSKU = $bundleMegaventory->getMegaventory_sku();
				}
				
				Mage::log('bundle sku = '.$bundleSKU,null,'megaventory.log',true);
				
				$taxPercent = $productItem->getTax_percent();
				if ($taxPercent == 0 && $productItem->getTax_amount()>0){
					$taxPercent = round($productItem->getTax_amount()/$productItem->getRow_total()*100,2);
				}
				Mage::log('item tax percent = '.$taxPercent,null,'megaventory.log',true);
				$tax = $taxesHelper->getTaxByPercentage($taxPercent);
				
				if ($tax != false){
					$salesOrderRowTaxID = $tax->getMegaventory_id();
				}
				else {
					if ($taxPercent>0){
						$megaventoryTaxId = $taxesHelper->addMagentoTax($taxPercent);
						if ($megaventoryTaxId != false)
							$salesOrderRowTaxID = $megaventoryTaxId;
					}
					else
						$salesOrderRowTaxID = '0';
				}
				
				//we need to set is_salable to option products
				//because when we resynchronize an order we don't have
				//a website id and products appear as no salable
				if ($product->hasCustomOptions()) {
					$customOption = $product->getCustomOption('bundle_option_ids');
					$customOption = $product->getCustomOption('bundle_selection_ids');
					$selectionIds = unserialize($customOption->getValue());
					$selections = $product->getTypeInstance(true)->getSelectionsByIds($selectionIds, $product);
					foreach ($selections->getItems() as $selection) {
						$selection->setIsSalable(true);
					}
				}
				
				$finalPriceNoTax = $this->getPrice($product, $product->getFinalPrice($product->getQty()), $taxPercent);
				//always insert orders in base currency 
				//$finalPriceNoTax = round(Mage::helper('directory')->currencyConvert($finalPriceNoTax, $baseCurrencyCode, $orderCurrencyCode),2);
				
				Mage::log('final price no tax = '.$finalPriceNoTax,null, 'megaventory.log',true);
				
				//add order item
				$salesOrderItem = array(
						'SalesOrderRowQuantity' => $productItem->getQty(),
						'SalesOrderRowUnitPriceWithoutTaxOrDiscount' => $finalPriceNoTax,
						'SalesOrderRowTaxID' => $salesOrderRowTaxID,
						'SalesOrderRowProductSKU' => $bundleSKU
				);
				$salesOrderDetails[] = $salesOrderItem;
				
				
				//add work order 
				if ($bundleSKU != -1){
					$woComments = 'product:'.$product->getName();
					$woComments .= ',order:'.$increment_id;
					$this->addWorkOrder($bundleSKU,$productItem->getQty(),$inventory->getData('megaventory_id'),$woComments, $product->getId());
				}
				//end of work order
			}
			else if ($productType == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE || $productType == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL)
			{
				//if product is checked through bundle then do nothing more
				if (!empty($bundles)){
					if (array_key_exists($product->getId(),$bundles)){
						//though unset it not to ignore if purchased individually
						unset($bundles[$product->getId()]);
						continue;
					}
				}
				
				$id = $product->getData('mv_product_id');
				Mage::log('megaventory id = '.$id,null,'megaventory.log');
				if(!isset($id)){ //product does not exist
					
					$productHelper = Mage::helper('megaventory/product');
					$megaventoryProductId = $productHelper->addProduct($product);
				}
				else
					$megaventoryProductId = $id;

				
				$parentItem = $productItem->getParentItem();
				if (isset($parentItem)){
					$productItem = $parentItem;
					//$product = $productItem->getProduct();
				}
				
				$finalPriceNoTax = $this->getPrice($productItem->getProduct(), $productItem->getProduct()->getFinalPrice($productItem->getQty()));
				//allways insert orders in base currency
				//$finalPriceNoTax = round(Mage::helper('directory')->currencyConvert($finalPriceNoTax, $baseCurrencyCode, $orderCurrencyCode),2);
				
				
				$taxPercent = $productItem->getTax_percent();
				$tax = $taxesHelper->getTaxByPercentage($taxPercent);
				Mage::log('tax percent = '.$taxPercent,null, 'megaventory.log',true);
				
				if ($tax != false){
					$salesOrderRowTaxID = $tax->getMegaventory_id(); 
				}
				else {
					if ($taxPercent>0){
						$megaventoryTaxId = $taxesHelper->addMagentoTax($taxPercent);
						if ($megaventoryTaxId != false)
							$salesOrderRowTaxID = $megaventoryTaxId;
					}
					else
						$salesOrderRowTaxID = '0';
				}
				
				
				
				Mage::log('final price no tax = '.$finalPriceNoTax,null, 'megaventory.log',true);
				
				if ($megaventoryProductId != 0)
				{
					$salesOrderItem = array(
											'SalesOrderRowQuantity' => $productItem->getQty(), 
											'SalesOrderRowUnitPriceWithoutTaxOrDiscount' => $finalPriceNoTax, 
											'SalesOrderRowTaxID' => $salesOrderRowTaxID,
											'SalesOrderRowProductSKU' => $product->getSku()
											);
					$salesOrderDetails[] = $salesOrderItem;
				}
				
				//check if product is a bom and add a wo 
				if (strpos($product->getSku(),'bom_')!== false)
				{
					//check local bom table
					$bomProduct = Mage::getModel('megaventory/bom')->loadByBOMSku($product->getSku());
					if ($bomProduct->hasId()){
						$woComments = 'product:'.$product->getName();
						$woComments .= ',order:'.$increment_id;
						$this->addWorkOrder($product->getSku(), $productItem->getQty(), $inventory->getData('megaventory_id'), $woComments, $product->getId());
					}
				}
				//end of bom check
			}
		}
		
		//add shipping as product
		
		//get base prices
		/* $shippingNoTax = $order->getShipping_amount();
		$shippingWithTax = $order->getShipping_incl_tax();
		$shippingTax = $order->getShipping_tax_amount(); */
		$shippingNoTax = $order->getBase_shipping_amount();
		$shippingWithTax = $order->getBase_shipping_incl_tax();
		$shippingTax = $order->getBase_shipping_tax_amount();
		
		
		$shippingProductSKU = Mage::getStoreConfig('megaventory/general/shippingproductsku');
		$shippingOrderItem = array(
				'SalesOrderRowQuantity' => '1',
				'SalesOrderRowUnitPriceWithoutTaxOrDiscount' => $shippingNoTax,
				'SalesOrderRowProductSKU' => $shippingProductSKU
		);
		
		Mage::log('shipping no tax = '.$shippingNoTax,null, 'megaventory.log',true);
		Mage::log('shipping tax  = '.$shippingTax,null, 'megaventory.log',true);
		
		$shippingTaxCalculator = Mage::getModel('tax/calculation');
		$currentStore = Mage::app()->getStore();
		
		$shippingRequest  = $shippingTaxCalculator->getRateRequest(
				$shippingAddress,
				$billingAddress,
				$customer->getTax_class_id(),
				$currentStore
		);
		$shippingClassId   = Mage::helper('tax')->getShippingTaxClass($order->getStore_id());
		$shippingRequest->setProductClassId($shippingClassId);

		$shippingTaxPercentage = $shippingTaxCalculator->getRate($shippingRequest);
		
		Mage::log('shipping tax percentage = '.$shippingTaxPercentage,null, 'megaventory.log',true);
		//add shipping tax only if there is one
		if ($shippingTaxPercentage > 0){
			$shippingTaxEntity = $taxesHelper->getTaxByPercentage($shippingTaxPercentage);
			
			if ($shippingTaxEntity != false){
				$shippingTaxID = $shippingTaxEntity->getMegaventory_id();
			}
			else {
				$megaventoryTaxId = $taxesHelper->addMagentoTax($shippingTaxPercentage);
				if ($megaventoryTaxId != false)
					$shippingTaxID = $megaventoryTaxId;
			}
			
			Mage::log('shipping tax id = '.$shippingTaxID,null, 'megaventory.log',true);
			$shippingOrderItem['SalesOrderRowTaxID'] = $shippingTaxID;
		}
		
		if ($shippingNoTax > 0)
			$salesOrderDetails[] = $shippingOrderItem;
		//end of shipping
		
		//discount handling
		$totalDiscount = 0;
		//discount amount is negative number
		$discount = $order->getDiscount_amount();
		if (!empty($discount) && $discount != 0){
			$totalDiscount -= abs($discount);
		}
		$giftDiscount = $order->getData('gift_voucher_discount');
		if (!empty($giftDiscount) && $giftDiscount != 0){
			$totalDiscount -= abs($giftDiscount);
		}
		
		$discountProductSKU = Mage::getStoreConfig('megaventory/general/discountproductsku');
		if ($totalDiscount != 0)
		{
			$discountOrderItem = array(
					'SalesOrderRowQuantity' => '1',
					'SalesOrderRowUnitPriceWithoutTaxOrDiscount' => $totalDiscount,
					'SalesOrderRowProductSKU' => $discountProductSKU
			);
			$salesOrderDetails[] = $discountOrderItem;
		}
		//end of discount
		
		$subTotal = $order->getSubtotal();
		$taxAmount = $order->getTax_amount();
		$grandTotal = $order->getGrand_total();
		
		$totalQty = $order->getTotal_qty_ordered();
		$totalItemCount = $order->getTotal_item_count();
		$status = $order->getStatus();
		$shippingDescription = $order->getShipping_description();
		$shippingMethod = $order->getShipping_method();
		$paymentMethodTitle = $order->getPayment()->getMethodInstance()->getTitle();
		
		/* if (!empty($shippingTelephone))
			$comments .= 'ship_tel:'.$shippingTelephone;
		if (!empty($billingTelephone))
			$comments .= 'bill tel:'.$billingTelephone; */
		
		$storeName = $order->getStore_name();
		$comments .= 'ship:'.$shippingDescription.',pay:'.$paymentMethodTitle;
		
		$tags = '';
		
		
		/* if (!empty($shippingEmail))
			$tags .= ',shipping email:'.$shippingEmail;
		
		if (!empty($billingEmail))
			$tags .= ',billing email:'.$billingEmail;
		else
			$tags .= ',email:'.$order->getCustomerEmail();
		
		$tags .= 'store:'.$storeName;
		
		$tags = trim($tags); */
		
		
		
		$data = array (
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'), 
				'mvSalesOrder' => 
								array (
										'SalesOrderNo' => $increment_id, 
										'SalesOrderReferenceNo' => $increment_id,
										'SalesOrderReferenceApplication' => 'MagentoCommunity',
										//always insert orders in base currency
										'SalesOrderCurrencyCode' => $baseCurrencyCode,
										//'SalesOrderCurrencyCode' => $orderCurrencyCode, 
										'SalesOrderClientID' => $megaVentoryCustomerId, 
										'SalesOrderBillingAddress' => $billingAddressString, 
										'SalesOrderShippingAddress' => $shippingAddressString, 
										'SalesOrderContactPerson' => $billingAddress->getLastname().' '.$billingAddress->getFirstname(), 
										'SalesOrderInventoryLocationID' => $inventory->getData('megaventory_id'),
										'SalesOrderComments' => $comments, 
										'SalesOrderTags' => $tags, 
										'SalesOrderAmountShipping' => $shippingWithTax,
										'SalesOrderDetails' => $salesOrderDetails,
										'SalesOrderStatus' => 'Verified' 
									), 
				'mvRecordAction' => "Insert" ); 
		
		$helper = Mage::helper('megaventory');
		$orderAdded = Mage::getModel('sales/order')->loadByIncrementId($increment_id);
		$json_result = $helper->makeJsonRequest($data ,'SalesOrderUpdate',$orderAdded->getId());
		
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode == '0'){//no errors
			$orderAdded->setData('mv_salesorder_id',$json_result['mvSalesOrder']['SalesOrderNo']);
			$orderAdded->setData('mv_inventory_id',$inventory->getData('id'));
			$orderAdded->save();
			
		} 
	}	
	
	public function cancelOrder($order)
	{
		$mvOrderId = $order->getData('mv_salesorder_id');
		if (!empty($mvOrderId)){
			$data = array (
					'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
					'mvSalesOrderNoToCancel' => $mvOrderId
					);
			
			$helper = Mage::helper('megaventory');
			$json_result = $helper->makeJsonRequest($data ,'SalesOrderCancel',$order->getId());
		}
	}
	
	public function addWorkOrder($sku, $quantity, $mvInventoryId, $comments, $magentoId){
		$data = array (
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'), 
				'mvWorkOrder' => 
								array (
										'WorkOrderId' => 0,
										'WorkOrderNo' => 0, 
										'WorkOrderFinishedGoodSKU' => $sku,
										'WorkOrderPriority' => 50,
										'WorkOrderInventoryLocationID' => $mvInventoryId,
										'WorkOrderComments' => $comments,
										'WorkOrderTags' => '',
										'WorkOrderOrderedQuantity' => $quantity,
										'WorkOrderStatus' => 'Pending'
									), 
				'mvRecordAction' => "Insert" ); 
		
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'WorkOrderUpdate',$magentoId);
	}
	
	public function getPrice($product, $price, $taxPercent=null, $shippingAddress = null, $billingAddress = null,
        $ctc = null, $store = null, $priceIncludesTax = null
    ) {
        if (!$price) {
            return $price;
        }
        $store = Mage::app()->getStore($store);
        if (!$this->needPriceConversion($store)) {
            return $store->roundPrice($price);
        } 
        if (is_null($priceIncludesTax)) {
            $priceIncludesTax = $this->priceIncludesTax($store);
        }

        if ($taxPercent!=null)
        	$percent = $taxPercent;
        else
        	$percent = $product->getTaxPercent();
        
        $includingPercent = null;

        $taxClassId = $product->getTaxClassId();
		Mage::log('tax class id = '.$taxClassId,null, 'megaventory.log',true);
        if (is_null($percent)) {
            if ($taxClassId) {
                $request = Mage::getSingleton('tax/calculation')
                    ->getRateRequest($shippingAddress, $billingAddress, $ctc, $store);
                $percent = Mage::getSingleton('tax/calculation')
                    ->getRate($request->setProductClassId($taxClassId));
            }
        }
        if (!empty($taxClassId) && $taxClassId>0){
	        if ($priceIncludesTax) {
	            $request = Mage::getSingleton('tax/calculation')->getRateRequest(false, false, false, $store);
	            $includingPercent = Mage::getSingleton('tax/calculation')
	                ->getRate($request->setProductClassId($taxClassId));
	        }
        }
        else
        {
        	if ($percent && $priceIncludesTax)
        		$includingPercent = $percent;
        }
        
        Mage::log('including percent  = '.$includingPercent,null, 'megaventory.log',true);
        if ($percent === false || is_null($percent)) {
            if ($priceIncludesTax && !$includingPercent) {
                return $price;
            }
        }

        $product->setTaxPercent($percent);

        
		if ($includingPercent != $percent) {
			$price = $this->_calculatePrice ( $price, $includingPercent, false );
		} else {
			$price = $this->_calculatePrice ( $price, $includingPercent, false, true );
		}
			
        return $store->roundPrice($price);
    }
    
    protected function _calculatePrice($price, $percent, $type, $roundTaxFirst = false)
    {
    	$calculator = Mage::getSingleton('tax/calculation');
    	if ($type) {
    		$taxAmount = $calculator->calcTaxAmount($price, $percent, false, $roundTaxFirst);
    		return $price + $taxAmount;
    	} else {
    		$taxAmount = $calculator->calcTaxAmount($price, $percent, true, $roundTaxFirst);
    		return $price - $taxAmount;
    	}
    }
    
    public function needPriceConversion($store = null)
    {
    	$res = false;
    	$taxConfig = Mage::getSingleton('tax/config');
    	$priceDisplayType = $taxConfig->getPriceDisplayType($store);
    	if ($this->priceIncludesTax($store)) {
    		switch ($priceDisplayType) {
    			case Mage_Tax_Model_Config::DISPLAY_TYPE_EXCLUDING_TAX:
    			case Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH:
    				return self::PRICE_CONVERSION_MINUS;
    			case Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX:
    				$res = true;
    		}
    	} else {
    		switch ($priceDisplayType) {
    			case Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX:
    			case Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH:
    				return self::PRICE_CONVERSION_PLUS;
    			case Mage_Tax_Model_Config::DISPLAY_TYPE_EXCLUDING_TAX:
    				$res = false;
    		}
    	}
    
    	if ($res === false) {
    		$res = $taxConfig->displayCartPricesBoth();
    	}
    	return $res;
    }
    
    public function priceIncludesTax($store = null)
    {
    	$taxConfig = Mage::getSingleton('tax/config');
    	return $taxConfig->priceIncludesTax($store) || $taxConfig->getNeedUseShippingExcludeTax();
    }
    
    public function getCalculatedTaxes($source)
    {
    	
    		$current = $source;
    
    	$taxClassAmount = array();
    	if ($current && $source) {
    			// regenerate tax subtotals
    			foreach ($current->getItemsCollection() as $item) {
    				$taxCollection = Mage::getResourceModel('tax/sales_order_tax_item')
    				->getTaxItemsByItemId(
    						$item->getOrderItemId() ? $item->getOrderItemId() : $item->getItemId()
    				);
    
    				$shippingAmount = $current->getShippingAmount();
    				$baseShippingAmount = $current->getBaseShippingAmount();
    				$shippingTaxAmount = $current->getShippingTaxAmount();
    
    				foreach ($taxCollection as $tax) {
    					$taxClassId = $tax['tax_id'];
    					$percent    = $tax['tax_percent'];
    
    					$price     = $item->getRowTotal();
    					$basePrice = $item->getBaseRowTotal();
    					/* if ($this->applyTaxAfterDiscount($item->getStoreId())) {
    						$price     = $price - $item->getDiscountAmount() + $item->getHiddenTaxAmount();
    						$basePrice = $basePrice - $item->getBaseDiscountAmount() + $item->getBaseHiddenTaxAmount();
    					} */
    					$tax_amount      = $price * $percent / 100;
    					$base_tax_amount = $basePrice * $percent / 100;
    
    					if ($shippingTaxAmount) {
    						$tax_amount = $tax_amount + $shippingAmount * $percent / 100;
    						$base_tax_amount = $base_tax_amount + $baseShippingAmount * $percent /100;
    					}
    
    					if (isset($taxClassAmount[$taxClassId])) {
    						$taxClassAmount[$taxClassId]['tax_amount']      += $tax_amount;
    						$taxClassAmount[$taxClassId]['base_tax_amount'] += $base_tax_amount;
    					} else {
    						$taxClassAmount[$taxClassId]['tax_amount']      = $tax_amount;
    						$taxClassAmount[$taxClassId]['base_tax_amount'] = $base_tax_amount;
    						$taxClassAmount[$taxClassId]['title']           = $tax['title'];
    						$taxClassAmount[$taxClassId]['percent']         = $tax['percent'];
    					}
    				}
    			}
    
    		foreach ($taxClassAmount as $key => $tax) {
    			if ($tax['tax_amount'] == 0 && $tax['base_tax_amount'] == 0) {
    				unset($taxClassAmount[$key]);
    			}
    		}
    
    		$taxClassAmount = array_values($taxClassAmount);
    	}
    
    	return $taxClassAmount;
    }
}
