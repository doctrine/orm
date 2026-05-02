<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping\ToManyAssociationMapping;
use SortDirection;

use function array_map;
use function is_a;
use function strtoupper;

trait CompatOrderings
{
    /** 3.1 is guaranteed thanks to a Composer constraint */
    private function isCollections31(): bool
    {
        // @phpstan-ignore function.alreadyNarrowedType
        return is_a(ReadableCollection::class, Selectable::class, true);
    }

    /**
     * @return array<string, Order>|array<string, SortDirection>
     *
     * @phpstan-ignore return.deprecatedEnum
     */
    private function getOrderings(Criteria $criteria): array
    {
        return $this->isCollections31()
            ? $criteria->getOrderings()
            // @phpstan-ignore method.deprecated
            : $criteria->orderings();
    }

    /** @return array<string, string> */
    private function getOrderingsAsStringMap(Criteria $criteria): array
    {
        if ($this->isCollections31()) {
            return array_map(
                static fn (SortDirection $order): string => $order === SortDirection::Ascending ? 'ASC' : 'DESC',
                $criteria->getOrderings(),
            );
        }

        // @phpstan-ignore method.deprecated
        return array_map(
            // @phpstan-ignore property.deprecatedEnum, parameter.deprecatedEnum
            static fn (Order $order): string => $order->value,
            // @phpstan-ignore method.deprecated
                $criteria->orderings(),
        );
    }

    private function orderCriteriaByAssociation(Criteria $criteria, ToManyAssociationMapping $association): void
    {
        // @phpstan-ignore function.alreadyNarrowedType
        if ($this->isCollections31()) {
            $criteria->orderBy(
                $criteria->getOrderings() ?: array_map(
                    static function (SortDirection|string $order): SortDirection {
                        if ($order instanceof SortDirection) {
                            return $order;
                        }

                        return strtoupper($order) === 'ASC'
                                        ? SortDirection::Ascending
                                        : SortDirection::Descending;
                    },
                    $association->orderBy(),
                ),
            );

            return;
        }

        $criteria->orderBy(
            // @phpstan-ignore method.deprecated
            $criteria->orderings() ?: array_map(
                // @phpstan-ignore staticMethod.deprecatedEnum, return.deprecatedEnum
                static function (SortDirection|string $order): Order {
                    if ($order instanceof SortDirection) {
                        // @phpstan-ignore classConstant.deprecatedEnum, classConstant.deprecatedEnum
                        return $order === SortDirection::Ascending ? Order::Ascending : Order::Descending;
                    }

                    // @phpstan-ignore return.deprecatedEnum, staticMethod.deprecatedEnum
                    return Order::from(strtoupper($order));
                },
                $association->orderBy(),
            ),
        );
    }
}
