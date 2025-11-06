<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Identity\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Model\AdminAttributeDefinition;
use PhpList\Core\Domain\Identity\Model\AdminAttributeValue;
use PhpList\Core\TestingSupport\Traits\ModelTestTrait;
use RuntimeException;

class AdminAttributeValueFixture extends Fixture implements DependentFixtureInterface
{
    use ModelTestTrait;

    public function load(ObjectManager $manager): void
    {
        $csvFile = __DIR__ . '/AdminAttributeValue.csv';

        if (!file_exists($csvFile)) {
            throw new RuntimeException(sprintf('Fixture file "%s" not found.', $csvFile));
        }

        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open fixture file "%s".', $csvFile));
        }

        $headers = fgetcsv($handle);
        $adminRepository = $manager->getRepository(Administrator::class);
        $definitionRepository = $manager->getRepository(AdminAttributeDefinition::class);

        do {
            $data = fgetcsv($handle);
            if ($data === false) {
                break;
            }
            $row = array_combine($headers, $data);

            $admin = $adminRepository->find($row['admin_id']);
            if ($admin === null) {
                throw new RuntimeException(sprintf('Administrator with ID %d not found.', $row['admin_id']));
            }

            $definition = $definitionRepository->find($row['definition_id']);
            if ($definition === null) {
                throw new RuntimeException(
                    sprintf('AdminAttributeDefinition with ID %d not found.', $row['definition_id'])
                );
            }

            $value = new AdminAttributeValue($definition, $admin);
            $value->setValue($row['value']);

            $manager->persist($value);
        } while (true);

        fclose($handle);
    }

    public function getDependencies(): array
    {
        return [
            AdministratorFixture::class,
            AdminAttributeDefinitionFixture::class,
        ];
    }
}
