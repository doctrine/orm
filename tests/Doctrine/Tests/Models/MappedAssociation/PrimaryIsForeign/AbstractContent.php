<?php
namespace Doctrine\Tests\Models\MappedAssociation\PrimaryIsForeign;

/**
 * @MappedSuperclass
 */
class AbstractContent
{
    /**
     * @Id
     * @OneToOne(targetEntity="FileFolder", inversedBy="content")
     *
     * @var FileFolder $fileFolder
     */
    private $fileFolder;

    /**
     * @Column(type="string", length=128)
     *
     * @var string $description
     */
    private $description;

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return FileFolder
     */
    public function getFileFolder()
    {
        return $this->fileFolder;
    }

    /**
     * @param FileFolder $fileFolder
     */
    public function setFileFolder(FileFolder $fileFolder)
    {
        $this->fileFolder = $fileFolder;
    }
}
