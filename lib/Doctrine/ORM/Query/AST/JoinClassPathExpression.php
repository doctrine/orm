<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * JoinClassPathExpression ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link    www.doctrine-project.org
 * @since   2.3
 * @author  Alexander <iam.asm89@gmail.com>
 */
class JoinClassPathExpression extends Node
{
    /**
     * @var mixed
     */
    public $abstractSchemaName;

    /**
     * @var mixed
     */
    public $aliasIdentificationVariable;

    /**
     * @param mixed $abstractSchemaName
     * @param mixed $aliasIdentificationVar
     */
    public function __construct($abstractSchemaName, $aliasIdentificationVar)
    {
        $this->abstractSchemaName = $abstractSchemaName;
        $this->aliasIdentificationVariable = $aliasIdentificationVar;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkJoinPathExpression($this);
    }
}
