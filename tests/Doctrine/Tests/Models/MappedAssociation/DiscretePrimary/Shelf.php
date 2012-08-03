<?php
namespace Doctrine\Tests\Models\MappedAssociation\DiscretePrimary;

/**
 * @Entity
 * @Table(name="dp_shelf")
 * @NamedNativeQueries({
 *      @NamedNativeQuery(
 *          name                = "get-class-by-id",
 *          resultSetMapping    = "get-class",
 *          query               = "SELECT objectClass from dp_shelf WHERE id = ?"
 *      )
 * })
 *
 * @SqlResultSetMappings({
 *      @SqlResultSetMapping(
 *          name    = "get-class",
 *          columns = {
 *              @ColumnResult(
 *                  name = "objectClass"
 *              )
 *          }
 *      )
 * })
 *
 */
class Shelf
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     *
     * @var int $id
     */
    private $id;

    /**
     * @Column(type="string", length=128)
     *
     * @var string $bookcase
     */
    private $bookcase;

    /**
     * @OneToOne(targetEntity="AbstractObject", inversedBy="shelf", cascade={"all"})
     * @MappedAssociation
     *
     * @var AbstractObject $object
     */
    private $object;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $bookcase
     */
    public function setBookcase($bookcase)
    {
        $this->bookcase = $bookcase;
    }

    /**
     * @return string
     */
    public function getBookcase()
    {
        return $this->bookcase;
    }

    /**
     * @param AbstractObject $object
     */
    public function setObject(AbstractObject $object)
    {
        $object->setShelf($this);
        $this->object = $object;
    }

    /**
     * @return AbstractObject
     */
    public function getObject()
    {
        return $this->object;
    }
}
