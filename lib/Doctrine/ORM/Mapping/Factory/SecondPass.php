<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;

interface SecondPass
{
    public function process(ClassMetadataBuildingContext $metadataBuildingContext) : void;
}
