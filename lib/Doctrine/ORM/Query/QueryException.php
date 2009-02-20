<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query;

/**
 * Description of QueryException
 *
 * @author robo
 */
class QueryException extends \Doctrine\Common\DoctrineException
{
    public static function nonUniqueResult()
    {
        return new self("The query contains more than one result.");   
    }
}