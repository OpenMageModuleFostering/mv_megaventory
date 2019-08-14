<?php

class Mv_Megaventory_Helper_Taxes extends Mage_Core_Helper_Abstract
{
	public function getTaxes()
	{
		return Mage::getModel('megaventory/taxes')->getCollection()->load();
	}
	
	public function synchronizeTaxes($megaventoryHelper = false)
	{
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey')
		);
			
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'TaxGet',0);
		
		$mvTaxes = $json_result['mvTaxes'];
		
		$i = 0;
		
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$tableName = $resource->getTableName('megaventory_taxes');
		$deleteTaxes = 'delete from '.$tableName;
		$write->query($deleteTaxes);
		
		//import taxes from megaventory
		foreach($mvTaxes as $mvTax)
		{
			$tax = $this->checkIfTaxExists($mvTax);
			if ($tax == false)
				$this->insertTax($mvTax);
			else
				$this->updateTax($tax, $mvTax);
			$i++;	
		} 
		
		
		//send extra tax rates to megaventory
		$taxRates = Mage::getModel('tax/calculation_rate')->getCollection(); 
		
		foreach ($taxRates as $taxRate){
			$percentage = $taxRate->getRate();
			if ($this->getTaxByPercentage($percentage)==false)
			{
				$newMvTax = array (
						'TaxID'=> 0,
						'TaxName'=> $taxRate->getCode(),
						'TaxDescription'=>$taxRate->getTax_country_id().' '.$taxRate->getTax_Region_id().' '.$taxRate->getTax_postcode(),
						'TaxValue'=>$percentage	
						);
				$data['mvTax'] = $newMvTax; 
				$data['mvRecordAction'] = 'Insert';
				
				$json_result = $helper->makeJsonRequest($data ,'TaxUpdate',0);
				$errorCode = $json_result['ResponseStatus']['ErrorCode'];
					
				if ($errorCode == 0){
					$newMvTax = $json_result['mvTax'];
					$this->insertTax($newMvTax);
				}
			}
		}
		if ($megaventoryHelper != false){
			$megaventoryHelper->sendProgress(15, '<br>Tax rates synchronized successfully', '0', 'taxes',true);
		}
		return $i;
				
	}
	
	private function insertTax($mvTax){
		$mvID = $mvTax['TaxID'];
		$mvTaxName = $mvTax['TaxName'];
		$mvTaxDescription = $mvTax['TaxDescription'];
		$mvTaxValue = $mvTax['TaxValue'];
		$resource = Mage::getSingleton ( 'core/resource' );
		$write = $resource->getConnection ( 'core/write' );
		$tableName = $resource->getTableName('megaventory_taxes');
		$sql_insert = 'insert into '.$tableName.' (name, description, percentage,megaventory_id) values ("'.$mvTaxName.'","'.$mvTaxDescription.'","'.$mvTaxValue.'","'.$mvID.'")';
		$write->query($sql_insert);
	}
	
	private function updateTax($tax, $mvTax){
		$tax->setData('name',$mvTax['TaxName']);
		$tax->setData('description',$mvTax['TaxDescription']);
		$tax->setData('percentage',$mvTax['TaxValue']);
		$tax->save();
	
	}
	
	
	private function checkIfTaxExists($mvTax){
		
		$tax = Mage::getModel('megaventory/taxes')->load($mvTax['TaxID'], 'megaventory_id');
		$id = $tax->getData('id');
		if (!isset($id))
			return false;
		
		return $tax;
	}
	
	public function getTaxByPercentage($percentage)
	{
		/* $tax = Mage::getModel('megaventory/taxes')
		->getCollection()
		->addFieldToFilter('percentage', $percentage)
		->getFirstItem(); */
		
		$tax = Mage::getModel('megaventory/taxes')
		->getCollection()
		->addFieldToFilter('percentage', array('gt' => $percentage - 0.25))
		->addFieldToFilter('percentage', array('lt' => $percentage + 0.25))
		->getFirstItem();
		
		$id = $tax->getData('id');
		if (!isset($id))
			return false;
		
		return $tax;
		
	}
	
	public function addMagentoTax($percentage){
		$taxName = $percentage;
		$taxRate = Mage::getModel('tax/calculation_rate')->getCollection()
		->addFieldToFilter('rate',array('eq' => $percentage))->getFirstItem();
		
		if (!empty($taxRate) && $taxRate->getId()){
			$taxName = $taxRate->getCode();
		}
		
		$mvTax = array(
					'TaxID'	=> '0',	
					'TaxName' => $taxName,
					'TaxDescription' => '',
					'TaxValue' => $percentage
					);
		$data = array
		(
				'APIKEY' => Mage::getStoreConfig('megaventory/general/apikey'),
				'mvTax' => $mvTax,
				'mvRecordAction' => 'Insert'
		);
			
		$helper = Mage::helper('megaventory');
		$json_result = $helper->makeJsonRequest($data ,'TaxUpdate',0);
		$megaventoryId = $json_result ['mvTax'] ['TaxID'];
		if (isset($megaventoryId))
		{
			$mvTax['TaxID'] = $megaventoryId;
		
			$this->insertTax($mvTax);
			
			return $megaventoryId;
		}
		
		return false;
	}
}
