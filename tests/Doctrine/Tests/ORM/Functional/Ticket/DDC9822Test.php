<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-9822
 */
class DDC9822Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(DDC9822Field::class),
            $this->_em->getClassMetadata(DDC9822Mapping::class),
            $this->_em->getClassMetadata(DDC9822Assignment::class),
            $this->_em->getClassMetadata(DDC9822AssignmentFieldA::class),
            $this->_em->getClassMetadata(DDC9822AssignmentFieldB::class),
            $this->_em->getClassMetadata(DDC9822AssignmentFieldC::class),
        ]);
    }

    public function testIssue(): void
    {
        // Create fields & mapping
        $fieldA  = new DDC9822Field('field-a');
        $fieldB  = new DDC9822Field('field-b');
        $fieldC  = new DDC9822Field('field-c');
        $mapping = new DDC9822Mapping('mapping-a');

        $this->_em->persist($fieldA);
        $this->_em->persist($fieldB);
        $this->_em->persist($fieldC);
        $this->_em->persist($mapping);
        $this->_em->flush();

        // Connect/Assign the fields to the mapping
        $mapping->assignedFields->add(new DDC9822AssignmentFieldA($mapping, $fieldA));
        $mapping->assignedFields->add(new DDC9822AssignmentFieldB($mapping, $fieldB));
        $mapping->assignedFields->add(new DDC9822AssignmentFieldC('child-id', $mapping, $fieldC));

        $this->_em->persist($mapping);
        $this->_em->flush();

        $this->_em->clear();

        $mapping     = $this->_em->find(DDC9822Mapping::class, 'mapping-a');
        $fieldA      = $this->_em->find(DDC9822Field::class, 'field-a');
        $fieldB      = $this->_em->find(DDC9822Field::class, 'field-b');
        $fieldC      = $this->_em->find(DDC9822Field::class, 'field-c');
        $assignmentA = $this->_em->find(DDC9822Assignment::class, ['mapping' => 'mapping-a', 'field' => 'field-a']);
        $assignmentB = $this->_em->find(DDC9822Assignment::class, ['mapping' => 'mapping-a', 'field' => 'field-b']);
        $assignmentC = $this->_em->find(DDC9822Assignment::class, ['mapping' => 'mapping-a', 'field' => 'field-c']);

        self::assertNotNull($mapping);
        self::assertNotNull($fieldA);
        self::assertNotNull($fieldB);
        self::assertNotNull($fieldC);
        self::assertNotNull($assignmentA);
        self::assertNotNull($assignmentB);
        self::assertNotNull($assignmentC);

        // Test collections

        // mapping should have a count of 3, because it should have 3 fields assigned to it
        self::assertCount(3, $mapping->assignedFields);

        $mappingEntryA = $mapping->assignedFields->get($mapping->assignedFields->indexOf($assignmentA));
        self::assertEquals($assignmentA, $mappingEntryA);
        self::assertEquals($mapping, $mappingEntryA->mapping);
        self::assertEquals($fieldA, $mappingEntryA->field);

        $mappingEntryB = $mapping->assignedFields->get($mapping->assignedFields->indexOf($assignmentB));
        self::assertEquals($assignmentB, $mappingEntryB);
        self::assertEquals($mapping, $mappingEntryB->mapping);
        self::assertEquals($fieldB, $mappingEntryB->field);

        $mappingEntryC = $mapping->assignedFields->get($mapping->assignedFields->indexOf($assignmentC));
        self::assertEquals($assignmentC, $mappingEntryC);
        self::assertEquals($mapping, $mappingEntryC->mapping);
        self::assertEquals($fieldC, $mappingEntryC->field);

        // fieldA should have a count of 1, because it is connected/assigned to 1 mapping
        self::assertCount(1, $fieldA->assignedMappings);
        self::assertEquals($assignmentA, $fieldA->assignedMappings->get(0));
        self::assertEquals($fieldA, $fieldA->assignedMappings->get(0)->field);
        self::assertEquals($mapping, $fieldA->assignedMappings->get(0)->mapping);

        // fieldA should have a count of 1, because it is connected/assigned to 1 mapping
        self::assertCount(1, $fieldB->assignedMappings);
        self::assertEquals($assignmentB, $fieldB->assignedMappings->get(0));
        self::assertEquals($fieldB, $fieldB->assignedMappings->get(0)->field);
        self::assertEquals($mapping, $fieldB->assignedMappings->get(0)->mapping);

        // fieldC should have a count of 1, because it is connected/assigned to 1 mapping
        self::assertCount(1, $fieldC->assignedMappings);
        self::assertEquals($assignmentC, $fieldC->assignedMappings->get(0));
        self::assertEquals($fieldC, $fieldC->assignedMappings->get(0)->field);
        self::assertEquals($mapping, $fieldC->assignedMappings->get(0)->mapping);
    }
}

/**
 * @Entity
 */
class DDC9822Field
{
    /**
     * @Id
     * @Column(type="string")
     * @var string
     */
    public $id;

    /**
     * @OneToMany(
     *     targetEntity="DDC9822Assignment",
     *     mappedBy="field",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     * @var ArrayCollection
     */
    public $assignedMappings;

    public function __construct($id)
    {
        $this->id               = $id;
        $this->assignedMappings = new ArrayCollection();
    }
}

/**
 * @Entity
 */
class DDC9822Mapping
{
    /**
     * @Id
     * @Column(type="string")
     * @var string
     */
    public $id;

    /**
     * @OneToMany(
     *     targetEntity="DDC9822Assignment",
     *     mappedBy="mapping",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     * @var ArrayCollection
     */
    public $assignedFields;

    public function __construct($id)
    {
        $this->id             = $id;
        $this->assignedFields = new ArrayCollection();
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"a" = "DDC9822AssignmentFieldA", "b" = "DDC9822AssignmentFieldB", "c" = "DDC9822AssignmentFieldC"})
 */
abstract class DDC9822Assignment
{
    /**
     * @Id
     * @ManyToOne(targetEntity="DDC9822Mapping", inversedBy="assignedFields")
     * @var DDC9822Mapping
     */
    public $mapping;

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC9822Field", inversedBy="assignedMappings")
     * @var DDC9822Field
     */
    public $field;

    public function __construct($mapping, $field)
    {
        $this->mapping = $mapping;
        $this->field   = $field;
    }
}

/**
 * @Entity
 */
class DDC9822AssignmentFieldA extends DDC9822Assignment
{
    /**
     * @var string
     * @Column(type="string")
     */
    public $extension = 'ext-a';
}

/**
 * @Entity
 */
class DDC9822AssignmentFieldB extends DDC9822Assignment
{
    /**
     * @var string
     * @Column(type="string")
     */
    public $extension = 'ext-b';
}

/**
 * @Entity
 */
class DDC9822AssignmentFieldC extends DDC9822Assignment
{
    /**
     * @Id
     * @Column(type="string")
     * @var string
     */
    public $additionalId;

    /**
     * @var string
     * @Column(type="string")
     */
    public $extension = 'ext-c';

    public function __construct($id, $mapping, $field)
    {
        parent::__construct($mapping, $field);
        $this->additionalId = $id;
    }
}
