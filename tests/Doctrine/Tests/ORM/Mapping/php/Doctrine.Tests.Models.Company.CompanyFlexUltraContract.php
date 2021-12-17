<?php

declare(strict_types=1);

use Doctrine\ORM\Events;

$metadata->mapField(
    [
        'type'      => 'integer',
        'name'      => 'maxPrice',
        'fieldName' => 'maxPrice',
    ]
);
$metadata->addEntityListener(Events::postPersist, 'CompanyContractListener', 'postPersistHandler');
$metadata->addEntityListener(Events::prePersist, 'CompanyContractListener', 'prePersistHandler');

$metadata->addEntityListener(Events::postUpdate, 'CompanyContractListener', 'postUpdateHandler');
$metadata->addEntityListener(Events::preUpdate, 'CompanyContractListener', 'preUpdateHandler');

$metadata->addEntityListener(Events::postRemove, 'CompanyContractListener', 'postRemoveHandler');
$metadata->addEntityListener(Events::preRemove, 'CompanyContractListener', 'preRemoveHandler');

$metadata->addEntityListener(Events::preFlush, 'CompanyContractListener', 'preFlushHandler');
$metadata->addEntityListener(Events::postLoad, 'CompanyContractListener', 'postLoadHandler');

$metadata->addEntityListener(Events::prePersist, 'CompanyFlexUltraContractListener', 'prePersistHandler1');
$metadata->addEntityListener(Events::prePersist, 'CompanyFlexUltraContractListener', 'prePersistHandler2');
