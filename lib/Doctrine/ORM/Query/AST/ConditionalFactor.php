<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalFactor ::= ["NOT"] ConditionalPrimary
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ConditionalFactor extends Node
{
    /**
     * @var bool
     */
    public $not = false;

    /**
     * @var ConditionalPrimary
     */
    public $conditionalPrimary;

    /**
     * @param ConditionalPrimary $conditionalPrimary
     */
    public function __construct($conditionalPrimary)
    {
        $this->conditionalPrimary = $conditionalPrimary;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkConditionalFactor($this);
    }
}
