<?php

//echo 'Installing extension: '.get_class($this)."\n <br /> \n";
// Mage::log("Installing magento : 1.2.2");

$installer = $this;
$mage_ver = Mage::getVersion();
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$installer->startSetup();

$result =array();
//install/upgrade status
$result['status'] = "Installed";
//magento version
$result['mage_ver'] = $mage_ver;
//expertrec version
$result['expertrec_ver'] = '1.2.3';
//Subdomain
$result['site_subdomain'] = $uri;
//hostname 
$result['site_host'] = $host;
//selecting for site_details
$site_data = $installer->getConnection()->fetchAll("select * from {$this->getTable('core_config_data')} where `path` like '%trans_email%'");
//site name and email
$result['site_name'] = $site_data[0]['value'];
$result['site_email'] = $site_data[1]['value'];
//selecting for admin_details
$admin_data = $installer->getConnection()->fetchAll("select * from {$this->getTable('admin_user')}");
//admin name and email
$result['admin_firstname'] = $admin_data[0]['firstname'];
$result['admin_lastname'] = $admin_data[0]['lastname'];
$result['admin_email'] = $admin_data[0]['email'];

$postData = '';
//create name value pairs seperated by &
foreach($result as $key => $value) 
{ 
  $postData .= $key . '='.$value.'&'; 
}
$postData = rtrim($postData, '&');

//call install api
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
  VALUES
    ('default',0,'expertrec/general/mid','new_user'),
    ('default',0,'expertrec/general/secret','NTE5NTQ1Zjk4OGExYzYxOWFkOTkyN2Y3MDQ5MTQ3NTM='),
    ('default',0,'expertrec/general/log_endpoint','https://feed.expertrec.com/magento/9418bba77c3b75abd2842e93b8c52c4a'),
    ('default',0,'expertrec/general/headers','is_in_stock,expert_image,expert_thumbnail,expert_category,expert_category_ids,final_price,entity_id,rating_summary,expert_url,created_at,image,msrp,name,price,short_description,sku,small_image,special_price,category_ids'),
    ('default',0,'expertrec/general/expertrec_image_width',250),
    ('default',0,'expertrec/general/expertrec_image_height',250),
    ('default',0,'expertrec/general/expertrec_thumbnail_width',80),
    ('default',0,'expertrec/general/expertrec_thumbnail_height',80),
     ('default',0,'expertrec/general/expertrec_feed_push',0)
    ON DUPLICATE KEY UPDATE `value`=`value`;
");

$installer->endSetup();

?>