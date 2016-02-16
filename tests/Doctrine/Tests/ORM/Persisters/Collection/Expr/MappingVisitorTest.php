<?php


namespace Doctrine\Tests\ORM\Persisters\Collection\Expr;


use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Persisters\Collection\Expr\MappingVisitor;

class MappingVisitorTest extends \PHPUnit_Framework_TestCase
{
    /** @var  QuoteStrategy */
    private $quoteStrategy;
    /** @var  MappingVisitor */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        /** @var ClassMetadata $metadata */
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var AbstractPlatform $platform */
        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteStrategy = $this->getMockBuilder(QuoteStrategy::class)
            ->getMock();

        $this->quoteStrategy->expects($this->any())
            ->method('getColumnName')
            ->with('field', $metadata, $platform)
            ->willReturn('column');

        $this->sut = new MappingVisitor(
            $this->quoteStrategy,
            $metadata,
            $platform
        );
    }


    public function testWalkComparisonReturnsComparisonWithMappedField()
    {
        $expr = new Comparison('field', Comparison::GT, 73);
        /** @var Comparison $mapped */
        $mapped = $this->sut->dispatch($expr);
        static::assertInstanceOf(Comparison::class, $mapped);
        static::assertEquals('column', $mapped->getField());
        static::assertEquals($expr->getOperator(), $mapped->getOperator());
        static::assertEquals($expr->getValue(), $mapped->getValue());

    }

    public function testWalkValueReturnsValue()
    {
        $value = new Value('foo');
        static::assertSame($value, $this->sut->dispatch($value));
    }

    public function testWalkCompositeExpressionReturnsMappedCompositeExpression()
    {
        $gtExpr = new Comparison('field', Comparison::GT, 73);
        $ltExpr = new Comparison('field', Comparison::LT, 53);

        $composite = new CompositeExpression(
            CompositeExpression::TYPE_OR, [$gtExpr, $ltExpr]);

        /** @var CompositeExpression $mapped */
        $mapped = $this->sut->dispatch($composite);
        static::assertInstanceOf(CompositeExpression::class, $mapped);
        /** @var Comparison[]|Value[] $mappedExpressions */
        $mappedExpressions = $mapped->getExpressionList();

        static::assertCount(2, $mappedExpressions);

        static::assertInstanceOf(Comparison::class, $mappedExpressions[0]);
        static::assertInstanceOf(Comparison::class, $mappedExpressions[1]);

        static::assertEquals('column', $mappedExpressions[0]->getField());
        static::assertEquals('column', $mappedExpressions[1]->getField());

        static::assertEquals($gtExpr->getOperator(), $mappedExpressions[0]->getOperator());
        static::assertEquals($ltExpr->getOperator(), $mappedExpressions[1]->getOperator());

        static::assertEquals($gtExpr->getValue(), $mappedExpressions[0]->getValue());
        static::assertEquals($ltExpr->getValue(), $mappedExpressions[1]->getValue());
    }
}
