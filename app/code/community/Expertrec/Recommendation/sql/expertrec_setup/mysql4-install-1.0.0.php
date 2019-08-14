<?php

$installer = $this;
$installer->startSetup();

$installer->run("
  INSERT INTO {$this->getTable('core_config_data')} (`scope`, `scope_id`, `path`, `value`)
  VALUES
    ('default',0,'expertrec/general/mid','new_user'),
    ('default',0,'expertrec/general/secret','NTE5NTQ1Zjk4OGExYzYxOWFkOTkyN2Y3MDQ5MTQ3NTM='),
    ('default',0,'expertrec/general/headers','is_in_stock,expert_image,expert_thumbnail,expert_category,final_price,entity_id,rating_summary,expert_url,created_at,image,msrp,name,price,short_description,sku,small_image,special_price,category_ids'),
    ('default',0,'expertrec/general/expertrec_image_width',250),
    ('default',0,'expertrec/general/expertrec_image_height',250),
    ('default',0,'expertrec/general/expertrec_thumbnail_width',80),
    ('default',0,'expertrec/general/expertrec_thumbnail_height',80)
    ON DUPLICATE KEY UPDATE `value`=`value`;
");

$installer->endSetup();

?>
