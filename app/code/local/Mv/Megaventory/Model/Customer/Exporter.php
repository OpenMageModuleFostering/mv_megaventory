<?php
/**
 * this class adds small functions to modify the export data
 *
 */


class Mv_Megaventory_Model_Customer_Exporter extends Mage_Dataflow_Model_Convert_Parser_Abstract
{	

	/**
	 * modifies each data
	 */
	public function unparse()
	{
		//init
        $batchExport    = $this->getBatchExportModel();
        $batchExportIds = $batchExport
			->setBatchId($this->getBatchModel()->getId())
			->getIdCollection();
        
        $customerIds = $this->getData();
        $customer = Mage::getModel('customer/customer');
       	$billingaddress = Mage::getModel('customer/address');
       	$shippingaddress = Mage::getModel('customer/address');
        $counter = 0;
        $mvTaxId = Mage::getStoreConfig('megaventory/general/defaulttaxid');
        
		
		//start modifying data
		foreach ($batchExportIds as $batchExportId) {
            $batchExport->load($batchExportId);
            $row = $batchExport->getBatchData();
			
			//letrim
            $customer->load($customerIds[$counter]);
            $row['Client Name'] = $customer->getData('lastname').' '.$customer->getData('firstname');
            
            $billingaddress->load($customer->default_billing);
            $billing = $billingaddress ->getData();
            
            $billing['telephone'];
            $country = Mage::app()->getLocale()->getCountryTranslation($billing['country_id']);
            $row['Billing Address'] =  $billing['street'].','.$billing['city'].','.$billing['postcode'].','.$country;
            $row['Billing Address'] =  preg_replace( "/\r|\n/", "", $row['Billing Address'] );
            
            $shippingaddress->load($customer->default_shipping);
            $shipping = $shippingaddress->getData();
            $country = Mage::app()->getLocale()->getCountryTranslation($shipping['country_id']);
            $row['Shipping Address'] =  $shipping['street'].','.$shipping['city'].','.$shipping['postcode'].','.$country;
            $row['Shipping Address'] = preg_replace( "/\r|\n/", "", $row['Shipping Address'] );
            $row['Shipping Address 2'] =  '';
            $row['Client Comments'] = $customer->getId();
            
            $row['Phone'] = $shipping['telephone'];
            $row['Phone 2'] = $shipping['fax'];
            
            
            $row['Tax ID'] = $mvTaxId;
            $row['Also my Supplier?'] = '';
            
            
			//end of letrim
			
            $batchExport->setBatchData($row)
				->setStatus(2)
				->save();
            
            $counter++;
        }
		
		return $this;
	}
	
	public function parse()
	{
		$this->addException("category parser not implemented, only use 'unparse' to modify export data", Varien_Convert_Exception::WARNING);
		return $this;
	}

}
