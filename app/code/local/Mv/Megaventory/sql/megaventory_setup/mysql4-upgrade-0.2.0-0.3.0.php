<?php
Mage::log('setup');
$installer = $this;
$installer->startSetup();
$installer->run("
		delete from `{$installer->getTable('core_config_data')}` where path = 'cataloginventory/item_options/manage_stock';
		insert into `{$installer->getTable('core_config_data')}` (scope, scope_id, path, value) values ('default',0,'cataloginventory/item_options/manage_stock',1);
		delete from `{$installer->getTable('core_config_data')}` where path = 'api/config/wsdl_cache_enabled';
		insert into `{$installer->getTable('core_config_data')}` (scope, scope_id, path, value) values ('default',0,'api/config/wsdl_cache_enabled',1);
		delete from `{$installer->getTable('core_config_data')}` where path = 'api/config/compliance_wsi';
		insert into `{$installer->getTable('core_config_data')}` (scope, scope_id, path, value) values ('default',0,'api/config/compliance_wsi',0);
		");
$installer->endSetup();