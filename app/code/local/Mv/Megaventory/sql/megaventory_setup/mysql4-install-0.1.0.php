<?php
Mage::log('setup');
$installer = $this;
$installer->startSetup();
$installer->run("
 
ALTER TABLE `{$installer->getTable('customer_entity')}` ADD `mv_supplierclient_id` VARCHAR( 255 ) ;

ALTER TABLE `{$installer->getTable('catalog_category_entity')}` ADD `mv_productcategory_id` VARCHAR( 255 ) ;

ALTER TABLE `{$installer->getTable('catalog_product_entity')}` ADD `mv_product_id` VARCHAR( 255 ) ;

ALTER TABLE `{$installer->getTable('sales_flat_order')}` ADD `mv_salesorder_id` VARCHAR( 255 ) ;

");
$installer->endSetup();