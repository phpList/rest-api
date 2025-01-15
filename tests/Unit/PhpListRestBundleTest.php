<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit;

use PhpList\RestBundle\PhpListRestBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class PhpListRestBundleTest extends TestCase
{
    private ?PhpListRestBundle $subject = null;

    protected function setUp(): void
    {
        $this->subject = new PhpListRestBundle();
    }

    public function testClassIsBundle()
    {
        static::assertInstanceOf(Bundle::class, $this->subject);
    }
}
