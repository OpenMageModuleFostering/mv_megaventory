<?php
class Mv_Megaventory_Model_Product_Observer {
	
	public function onProductSave($observer) {
		$event = $observer->getEvent ();
		$product = $event->getProduct ();
		
		$productHelper = Mage::helper('megaventory/product');
		
		$sku = $product->getSku();
		$megaventoryId = $product->getData('mv_product_id');
		$startsWith = $this->startsWith($sku, 'bom_'); 
		if ($startsWith && empty($megaventoryId)) //it is an insert of a bom and we should ignore
			return;
		
		$productHelper->addProduct($product);
		
	}
	
	public function onProductDelete($observer) {
		$event = $observer->getEvent ();
		$product = $event->getProduct ();
		
		$productHelper = Mage::helper('megaventory/product');
		$productHelper->deleteProduct($product);
		
				
	}
	
	public function onProductMainTab($observer){
		$event = $observer->getEvent ();
		$block = $event->getBlock();
		$transport = $event->getTransport();
		
		if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Attributes){
			$i=0;
			$group = $block->getGroup();
			
			if (!empty($group) && $group->getAttribute_group_name() == 'General')
			{
				/* $megaventoryIntegation = Mage::getStoreConfig('megaventory/general/enabled');
				
				if ($megaventoryIntegation != '1')
					return; */
				
				if (!Mv_Megaventory_Helper_Common::isMegaventoryEnabled())
					return;
				
				
				$product = Mage::registry('product');
				if (!empty($product)){
					$megaventoryId = $product->getData('mv_product_id');
					if (!empty($megaventoryId)){
						$productHelper = Mage::helper('megaventory/product');
						$mvProduct = $productHelper->getProduct($megaventoryId);
						if ($mvProduct!==-1)
						{
							$html = $transport->getHtml();
							$html .= $this->getProductMegaventoryHtml($mvProduct,$product);
							$transport->setHtml($html);
						}
					}
				}
			}
		}
	}
	
	private function getProductMegaventoryHtml($mvProduct, $product){
		
		$adminSession = Mage::getSingleton('admin/session');
		$subDomain = $adminSession->getData('mv_DomainName');
		$domain = '.megaventory.com';
		
		$links = 'View all ';
		//$links .=  '<a href="https://'.$subDomain.$domain.'/inventory/Maintainance/ProductEdit.aspx?id='.$mvProduct['ProductID'].'" target="_blank">Product Page</a><br/>';
		$links .= '<a href="https://'.$subDomain.$domain.'/inventory/Invoices/InvoiceList.aspx?sText='.$mvProduct['ProductSKU'].'" target="_blank">Transactions</a>, ';
		$links .= '<a href="https://'.$subDomain.$domain.'/inventory/Ordering/PurchaseOrdersList.aspx?sText='.$mvProduct['ProductSKU'].'" target="_blank">Purchase Orders</a>, ';
		$links .= '<a href="https://'.$subDomain.$domain.'/inventory/Ordering/SalesOrdersList.aspx?sText='.$mvProduct['ProductSKU'].'" target="_blank">Sales Orders</a>, ';
		$links .= '<a href="https://'.$subDomain.$domain.'/inventory/Ordering/SalesQuotesList.aspx?sText='.$mvProduct['ProductSKU'].'" target="_blank">Sales Quotes</a> ';
		$links .= 'with this SKU or ';
		$links .= '<a href="https://'.$subDomain.$domain.'/inventory/Stock/WarehouseStock.aspx?sText='.$mvProduct['ProductSKU'].'" target="_blank">Set Alert Levels for this SKU</a>';
		
		$noticeSKU = '';
		if ($mvProduct['ProductSKU'] != $product->getSku())
		{
			$messageImg = Mage::getDesign()->getSkinUrl('images/megaventory/message.png');
			$noticeSKU = '<img src="'.$messageImg.'">';
			$noticeSKU .= '<br/><span style="color:red;">Attention!<br/>Products are synchronized but SKUs are not the same.<br/>You should modify Magento Product SKU to exactly match Megaventory Product SKU</span>';
		}
		
		
		$fragment = '<div class="entry-edit">'.
					'<div class="entry-edit-head">'.
					'<h4 class="icon-head head-edit-form fieldset-legend">Megaventory</h4>'.
		    		'</div>'.
					'<div class="fieldset fieldset-wide">'.
					'<div class="hor-scroll">'.
					'<table cellspacing="0" class="form-list">'.
					'<tbody>'.
					'<tr>'.
					'<td class="label">Product SKU</td>'.
					'<td class="value">'.$mvProduct['ProductSKU'].$noticeSKU.'</td>'.
					'</tr>'.
					'<tr>'.
					'<td class="label">&nbsp;</td>'.
					'<td class="value">'.$links.'</td>'.
					'</tr>'.
					'</tbody>'.
					'</table>'.
					'</div>'.
					'</div>'.
					'</div>';
		
		return $fragment;
	}
	
	private function startsWith($haystack, $needle)
	{
		return $needle === "" || strpos($haystack, $needle) === 0;
	}

}
?>