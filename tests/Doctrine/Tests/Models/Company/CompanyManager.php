<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @DoctrineEntity
 */
class CompanyManager extends CompanyEmployee
{
    /*
     * @DoctrineColumn(type="string", length="255")
     */
    public $title;
}