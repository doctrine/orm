<?php
namespace Doctrine\Tests\Models\CompositeKeyRelations;

/**
 * @Entity
 */
class CustomerClass
{
    /** @Id @Column(type="string") */
    public $companyCode;

    /** @Id @Column(type="string") */
    public $code;

    /** @Column(type="string") */
    public $name;
}
