<?php


namespace Doctrine\ORM\Persisters\Collection\Expr;


use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\QuoteStrategy;

class MappingVisitor extends ExpressionVisitor
{
    /** @var  QuoteStrategy */
    private $quoteStrategy;
    /** @var  ClassMetadata */
    private $metadata;
    /** @var  AbstractPlatform */
    private $platform;

    /**
     * MappingVisitor constructor.
     * @param QuoteStrategy $quoteStrategy
     * @param ClassMetadata $metadata
     * @param AbstractPlatform $platform
     */
    public function __construct(QuoteStrategy $quoteStrategy, ClassMetadata $metadata, AbstractPlatform $platform)
    {
        $this->quoteStrategy = $quoteStrategy;
        $this->metadata = $metadata;
        $this->platform = $platform;
    }


    /**
     * Converts a comparison expression into the target query language output.
     *
     * @param Comparison $comparison
     *
     * @return mixed
     */
    public function walkComparison(Comparison $comparison)
    {
        return new Comparison(
            $this->quoteStrategy->getColumnName($comparison->getField(), $this->metadata, $this->platform),
            $comparison->getOperator(),
            $comparison->getValue()
        );
    }

    /**
     * Converts a value expression into the target query language part.
     *
     * @param Value $value
     *
     * @return mixed
     */
    public function walkValue(Value $value)
    {
        return $value;
    }

    /**
     * Converts a composite expression into the target query language output.
     *
     * @param CompositeExpression $expr
     *
     * @return mixed
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressions = [];
        foreach($expr->getExpressionList() as $expression) {
            $expressions[] = $this->dispatch($expression);
        }

        return new CompositeExpression($expr->getType(), $expressions);
    }
}