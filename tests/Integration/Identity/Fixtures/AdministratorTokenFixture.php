<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Identity\Fixtures;

use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Model\AdministratorToken;
use PhpList\Core\TestingSupport\Traits\ModelTestTrait;
use RuntimeException;

class AdministratorTokenFixture extends Fixture
{
    use ModelTestTrait;
    public function load(ObjectManager $manager): void
    {
        $csvFile = __DIR__ . '/AdministratorToken.csv';

        if (!file_exists($csvFile)) {
            throw new RuntimeException(sprintf('Fixture file "%s" not found.', $csvFile));
        }

        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open fixture file "%s".', $csvFile));
        }

        $headers = fgetcsv($handle);
        $adminRepository = $manager->getRepository(Administrator::class);

        do {
            $data = fgetcsv($handle);
            if ($data === false) {
                break;
            }
            $row = array_combine($headers, $data);

            $admin = $adminRepository->find($row['adminid']);
            if ($admin === null) {
                $admin = new Administrator();
                $this->setSubjectId($admin, (int)$row['adminid']);
                // Use a deterministic, non-conflicting login name to avoid clashes with other fixtures
                $admin->setLoginName('admin_' . $row['adminid']);
                $admin->setSuperUser(true);
                $manager->persist($admin);
            }

            $adminToken = new AdministratorToken($admin);
            $this->setSubjectId($adminToken, (int)$row['id']);
            $adminToken->setKey($row['value']);
            $manager->persist($adminToken);

            $this->setSubjectProperty($adminToken, 'expiry', new DateTime($row['expires']));
            $this->setSubjectProperty($adminToken, 'createdAt', (bool) $row['entered']);
        } while (true);

        fclose($handle);
    }
}
