<?php

	/* 
	Collect Magento_version,PHP_version,Expertrec_version, Domain and Subdomain,
	Site_info from core_config_data and user_info from admin_user, 
	and send to expertrec/magento-install api to know Upgrade to new version.
	Update feed push to 0, so that user push feed. 
	*/

	$installer = $this;

	$mage_ver = Mage::getVersion();
	$php_ver = phpversion();
	$expertrec_version = "1.2.18";

	$installer->startSetup();

	$result =array();

	$result['status'] = "Upgraded";
	$result['mage_ver'] = $mage_ver;
	$result['php_ver'] = $php_ver;
	$result['expertrec_ver'] = $expertrec_version;
	$result['site_host'] = $_SERVER['HTTP_HOST'];
	$result['site_subdomain'] = $_SERVER['SCRIPT_NAME'];

	if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
		$result['site_protocol'] = 'https';
	}
	else {
		$result['site_protocol'] = 'http';
	}

	$site_data = $installer->getConnection()->fetchAll("select * from {$this->getTable('core_config_data')} where `path` like '%trans_email%'");

	$result['site_name'] = $site_data[0]['value'];
	$result['site_email'] = $site_data[1]['value'];

	$admin_data = $installer->getConnection()->fetchAll("select * from {$this->getTable('admin_user')}");

	$result['admin_firstname'] = $admin_data[0]['firstname'];
	$result['admin_lastname'] = $admin_data[0]['lastname'];
	$result['admin_email'] = $admin_data[0]['email'];

	$postData = '';

	foreach($result as $key => $value) { 
		$postData .= $key . '='.$value.'&'; 
	}

	$postData = rtrim($postData, '&');

	$url = "https://feed.expertrec.com/magento-install/77c3b75abd2842e93b8c52c4aiu8y";

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($curl, CURLOPT_TIMEOUT, 130);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($curl);
	curl_close($curl);

	$installer->run("
		INSERT INTO {$this->getTable('core_config_data')} (`scope`, `scope_id`, `path`, `value`)
	    VALUES ('default',0,'expertrec/general/mid','new_user'),
	    ('default',0,'expertrec/general/secret','NTE5NTQ1Zjk4OGExYzYxOWFkOTkyN2Y3MDQ5MTQ3NTM='),
	    ('default',0,'expertrec/general/log_endpoint','https://feed.expertrec.com/magento/9418bba77c3b75abd2842e93b8c52c4a'),
	    ('default',0,'expertrec/general/headers','qty,is_in_stock,expert_image,expert_thumbnail,expert_category,expert_category_ids,final_price,entity_id,rating_summary,expert_url,created_at,image,msrp,name,price,short_description,sku,small_image,special_price,category_ids,minimal_price,status,visibility'),
	    ('default',0,'expertrec/general/expertrec_image_width',250),
	    ('default',0,'expertrec/general/expertrec_image_height',250),
	    ('default',0,'expertrec/general/expertrec_thumbnail_width',80),
	    ('default',0,'expertrec/general/expertrec_thumbnail_height',80),
	    ('default',0,'expertrec/general/expertrec_feed_push',0),
	    ('default',0,'expertrec/general/expertrec_feed_pushed_pages','0,0'),
	    ('default',0,'expertrec/general/debug','1'),
	    ('default',0,'expertrec/general/filters','filter_by_stock,filter_by_status')
	    ON DUPLICATE KEY UPDATE `value`=`value`;
	    
		UPDATE {$this->getTable('core_config_data')} SET `value`=0 WHERE `path`='expertrec/general/expertrec_feed_push';
		UPDATE {$this->getTable('core_config_data')} SET `value`='qty,is_in_stock,expert_image,expert_thumbnail,expert_category,expert_category_ids,final_price,entity_id,rating_summary,expert_url,created_at,image,msrp,name,price,short_description,sku,small_image,special_price,category_ids,minimal_price,status,visibility' WHERE `path`='expertrec/general/headers';
		UPDATE {$this->getTable('core_config_data')} SET `value`='0,0' WHERE `path`='expertrec/general/expertrec_feed_pushed_pages';
	");
	
	$installer->endSetup();
?>