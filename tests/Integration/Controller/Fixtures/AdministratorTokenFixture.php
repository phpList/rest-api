<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller\Fixtures;

use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Identity\AdministratorToken;
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

        $administratorRepository = $manager->getRepository(Administrator::class);

        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $data);

            $adminToken = new AdministratorToken();
            $this->setSubjectId($adminToken,$row['id']);
            $adminToken->setKey($row['value']);
            $this->setSubjectProperty($adminToken,'expiry', new DateTime($row['expires']));
            $adminToken->setAdministrator($administratorRepository->find($row['adminid']));
            $this->setSubjectProperty($adminToken,'creationDate', new DateTime($row['created']));

            $manager->persist($adminToken);
        }

        fclose($handle);
    }
}
