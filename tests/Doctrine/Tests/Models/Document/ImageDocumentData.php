<?php

namespace Doctrine\Tests\Models\Document;

/**
 * @Entity
 * @Table(name="image_document_data")
 */
class ImageDocumentData extends DocumentData
{
    /**
     * @Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }
}
