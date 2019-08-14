<?php
Mage::log('setup');
$installer = $this;
$installer->startSetup();
$installer->run("
CREATE TABLE `{$installer->getTable('megaventory_progress')}` (
  `id` int(11) NOT NULL,
  `messagedata` varchar(5000) DEFAULT NULL,
  `flag` int(11) NOT NULL DEFAULT '1',
  `type` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{$installer->getTable('megaventory_bom')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `auto_code` varchar(1000) NOT NULL,
  `megaventory_id` int(11) NOT NULL,
  `magento_product_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `megaventory_sku` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_fkey_idx` (`magento_product_id`),
  CONSTRAINT `product_fkey` FOREIGN KEY (`magento_product_id`) REFERENCES `catalog_product_entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
");
$installer->endSetup();