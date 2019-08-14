<?php

//echo 'Running The Upgrade from 1.1.5 to 1.1.6 : '.get_class($this)."\n <br /> \n";
// Mage::log("Running The Upgrade to 1.2.1 : ".get_class($this));

$installer = $this;
$mage_ver = Mage::getVersion();
$php_ver = phpversion();
$host = $_SERVER['HTTP_HOST'];  
$uri = $_SERVER['SCRIPT_NAME']; 
// $protocol = $_SERVER['REQUEST_SCHEME'];

$installer->startSetup();

$result =array();
//status
$result['status'] = "Upgraded";
//magento version
$result['mage_ver'] = $mage_ver;
//expertrec version
$result['expertrec_ver'] = '1.2.9';
$result['php_ver'] = $php_ver;

if (isset($_SERVER['HTTPS']) &&
    ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $protocol = 'https';
}
else {
  $protocol = 'http';
}

//hostname 
$result['site_protocol'] = $protocol;
$result['site_host'] = $host;
//Subdomain
$result['site_subdomain'] = $uri;
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
  UPDATE {$this->getTable('core_config_data')} SET `value`=0 WHERE `path`='expertrec/general/expertrec_feed_push';
");

$installer->endSetup();

?>