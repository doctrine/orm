<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;

/**
 * Visit Expressions and generate SQL WHERE conditions from them.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.3
 */
class SqlExpressionVisitor extends ExpressionVisitor
{
    /**
     * @var \Doctrine\ORM\Persisters\Entity\BasicEntityPersister
     */
    private $persister;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    private $classMetadata;

    /**
     * @param \Doctrine\ORM\Persisters\Entity\BasicEntityPersister $persister
     * @param \Doctrine\ORM\Mapping\ClassMetadata                  $classMetadata
     */
    public function __construct(BasicEntityPersister $persister, ClassMetadata $classMetadata)
    {
        $this->persister = $persister;
        $this->classMetadata = $classMetadata;
    }

    /**
     * Converts a comparison expression into the target query language output.
     *
     * @param \Doctrine\Common\Collections\Expr\Comparison $comparison
     *
     * @return mixed
     */
    public function walkComparison(Comparison $comparison)
    {
        $field    = $comparison->getField();
        $value    = $comparison->getValue()->getValue(); // shortcut for walkValue()
        $property = $this->classMetadata->getProperty($field);

        if ($property instanceof AssociationMetadata &&
            $value !== null &&
            ! is_object($value) &&
            ! in_array($comparison->getOperator(), [Comparison::IN, Comparison::NIN])) {

            throw PersisterException::matchingAssocationFieldRequiresObject($this->classMetadata->getClassName(), $field);
        }

        return $this->persister->getSelectConditionStatementSQL($field, $value, null, $comparison->getOperator());
    }

    /**
     * Converts a composite expression into the target query language output.
     *
     * @param \Doctrine\Common\Collections\Expr\CompositeExpression $expr
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = [];

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        switch ($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return '(' . implode(' AND ', $expressionList) . ')';

            case CompositeExpression::TYPE_OR:
                return '(' . implode(' OR ', $expressionList) . ')';

            default:
                throw new \RuntimeException("Unknown composite " . $expr->getType());
        }
    }

    /**
     * Converts a value expression into the target query language part.
     *
     * @param \Doctrine\Common\Collections\Expr\Value $value
     *
     * @return mixed
     */
    public function walkValue(Value $value)
    {
        return '?';
    }
}
