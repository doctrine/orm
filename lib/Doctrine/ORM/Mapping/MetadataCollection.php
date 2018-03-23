<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;


class MetadataCollection
{

    /**
     * @var ClassMetadata[]
     */
    private $classMetadatas;

    private function __construct(array $classMetadatas)
    {
        $metadataByClassName = array_reduce (
            $classMetadatas,
            function ( $carry, ClassMetadata $classMetadata )
            {
                $className = $classMetadata->getClassName();
                $carry[$className] = $classMetadata;
                return $carry;
            } ,
            []
        ) ;
        $this->classMetadatas = $metadataByClassName;
    }

    public function get($name)
    {
        if(!isset($this->classMetadatas[$name])){
            throw new \Exception('No metadata found for ' . $name);
        }
        return $this->classMetadatas[$name];
    }

    public static function fromClassMetadatas(array $classMetadatas)
    {
        return new self($classMetadatas);
    }
}
