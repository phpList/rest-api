<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Service;

use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Subscription\Model\Dto\DynamicListAttrDto;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\Core\Domain\Subscription\Model\SubscribePageData;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeDefinition;
use PhpList\Core\Domain\Subscription\Repository\SubscriberAttributeDefinitionRepository;
use PhpList\RestBundle\Subscription\Service\PublicSubscriptionAttributeRuleProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PublicSubscriptionAttributeRuleProviderTest extends TestCase
{
    private SubscriberAttributeDefinitionRepository&MockObject $repository;
    private PublicSubscriptionAttributeRuleProvider $provider;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SubscriberAttributeDefinitionRepository::class);
        $this->provider = new PublicSubscriptionAttributeRuleProvider($this->repository);
    }

    public function testBuildsRulesWithModernOverridesAndAllowedOptions(): void
    {
        $page = (new SubscribePage())->setData([
            (new SubscribePageData())->setId(1)->setName('attributes')->setData('1'),
            (new SubscribePageData())->setId(1)->setName('attribute_1_required')->setData('1'),
            (new SubscribePageData())->setId(1)->setName('attribute_1_maxlength')->setData('5'),
        ]);

        $definition = $this->createMock(SubscriberAttributeDefinition::class);
        $definition->method('getId')->willReturn(1);
        $definition->method('getName')->willReturn('Country');
        $definition->method('getType')->willReturn(AttributeTypeEnum::Select);
        $definition->method('isRequired')->willReturn(false);
        $definition->method('getOptions')->willReturn([
            new DynamicListAttrDto(10, 'Armenia', 1),
            new DynamicListAttrDto(11, 'France', 2),
        ]);

        $this->repository->expects($this->once())->method('getByIds')->with([1])->willReturn([$definition]);

        $rules = $this->provider->getRules($page);

        $this->assertArrayHasKey('country', $rules);
        $this->assertTrue($rules['country']['required']);
        $this->assertSame(5, $rules['country']['max_length']);
        $this->assertArrayHasKey('10', $rules['country']['allowed']);
        $this->assertArrayHasKey('11', $rules['country']['allowed']);
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

        $this->repository->expects($this->once())->method('getByIds')->with([2])->willReturn([$definition]);

        $rules = $this->provider->getRules($page);

        $this->assertSame([], $rules);
    }
}

