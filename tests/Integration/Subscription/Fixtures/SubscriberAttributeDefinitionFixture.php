<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeDefinition;

class SubscriberAttributeDefinitionFixture extends Fixture implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $definition = new SubscriberAttributeDefinition();
        $definition->setName('Country');
        $definition->setType('checkbox');
        $definition->setListOrder(1);
        $definition->setDefaultValue('US');
        $definition->setRequired(true);
        $definition->setTableName('list_attributes');

        $manager->persist($definition);
        $manager->flush();
    }
}
