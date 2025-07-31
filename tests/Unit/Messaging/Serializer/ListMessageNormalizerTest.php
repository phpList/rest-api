<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Serializer;

use DateTime;
use PhpList\Core\Domain\Messaging\Model\ListMessage;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use PhpList\RestBundle\Messaging\Serializer\ListMessageNormalizer;
use PhpList\RestBundle\Messaging\Serializer\MessageNormalizer;
use PhpList\RestBundle\Subscription\Serializer\SubscriberListNormalizer;
use PHPUnit\Framework\TestCase;

class ListMessageNormalizerTest extends TestCase
{
    private ListMessageNormalizer $normalizer;
    private MessageNormalizer $messageNormalizer;
    private SubscriberListNormalizer $subscriberListNormalizer;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->messageNormalizer = $this->createMock(MessageNormalizer::class);
        $this->subscriberListNormalizer = $this->createMock(SubscriberListNormalizer::class);
        $this->normalizer = new ListMessageNormalizer(
            $this->messageNormalizer,
            $this->subscriberListNormalizer
        );
    }

    public function testSupportsNormalization(): void
    {
        $listMessage = $this->createMock(ListMessage::class);
        $this->assertTrue($this->normalizer->supportsNormalization($listMessage));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalizeReturnsExpectedArray(): void
    {
        $id = 123;
        $entered = new DateTime('2023-01-01T10:00:00+00:00');
        $updatedAt = new DateTime('2023-01-02T10:00:00+00:00');
        
        $message = $this->createMock(Message::class);
        $subscriberList = $this->createMock(SubscriberList::class);
        
        $listMessage = $this->createMock(ListMessage::class);
        $listMessage->method('getId')->willReturn($id);
        $listMessage->method('getMessage')->willReturn($message);
        $listMessage->method('getList')->willReturn($subscriberList);
        $listMessage->method('getEntered')->willReturn($entered);
        $listMessage->method('getUpdatedAt')->willReturn($updatedAt);
        
        $normalizedMessage = ['id' => 456, 'subject' => 'Test Message'];
        $normalizedList = ['id' => 789, 'name' => 'Test List'];
        
        $this->messageNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->identicalTo($message))
            ->willReturn($normalizedMessage);
            
        $this->subscriberListNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->identicalTo($subscriberList))
            ->willReturn($normalizedList);
        
        $result = $this->normalizer->normalize($listMessage);
        
        $this->assertSame($id, $result['id']);
        $this->assertSame($normalizedMessage, $result['message']);
        $this->assertSame($normalizedList, $result['subscriber_list']);
        $this->assertSame('2023-01-01T10:00:00+00:00', $result['created_at']);
        $this->assertSame('2023-01-02T10:00:00+00:00', $result['updated_at']);
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->normalizer->normalize(new \stdClass()));
    }
}
