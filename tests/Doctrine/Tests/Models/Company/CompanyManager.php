<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @DoctrineEntity
 */
class CompanyManager extends CompanyEmployee
{
    /*
     * @DoctrineColumn(type="varchar", length="255")
     */
    public $title;
}