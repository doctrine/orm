<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

interface SecondPass
{
    /**
     * @param ClassMetadataBuildingContext $metadataBuildingContext
     *
     * @return void
     */
    public function process(ClassMetadataBuildingContext $metadataBuildingContext) : void;
}
