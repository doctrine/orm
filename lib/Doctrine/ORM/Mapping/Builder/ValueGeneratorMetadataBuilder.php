<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\Exception\TableGeneratorNotImplementedYet;
use Doctrine\ORM\Mapping\Exception\UnknownGeneratorType;
use Doctrine\ORM\Mapping\GeneratorType;
use Doctrine\ORM\Sequencing\Generator;
use ReflectionClass;
use ReflectionException;
use function assert;
use function constant;
use function in_array;
use function sprintf;
use function strtoupper;

class ValueGeneratorMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var Mapping\ClassMetadata */
    private $componentMetadata;

    /** @var string */
    private $fieldName;

    /** @var Type */
    private $fieldType;

    /** @var Annotation\GeneratedValue|null */
    private $generatedValueAnnotation;

    /** @var Annotation\SequenceGenerator|null */
    private $sequenceGeneratorAnnotation;

    /** @var Annotation\CustomIdGenerator|null */
    private $customIdGeneratorAnnotation;

    public function __construct(Mapping\ClassMetadataBuildingContext $metadataBuildingContext)
    {
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : ValueGeneratorMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        return $this;
    }

    public function withFieldName(string $fieldName) : ValueGeneratorMetadataBuilder
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function withFieldType(Type $fieldType) : ValueGeneratorMetadataBuilder
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    public function withGeneratedValueAnnotation(
        ?Annotation\GeneratedValue $generatedValueAnnotation
    ) : ValueGeneratorMetadataBuilder {
        $this->generatedValueAnnotation = $generatedValueAnnotation;

        return $this;
    }

    public function withSequenceGeneratorAnnotation(
        ?Annotation\SequenceGenerator $sequenceGeneratorAnnotation
    ) : ValueGeneratorMetadataBuilder {
        $this->sequenceGeneratorAnnotation = $sequenceGeneratorAnnotation;

        return $this;
    }

    public function withCustomIdGeneratorAnnotation(
        ?Annotation\CustomIdGenerator $customIdGeneratorAnnotation
    ) : ValueGeneratorMetadataBuilder {
        $this->customIdGeneratorAnnotation = $customIdGeneratorAnnotation;

        return $this;
    }

    public function build() : ?Mapping\ValueGeneratorMetadata
    {
        // Validate required fields
        \assert($this->componentMetadata !== null);
        \assert($this->fieldName !== null);
        \assert($this->fieldType !== null);

        if (! $this->generatedValueAnnotation) {
            return null;
        }

        $platform      = $this->metadataBuildingContext->getTargetPlatform();
        $strategy      = \strtoupper($this->generatedValueAnnotation->strategy);
        $generatorType = \constant(\sprintf('%s::%s', Mapping\GeneratorType::class, $strategy));

        if (\in_array($generatorType, [Mapping\GeneratorType::AUTO, Mapping\GeneratorType::IDENTITY], true)) {
            $generatorType = $platform->prefersSequences() || $platform->usesSequenceEmulatedIdentityColumns()
                ? Mapping\GeneratorType::SEQUENCE
                : ($platform->prefersIdentityColumns() ? Mapping\GeneratorType::IDENTITY : Mapping\GeneratorType::TABLE);
        }

        switch ($generatorType) {
            case Mapping\GeneratorType::IDENTITY:
                $generator = $this->fieldType->getName() === 'bigint'
                    ? new Generator\BigIntegerIdentityGenerator()
                    : new Generator\IdentityGenerator();

                return new Mapping\ValueGeneratorMetadata($generatorType, $generator);

                break;

            case Mapping\GeneratorType::SEQUENCE:
                $sequenceName   = null;
                $allocationSize = 1;

                if ($this->sequenceGeneratorAnnotation) {
                    $sequenceName   = $this->sequenceGeneratorAnnotation->sequenceName ?? null;
                    $allocationSize = $this->sequenceGeneratorAnnotation->allocationSize ?? 1;
                }

                if (empty($sequenceName)) {
                    $sequenceName = $platform->fixSchemaElementName(
                        \sprintf(
                            '%s_%s_seq',
                            $platform->getSequencePrefix(
                                $this->componentMetadata->getTableName(),
                                $this->componentMetadata->getSchemaName()
                            ),
                            $this->fieldName
                        )
                    );
                }

                $generator = new Generator\SequenceGenerator($sequenceName, $allocationSize);

                return new Mapping\ValueGeneratorMetadata($generatorType, $generator);

                break;

            case Mapping\GeneratorType::CUSTOM:
                \assert($this->customIdGeneratorAnnotation !== null);

                if (empty($this->customIdGeneratorAnnotation->class)) {
                    $message = 'Cannot instantiate custom generator, no class has been defined';

                    throw new Mapping\MappingException($message);
                }

                try {
                    $reflectionClass = new ReflectionClass($this->customIdGeneratorAnnotation->class);
                    $generator       = $reflectionClass->newInstanceArgs($this->customIdGeneratorAnnotation->arguments);

                    return new Mapping\ValueGeneratorMetadata($generatorType, $generator);
                } catch (ReflectionException $exception) {
                    $message = \sprintf(
                        'Cannot instantiate custom generator : %s',
                        $this->customIdGeneratorAnnotation->class
                    );

                    throw new Mapping\MappingException($message);
                }

                break;

            case GeneratorType::TABLE:
                throw TableGeneratorNotImplementedYet::create();

            case null: // Function constant() returns null if it is not defined in class.
                throw UnknownGeneratorType::create($strategy);

            case Mapping\GeneratorType::NONE:
            default:
                return null;
        }
    }
}
