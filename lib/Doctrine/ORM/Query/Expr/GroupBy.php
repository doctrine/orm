<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL Group By parts.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class GroupBy extends Base
{
    /**
     * @var string
     */
    protected $preSeparator  = '';

    /**
     * @var string
     */
    protected $postSeparator = '';

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}
