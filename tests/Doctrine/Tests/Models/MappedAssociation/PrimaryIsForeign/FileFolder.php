<?php
namespace Doctrine\Tests\Models\MappedAssociation\PrimaryIsForeign;

/**
 * @Entity
 * @Table(name="pif_filefolder")
 * @NamedNativeQueries({
 *      @NamedNativeQuery(
 *          name                = "get-class-by-id",
 *          resultSetMapping    = "get-class",
 *          query               = "SELECT contentclass from pif_filefolder WHERE id = ?"
 *      )
 * })
 *
 * @SqlResultSetMappings({
 *      @SqlResultSetMapping(
 *          name    = "get-class",
 *          columns = {
 *              @ColumnResult(
 *                  name = "contentclass"
 *              )
 *          }
 *      )
 * })
 *
 */
class FileFolder
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
     * @var string $title
     */
    private $title;

    /**
     * @OneToOne(targetEntity="AbstractContent", mappedBy="fileFolder", cascade={"all"}, orphanRemoval=true)
     * @MappedAssociation
     *
     * @var AbstractContent $content
     */
    private $content;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param AbstractContent $content
     */
    public function setContent(AbstractContent $content)
    {
        $content->setFileFolder($this);
        $this->content = $content;
    }

    /**
     * @return AbstractContent
     */
    public function getContent()
    {
        return $this->content;
    }
}
