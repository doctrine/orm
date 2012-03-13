<?php

namespace Doctrine\Tests\Models\Document;

/**
 * @Entity
 * @Table(name="document_data")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"document_data"="DocumentData", "image_document_data"="ImageDocumentData"})
 */
abstract class DocumentData
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
