<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Tests\Adapter;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * @group time-sensitive
 */
class FilesystemAdapterTest extends AdapterTestCase
{
    public function createCachePool(int $defaultLifetime = 0): CacheItemPoolInterface
    {
        return new FilesystemAdapter('', $defaultLifetime);
    }

    public static function tearDownAfterClass(): void
    {
        self::rmdir(sys_get_temp_dir().'/symfony-cache');
    }

    public static function rmdir(string $dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        if (!$dir || !str_starts_with(\dirname($dir), sys_get_temp_dir())) {
            throw new \Exception(__METHOD__."() operates only on subdirs of system's temp dir");
        }
        $children = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($children as $child) {
            if ($child->isDir()) {
                rmdir($child);
            } else {
                unlink($child);
            }
        }
        rmdir($dir);
    }

    protected function isPruned(CacheItemPoolInterface $cache, string $name): bool
    {
        $getFileMethod = (new \ReflectionObject($cache))->getMethod('getFile');
        $getFileMethod->setAccessible(true);

        return !file_exists($getFileMethod->invoke($cache, $name));
    }
}
