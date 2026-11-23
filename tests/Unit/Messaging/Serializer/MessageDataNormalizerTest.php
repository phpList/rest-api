<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Serializer;

use PhpList\RestBundle\Messaging\Dto\CampaignMessageData;
use PhpList\RestBundle\Messaging\Serializer\MessageDataNormalizer;
use PHPUnit\Framework\TestCase;

class MessageDataNormalizerTest extends TestCase
{
    private MessageDataNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new MessageDataNormalizer();
    }

    public function testSupportsNormalizationOnlyForCampaignMessageData(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization(new CampaignMessageData([])));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalizeReturnsEmptyArrayForOtherObjects(): void
    {
        $this->assertSame([], $this->normalizer->normalize(new \stdClass()));
    }

    public function testNormalizeReturnsOnlyTheSettingsWithNullForTheOnesNotStored(): void
    {
        $result = $this->normalizer->normalize(new CampaignMessageData([
            'google_track' => '1',
            'utm_source' => 'newsletter',
            'excludelist' => [4 => 4, 6 => 6],
            'subject' => 'not part of the response',
        ]));

        $this->assertSame(
            [
                'google_track' => true,
                'utm_source' => 'newsletter',
                'utm_medium' => null,
                'utm_campaign' => null,
                'pk_source' => null,
                'pk_medium' => null,
                'pk_campaign' => null,
                'notify_start' => null,
                'notify_end' => null,
                'exclude_lists' => [4, 6],
            ],
            $result
        );
    }

    public function testNormalizeHandlesEmptyData(): void
    {
        $result = $this->normalizer->normalize(new CampaignMessageData([]));

        $this->assertFalse($result['google_track']);
        $this->assertSame([], $result['exclude_lists']);
    }
}
