<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Serializer;

use PhpList\RestBundle\Subscription\Request\SubscribersExportRequest;
use PhpList\RestBundle\Subscription\Serializer\SubscribersExportRequestNormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;

class SubscribersExportRequestNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = new SubscribersExportRequestNormalizer();
        $request = $this->createMock(SubscribersExportRequest::class);

        $this->assertTrue($normalizer->supportsNormalization($request));
        $this->assertFalse($normalizer->supportsNormalization(new stdClass()));
    }

    public function testNormalize(): void
    {
        $request = new SubscribersExportRequest();
        $request->dateType = 'signup';
        $request->listId = 123;
        $request->dateFrom = '2023-01-01';
        $request->dateTo = '2023-12-31';
        $request->columns = ['id', 'email', 'confirmed'];

        $normalizer = new SubscribersExportRequestNormalizer();

        $expected = [
            'date_type' => 'signup',
            'list_id' => 123,
            'date_from' => '2023-01-01',
            'date_to' => '2023-12-31',
            'columns' => ['id', 'email', 'confirmed'],
        ];

        $this->assertSame($expected, $normalizer->normalize($request));
    }

    public function testNormalizeWithDefaultValues(): void
    {
        $request = new SubscribersExportRequest();

        $normalizer = new SubscribersExportRequestNormalizer();

        $expected = [
            'date_type' => 'any',
            'list_id' => null,
            'date_from' => null,
            'date_to' => null,
            'columns' => [
                'id',
                'email',
                'confirmed',
                'blacklisted',
                'bounceCount',
                'createdAt',
                'updatedAt',
                'uniqueId',
                'htmlEmail',
                'disabled',
                'extraData'
            ],
        ];

        $this->assertSame($expected, $normalizer->normalize($request));
    }

    public function testNormalizeWithInvalidObject(): void
    {
        $normalizer = new SubscribersExportRequestNormalizer();
        $this->assertSame([], $normalizer->normalize(new stdClass()));
    }
}
