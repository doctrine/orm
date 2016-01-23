<?php

$metadata->mapField(array(
    'type'      => 'integer',
    'name'      => 'maxPrice',
    'fieldName' => 'maxPrice',
));
$metadata->addEntityListener(\Shitty\ORM\Events::postPersist, 'CompanyContractListener', 'postPersistHandler');
$metadata->addEntityListener(\Shitty\ORM\Events::prePersist, 'CompanyContractListener', 'prePersistHandler');

$metadata->addEntityListener(\Shitty\ORM\Events::postUpdate, 'CompanyContractListener', 'postUpdateHandler');
$metadata->addEntityListener(\Shitty\ORM\Events::preUpdate, 'CompanyContractListener', 'preUpdateHandler');

$metadata->addEntityListener(\Shitty\ORM\Events::postRemove, 'CompanyContractListener', 'postRemoveHandler');
$metadata->addEntityListener(\Shitty\ORM\Events::preRemove, 'CompanyContractListener', 'preRemoveHandler');

$metadata->addEntityListener(\Shitty\ORM\Events::preFlush, 'CompanyContractListener', 'preFlushHandler');
$metadata->addEntityListener(\Shitty\ORM\Events::postLoad, 'CompanyContractListener', 'postLoadHandler');

$metadata->addEntityListener(\Shitty\ORM\Events::prePersist, 'CompanyFlexUltraContractListener', 'prePersistHandler1');
$metadata->addEntityListener(\Shitty\ORM\Events::prePersist, 'CompanyFlexUltraContractListener', 'prePersistHandler2');