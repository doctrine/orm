<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Closure;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class LazyDataType extends Type
{
    /** @var Closure */
    private $initializer;

    /** @var Type */
    private $wrapped;

    public static function create(Closure $initializer) : LazyDataType
    {
        $type = new self();

        $type->setInitializer($initializer);

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->unwrap()->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType()
    {
        return $this->unwrap()->getBindingType();
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $this->unwrap()->getSQLDeclaration($fieldDeclaration, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $this->unwrap()->convertToDatabaseValue($value, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $this->unwrap()->convertToPHPValue($value, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return $this->unwrap()->convertToDatabaseValueSQL($sqlExpr, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return $this->unwrap()->convertToPHPValueSQL($sqlExpr, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function canRequireSQLConversion()
    {
        return $this->unwrap()->canRequireSQLConversion();
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return $this->unwrap()->requiresSQLCommentHint($platform);
    }

    private function setInitializer(Closure $initializer)
    {
        $this->initializer = $initializer;
    }

    private function unwrap() : Type
    {
        if ($this->wrapped === null) {
            $this->wrapped = ($this->initializer)();
        }

        return $this->wrapped;
    }
}
