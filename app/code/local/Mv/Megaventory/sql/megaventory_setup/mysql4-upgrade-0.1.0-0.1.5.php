<?php
Mage::log('setup');
$installer = $this;
$installer->startSetup();
$installer->run("
CREATE TABLE `{$installer->getTable('megaventory_log')}` (
      `log_id` int(11) NOT NULL auto_increment,
      `code` text,
      `result` text,
      `magento_id` text,
      `details` text,
      `return_entity` text,
      `data` text,
      `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
      PRIMARY KEY  (`log_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8

");
$installer->endSetup();