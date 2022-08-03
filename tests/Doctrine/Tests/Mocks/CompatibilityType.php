<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\ParameterType;

use function enum_exists;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80100 || ! enum_exists(ParameterType::class)) {
    trait CompatibilityType
    {
        public function getBindingType(): int
        {
            return $this->doGetBindingType();
        }

        private function doGetBindingType(): int|ParameterType
        {
            return parent::getBindingType();
        }
    }
} else {
    trait CompatibilityType
    {
        public function getBindingType(): ParameterType
        {
            return $this->doGetBindingType();
        }

        private function doGetBindingType(): int|ParameterType
        {
            return parent::getBindingType();
        }
    }
}
