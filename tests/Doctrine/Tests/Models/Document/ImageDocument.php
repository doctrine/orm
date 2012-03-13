<?php

namespace Doctrine\Tests\Models\Document;

/**
 * @Entity
 * @Table(name="image_document")
 */
class ImageDocument extends Document
{
    protected $dataClass = 'Doctrine\Tests\Models\Document\ImageDocumentData';

    public function setUrl($url)
    {
        $this->getData()->setUrl($url);
    }

    public function getUrl()
    {
        return $this->getData()->getUrl();
    }
}
