<?php


class Mv_Megaventory_Model_Mysql4_Megaventorylog extends Mage_Core_Model_Mysql4_Abstract{
	
    protected function _construct()
    {
        $this->_init('megaventory/megaventorylog', 'log_id');
    }   
}