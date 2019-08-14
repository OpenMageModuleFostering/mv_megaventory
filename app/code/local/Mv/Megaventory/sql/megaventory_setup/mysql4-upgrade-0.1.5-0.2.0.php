<?php
Mage::log('setup');
$installer = $this;
$installer->startSetup();
$installer->run("
CREATE TABLE `{$installer->getTable('megaventory_inventories')}` (
  id int(11) NOT NULL AUTO_INCREMENT,
  shortname text,
  name text,
  address text,
  isdefault int(11) DEFAULT NULL,
  megaventory_id int(11) DEFAULT NULL,
  counts_in_total_stock int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

		
CREATE TABLE `{$installer->getTable('megaventory_stock')}` (
  id int(11) NOT NULL AUTO_INCREMENT,
  product_id int(10) unsigned NOT NULL DEFAULT '0',
  parent_id int(10) unsigned,
  inventory_id int(11) DEFAULT '0',
  stockqty decimal(18,9) DEFAULT '0',
  stockqtyonhold decimal(18,9)  DEFAULT '0',
  stockalarmqty decimal(18,9)  DEFAULT '0',
  stocknonshippedqty decimal(18,9)  DEFAULT '0',
  stocknonreceivedqty decimal(18,9) DEFAULT '0',
  stockwipcomponentqty decimal(18,9) DEFAULT '0',
  stocknonreceivedwoqty decimal(18,9) DEFAULT '0',
  stocknonallocatedwoqty decimal(18,9) DEFAULT '0',
  extra1 text,
  extra2 text,
  extra3 text,
  PRIMARY KEY (id),
  KEY fkey_inventory_idx (inventory_id),
  KEY fkey_product_idx (product_id),
  CONSTRAINT fkey_inventory FOREIGN KEY (inventory_id) REFERENCES `{$installer->getTable('megaventory_inventories')}` (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fkey_product FOREIGN KEY (product_id) REFERENCES `{$installer->getTable('catalog_product_entity')}` (entity_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{$installer->getTable('megaventory_taxes')}` (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  percentage decimal(10,5) DEFAULT '0.00',
  description text,
  megaventory_id int(11) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{$installer->getTable('megaventory_currencies')}` (
  id int(11) NOT NULL AUTO_INCREMENT,
  code varchar(100) NOT NULL,
  description text,
  megaventory_id int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

if(version_compare(Mage::getVersion(), '1.4.0', '<')) {
	$installer->getConnection()->addColumn(
			$installer->getTable('sales_order'), 'mv_inventory_id', 'INT(11) unsigned NOT NULL DEFAULT 0'
	);
	$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
	$setup->addAttribute('order', 'mv_inventory_id', array('type' => 'static', 'visible' => false));
} else {
	$installer->getConnection()->addColumn(
			$installer->getTable('sales_flat_order'), 'mv_inventory_id', 'INT(11) unsigned NOT NULL DEFAULT 0'
	);
	$installer->getConnection()->addColumn(
			$installer->getTable('sales/order_grid'), 'mv_inventory_id', 'INT(11) unsigned NOT NULL DEFAULT 0'
	);
}


$installer->endSetup();