<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\Core\TestingSupport\Traits\ModelTestTrait;
use RuntimeException;

class SubscribePageFixture extends Fixture
{
    use ModelTestTrait;

    public function load(ObjectManager $manager): void
    {
        $csvFile = __DIR__ . '/SubscribePage.csv';

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
            if ($row === false) {
                throw new RuntimeException('Malformed CSV data: header/data length mismatch.');
            }

            $owner = $adminRepository->find($row['owner']);
            if ($owner === null) {
                $owner = new Administrator();
                $this->setSubjectId($owner, (int)$row['owner']);
                $owner->setSuperUser(true);
                $owner->setDisabled(false);
                $manager->persist($owner);
            }

            $page = new SubscribePage();
            $this->setSubjectId($page, (int)$row['id']);
            $page->setTitle($row['title']);
            $page->setActive(filter_var($row['active'], FILTER_VALIDATE_BOOLEAN));
            $page->setOwner($owner);

            $manager->persist($page);
        } while (true);

        fclose($handle);
    }
}
