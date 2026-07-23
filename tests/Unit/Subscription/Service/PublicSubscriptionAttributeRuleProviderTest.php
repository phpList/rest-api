<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Service;

use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\Core\Domain\Subscription\Model\SubscribePageData;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeDefinition;
use PhpList\Core\Domain\Subscription\Repository\SubscriberAttributeDefinitionRepository;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscribePageManager;
use PhpList\RestBundle\Subscription\Service\PublicSubscriptionAttributeRuleProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PublicSubscriptionAttributeRuleProviderTest extends TestCase
{
    private SubscriberAttributeDefinitionRepository&MockObject $repository;
    private PublicSubscriptionAttributeRuleProvider $provider;
    private SubscribePageManager&MockObject $subscribePageManager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SubscriberAttributeDefinitionRepository::class);
        $this->subscribePageManager = $this->createMock(SubscribePageManager::class);
        $this->provider = new PublicSubscriptionAttributeRuleProvider(
            attributeDefinitionRepository: $this->repository,
            subscribePageManager: $this->subscribePageManager
        );
    }

    public function testExcludesAttributesDisabledInLegacyOverride(): void
    {
        $page = (new SubscribePage())->setData([
            (new SubscribePageData())->setId(1)->setName('attributes')->setData('2'),
            (new SubscribePageData())->setId(1)->setName('attribute002')->setData('1###default###0###1'),
        ]);

        $definition = $this->createMock(SubscriberAttributeDefinition::class);
        $definition->method('getId')->willReturn(2);
        $definition->method('getName')->willReturn('State');
        $definition->method('getType')->willReturn(AttributeTypeEnum::TextLine);
        $definition->method('isRequired')->willReturn(true);
        $definition->method('getOptions')->willReturn([]);

        $this->repository->expects($this->once())
            ->method('getByIds')
            ->with([2])
            ->willReturn([$definition]);

        $rules = $this->provider->getRules($page);

        $this->assertSame([
            'state' => [
                'id' => 2,
                'key' => 'state',
                'type' => AttributeTypeEnum::TextLine,
                'required' => true,
                'allowed' => [],
            ]
        ], $rules);
    }
}
