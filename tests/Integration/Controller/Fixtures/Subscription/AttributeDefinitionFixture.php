<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Subscription;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Model\Subscription\AttributeDefinition;

class AttributeDefinitionFixture extends Fixture implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $definition = new AttributeDefinition();
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
