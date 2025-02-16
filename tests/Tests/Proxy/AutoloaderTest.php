<?php

declare(strict_types=1);

namespace Doctrine\Tests\Proxy;

use Doctrine\ORM\Proxy\Autoloader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function class_exists;
use function file_exists;
use function file_put_contents;
use function sys_get_temp_dir;
use function unlink;

use const DIRECTORY_SEPARATOR;

class AutoloaderTest extends TestCase
{
    /** @return iterable<string, array{string, string, class-string, string}> */
    public static function dataResolveFile(): iterable
    {
        return [
            ['/tmp', 'MyProxy', 'MyProxy\RealClass', '/tmp' . DIRECTORY_SEPARATOR . 'RealClass.php'],
            ['/tmp', 'MyProxy', 'MyProxy\__CG__\RealClass', '/tmp' . DIRECTORY_SEPARATOR . '__CG__RealClass.php'],
            ['/tmp', 'MyProxy\Subdir', 'MyProxy\Subdir\__CG__\RealClass', '/tmp' . DIRECTORY_SEPARATOR . '__CG__RealClass.php'],
            ['/tmp', 'MyProxy', 'MyProxy\__CG__\Other\RealClass', '/tmp' . DIRECTORY_SEPARATOR . '__CG__OtherRealClass.php'],
        ];
    }

    /** @param class-string $className */
    #[DataProvider('dataResolveFile')]
    public function testResolveFile(
        string $proxyDir,
        string $proxyNamespace,
        string $className,
        string $expectedProxyFile,
    ): void {
        $actualProxyFile = Autoloader::resolveFile($proxyDir, $proxyNamespace, $className);
        self::assertEquals($expectedProxyFile, $actualProxyFile);
    }

    public function testAutoload(): void
    {
        if (file_exists(sys_get_temp_dir() . '/AutoloaderTestClass.php')) {
            unlink(sys_get_temp_dir() . '/AutoloaderTestClass.php');
        }

        $autoloader = Autoloader::register(sys_get_temp_dir(), 'ProxyAutoloaderTest', static function ($proxyDir, $proxyNamespace, $className): void {
            file_put_contents(sys_get_temp_dir() . '/AutoloaderTestClass.php', '<?php namespace ProxyAutoloaderTest; class AutoloaderTestClass {} ');
        });

        self::assertTrue(class_exists('ProxyAutoloaderTest\AutoloaderTestClass', true));
        unlink(sys_get_temp_dir() . '/AutoloaderTestClass.php');
    }
}
