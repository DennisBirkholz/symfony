<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Tests\Simple;

use PHPUnit\Framework\SkippedTestSuiteError;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Simple\RedisCache;

/**
 * @group legacy
 */
abstract class AbstractRedisCacheTest extends CacheTestCase
{
    protected $skippedTests = [
        'testSetTtl' => 'Testing expiration slows down the test suite',
        'testSetMultipleTtl' => 'Testing expiration slows down the test suite',
        'testDefaultLifeTime' => 'Testing expiration slows down the test suite',
    ];

    protected static $redis;

    public function createSimpleCache(int $defaultLifetime = 0): CacheInterface
    {
        return new RedisCache(self::$redis, str_replace('\\', '.', __CLASS__), $defaultLifetime);
    }

    public static function setUpBeforeClass(): void
    {
        if (!\extension_loaded('redis')) {
            throw new SkippedTestSuiteError('Extension redis required.');
        }
        try {
            (new \Redis())->connect(...explode(':', getenv('REDIS_HOST')));
        } catch (\Exception $e) {
            throw new SkippedTestSuiteError($e->getMessage());
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$redis = null;
    }
}
