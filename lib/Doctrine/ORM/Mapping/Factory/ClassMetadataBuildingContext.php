<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

/**
 * Class ClassMetadataBuildingContext
 *
 * @package Doctrine\ORM\Mapping\Factory
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ClassMetadataBuildingContext
{
    private $classMetadataFactory;

    /**
     * @var array<SecondPass>
     */
    protected $secondPassList = [];

    /**
     * @var bool
     */
    private $inSecondPass = false;

    /**
     * ClassMetadataBuildingContext constructor.
     *
     * @param AbstractClassMetadataFactory $classMetadataFactory
     */
    public function __construct(AbstractClassMetadataFactory $classMetadataFactory)
    {
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * @return AbstractClassMetadataFactory
     */
    public function getClassMetadataFactory() : AbstractClassMetadataFactory
    {
        return $this->classMetadataFactory;
    }

    /**
     * @param SecondPass $secondPass
     *
     * @return void
     */
    public function addSecondPass(SecondPass $secondPass) : void
    {
        $this->secondPassList[] = $secondPass;
    }

    /**
     * @return bool
     */
    public function isInSecondPass() : bool
    {
        return $this->inSecondPass;
    }

    /**
     * @return void
     */
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
