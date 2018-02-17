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
    /**
     * @var PhpListRestBundle
     */
    private $subject = null;

    protected function setUp()
    {
        $this->subject = new PhpListRestBundle();
    }

    /**
     * @test
     */
    public function classIsBundle()
    {
        static::assertInstanceOf(Bundle::class, $this->subject);
    }
}
