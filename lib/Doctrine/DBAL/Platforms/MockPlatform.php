<?php
namespace Doctrine\DBAL\Platforms;

class MockPlatform extends AbstractPlatform
{
    public function getNativeDeclaration(array $field) {}
    public function getPortableDeclaration(array $field) {}

    /**
     * Get the platform name for this instance
     *
     * @return string
     */
    public function getName()
    {
        return 'mock';
    }
}