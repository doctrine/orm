<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Platforms\Keywords\KeywordList;

/**
 * Mock class for KeywordList.
 */
class KeywordListMock extends KeywordList
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Mock';
    }

    /**
     * {@inheritdoc}
     */
    protected function getKeywords()
    {
        return [];
    }
}
