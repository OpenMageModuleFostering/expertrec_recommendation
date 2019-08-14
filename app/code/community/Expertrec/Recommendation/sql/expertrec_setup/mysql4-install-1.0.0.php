<?php

$installer = $this;
$installer->startSetup();

$installer->run("
  INSERT INTO {$this->getTable('core_config_data')} (`scope`, `scope_id`, `path`, `value`)
  VALUES
    ('default',0,'expertrec/general/mid','new_user'),
    ('default',0,'expertrec/general/secret','NTE5NTQ1Zjk4OGExYzYxOWFkOTkyN2Y3MDQ5MTQ3NTM='),
    ('default',0,'expertrec/general/headers','is_in_stock,expert_image,expert_category,final_price,entity_id,rating_summary,expert_url,created_at,image,msrp,name,price,short_description,sku,small_image,special_price')
    ON DUPLICATE KEY UPDATE `value`=`value`;
");

$installer->endSetup();

?>
