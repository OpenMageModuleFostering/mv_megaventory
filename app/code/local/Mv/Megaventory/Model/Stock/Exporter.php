<?php
/**
 * this class adds small functions to modify the export data
 *
 */


class Mv_Megaventory_Model_Stock_Exporter extends Mage_Dataflow_Model_Convert_Parser_Abstract
{	

	/**
	 * modifies each data
	 */
	public function unparse()
	{
		$setDefaultCost = $this->getVar('set_default_cost', '1');
		//init
        $batchExport    = $this->getBatchExportModel();
        $batchExportIds = $batchExport
			->setBatchId($this->getBatchModel()->getId())
			->getIdCollection();
		
		$productIds     = $this->getData();
		$product        = Mage::getModel('catalog/product');
		$productHelper  = Mage::helper('catalog/product');
		$productCounter = 0;
		
		//start modifying data
		foreach ($batchExportIds as $batchExportId) {
            $batchExport->load($batchExportId);
            $row = $batchExport->getBatchData();
			
			$product->load($productIds[$productCounter]);
			
			//letrim
			$sku = $row['SKU'];
			if (isset($sku) && strlen($sku) >= 30){
				$batchExport->delete();
				$this->addException('SKU '.$sku.' is over 30 characters long and will be ignored', 'ERROR');
				continue;
			}
			
			$cost = $row['Unit Cost (average cost) (EUR)'];
			if (!isset($cost))
				$row['Unit Cost (average cost) (EUR)'] = $setDefaultCost;
			//end of letrim
			
            $batchExport->setBatchData($row)
				->setStatus(2)
				->save();
            
			$productCounter++;
        }
		
		return $this;
	}
	
	public function parse()
	{
		$this->addException("category parser not implemented, only use 'unparse' to modify export data", Varien_Convert_Exception::WARNING);
		return $this;
	}

}
