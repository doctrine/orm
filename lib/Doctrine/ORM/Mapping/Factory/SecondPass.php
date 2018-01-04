<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

interface SecondPass
{
    public function process(ClassMetadataBuildingContext $metadataBuildingContext) : void;
}
