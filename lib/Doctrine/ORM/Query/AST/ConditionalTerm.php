<?php
declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalTerm ::= ConditionalFactor {"AND" ConditionalFactor}*
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ConditionalTerm extends Node
{
    /**
     * @var array
     */
    public $conditionalFactors = [];

    /**
     * @param array $conditionalFactors
     */
    public function __construct(array $conditionalFactors)
    {
        $this->conditionalFactors = $conditionalFactors;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkConditionalTerm($this);
    }
}
