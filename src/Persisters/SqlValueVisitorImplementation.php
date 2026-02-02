<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;

use function defined;

if (defined(Criteria::class . '::ASC')) {
    // collections 2
    /** @internal */
    trait SqlValueVisitorImplementation
    {
        abstract private function doWalkComparison(Comparison $comparison): mixed;

        abstract private function doWalkCompositeExpression(CompositeExpression $comparison): mixed;

        /**
         * Converts a comparison expression into the target query language output.
         *
         * {@inheritDoc}
         *
         * @phpstan-ignore missingType.return
         */
        public function walkComparison(Comparison $comparison)
        {
            return $this->doWalkComparison($comparison);
        }

        /**
         * Converts a value expression into the target query language part.
         *
         * {@inheritDoc}
         *
         * @phpstan-ignore missingType.return
         */
        public function walkValue(Value $value)
        {
            return null;
        }

        /**
         * Converts a composite expression into the target query language output.
         *
         * {@inheritDoc}
         *
         * @phpstan-ignore missingType.return
         */
        public function walkCompositeExpression(CompositeExpression $expr)
        {
            return $this->doWalkCompositeExpression($expr);
        }
    }
} else {
    // collections 3
    /** @internal */
    trait SqlValueVisitorImplementation
    {
        abstract private function doWalkComparison(Comparison $comparison): mixed;

        abstract private function doWalkCompositeExpression(CompositeExpression $comparison): mixed;

        /** Converts a comparison expression into the target query language output. */
        public function walkComparison(Comparison $comparison): mixed
        {
            return $this->doWalkComparison($comparison);
        }

        /** Converts a value expression into the target query language part. */
        public function walkValue(Value $value): mixed
        {
            return null;
        }

        /** Converts a composite expression into the target query language output. */
        public function walkCompositeExpression(CompositeExpression $expr): mixed
        {
            return $this->doWalkCompositeExpression($expr);
        }
    }
}
