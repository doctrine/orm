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
     */
    private $id;

    /**
     * @Column(type="string", length=128)
     */
    private $bookcase;

    /**
     * @OneToOne(targetEntity="AbstractObject", inversedBy="shelf", cascade={"all"})
     * @MappedAssociation
     */
    private $object;

    public function getId()
    {
        return $this->id;
    }

    public function setBookcase($bookcase)
    {
        $this->bookcase = $bookcase;
    }

    public function getBookcase()
    {
        return $this->bookcase;
    }

    public function setObject(AbstractObject $object)
    {
        $object->setShelf($this);
        $this->object = $object;
    }

    public function getObject()
    {
        return $this->object;
    }
}
