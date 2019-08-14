<?php

class Mv_Megaventory_Helper_Suppliers extends Mage_Core_Helper_Abstract
{
	public function importSuppliersToMegaventory($attributeId, $megaventoryHelper)
	{
		$attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
		$attributeOptions = $attribute ->getSource()->getAllOptions();
		
		$total = 0;
		$optionsCount = count($attributeOptions);
		foreach ($attributeOptions as $attributeOption){
			try{
				$optionValue = $attributeOption['value'];
				$optionLabel = $attributeOption['label'];
				if (!isset($optionLabel))
					continue;
				
				if ($this->insertSingleSupplier($optionLabel) == 0) //no errors
				{
					$message = $total.'/'.$optionsCount;
					$megaventoryHelper->sendProgress('', $message, '0', 'suppliers');
					$total++;
				}
			}
			catch(Exception $ex){
	
			}
		}
		
		$megaventoryHelper->sendProgress('', $total.'/'.$optionsCount.' were imported! Finished Importing Suppliers to Megaventory..', '0', 'suppliers');
		return $total;
	}
	
	public function insertSingleSupplier($label){
		$helper = Mage::helper('megaventory');
		$megaventoryId = '0';
		
		
		$data = array (
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'mvSupplierClient' => array (
						'SupplierClientID' => 0,
						'SupplierClientType' => '1',
						'SupplierClientName' => $label,
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
				'mvRecordAction' => 'Insert' );
		
		$json_result = $helper->makeJsonRequest($data, 'SupplierClientUpdate',0);
		
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		
		return $errorCode;
		
		}
	}

