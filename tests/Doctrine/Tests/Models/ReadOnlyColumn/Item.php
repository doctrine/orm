<?php

namespace Doctrine\Tests\Models\ReadOnlyColumn;

/**
 * @Entity
 * @Table(name="readonly_column")
 */
class Item
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $label;

    /**
     * @Column(type="string", readOnly=false)
     */
    public $content;

    /**
     * @Column(type="string", readOnly=true)
     */
    public $generatedString;
}
