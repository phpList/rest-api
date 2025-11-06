<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Identity\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Identity\Model\AdminAttributeDefinition;
use PhpList\Core\TestingSupport\Traits\ModelTestTrait;
use RuntimeException;

class AdminAttributeDefinitionFixture extends Fixture
{
    use ModelTestTrait;
    
    public function load(ObjectManager $manager): void
    {
        $csvFile = __DIR__ . '/AdminAttributeDefinition.csv';

        if (!file_exists($csvFile)) {
            throw new RuntimeException(sprintf('Fixture file "%s" not found.', $csvFile));
        }

        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open fixture file "%s".', $csvFile));
        }

        $headers = fgetcsv($handle);

        do {
            $data = fgetcsv($handle);
            if ($data === false) {
                break;
            }
            $row = array_combine($headers, $data);
            if ($row === false) {
                throw new RuntimeException('Malformed CSV data: header/data length mismatch.');
            }

            $definition = new AdminAttributeDefinition($row['name']);
            $this->setSubjectId($definition, (int)$row['id']);
            $definition->setType($row['type']);
            $definition->setListOrder((int)$row['list_order']);
            $definition->setDefaultValue($row['default_value']);
            $definition->setRequired((bool)$row['required']);
            $definition->setTableName($row['table_name']);

            $manager->persist($definition);
        } while (true);

        fclose($handle);
    }
}
