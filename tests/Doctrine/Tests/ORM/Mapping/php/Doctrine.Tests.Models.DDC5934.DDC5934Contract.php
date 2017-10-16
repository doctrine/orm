<?php

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->setAssociationOverride('members', [
    'fetch' => ClassMetadata::FETCH_EXTRA_LAZY,
]);
