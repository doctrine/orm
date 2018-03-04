<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

/**
 * Class ClassMetadataBuildingContext
 */
class ClassMetadataBuildingContext
{
    /** @var AbstractClassMetadataFactory */
    private $classMetadataFactory;

    /** @var SecondPass[] */
    protected $secondPassList = [];

    /** @var bool */
    private $inSecondPass = false;

    public function __construct(AbstractClassMetadataFactory $classMetadataFactory)
    {
        $this->classMetadataFactory = $classMetadataFactory;
    }

    public function getClassMetadataFactory() : AbstractClassMetadataFactory
    {
        return $this->classMetadataFactory;
    }

    public function addSecondPass(SecondPass $secondPass) : void
    {
        $this->secondPassList[] = $secondPass;
    }

    public function isInSecondPass() : bool
    {
        return $this->inSecondPass;
    }

    public function validate() : void
    {
        $this->inSecondPass = true;

        foreach ($this->secondPassList as $secondPass) {
            /** @var SecondPass $secondPass */
            $secondPass->process($this);
        }

        $this->inSecondPass = false;
    }
}
