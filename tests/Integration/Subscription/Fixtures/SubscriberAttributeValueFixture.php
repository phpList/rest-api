<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeDefinition;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeValue;

class SubscriberAttributeValueFixture extends Fixture implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $definition = new SubscriberAttributeDefinition();
        $definition->setName('Country');
        $definition->setType(AttributeTypeEnum::Checkbox);
        $definition->setListOrder(1);
        $definition->setDefaultValue('US');
        $definition->setRequired(true);
        $definition->setTableName('list_attributes');

        $manager->persist($definition);

        $subscriberRepository = $manager->getRepository(Subscriber::class);
        $value = new SubscriberAttributeValue($definition, $subscriberRepository->find(1));
        $value->setValue('test value');

        $manager->persist($value);

        $manager->flush();
    }
}
