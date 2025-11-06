<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Fixtures;

use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use PhpList\Core\TestingSupport\Traits\ModelTestTrait;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use RuntimeException;

class SubscriberListFixture extends Fixture implements DependentFixtureInterface
{
    use ModelTestTrait;
    public function load(ObjectManager $manager): void
    {
        $csvFile = __DIR__ . '/SubscriberList.csv';

        if (!file_exists($csvFile)) {
            throw new RuntimeException(sprintf('Fixture file "%s" not found.', $csvFile));
        }

        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open fixture file "%s".', $csvFile));
        }

        $headers = fgetcsv($handle);

        $adminRepository = $manager->getRepository(Administrator::class);
        $adminsById = [];

        do {
            $data = fgetcsv($handle);
            if ($data === false) {
                break;
            }
            $row = array_combine($headers, $data);
            if ($row === false) {
                throw new RuntimeException('Malformed CSV data: header/data length mismatch.');
            }

            $ownerId = (int)$row['owner'];
            $admin = $adminsById[$ownerId] ?? $adminRepository->find($ownerId);
            if ($admin === null) {
                $admin = new Administrator();
                $this->setSubjectId($admin, $ownerId);
                // Use a deterministic, non-conflicting login name to avoid clashes with other fixtures
                $admin->setLoginName('owner_' . $ownerId);
                $admin->setSuperUser(true);
                $admin->setDisabled(false);
                $manager->persist($admin);
                $adminsById[$ownerId] = $admin;
            }

            $subscriberList = new SubscriberList();
            $this->setSubjectId($subscriberList, (int)$row['id']);
            $subscriberList->setName($row['name']);
            $subscriberList->setDescription($row['description']);
            $subscriberList->setListPosition((int)$row['listorder']);
            $subscriberList->setSubjectPrefix($row['prefix']);
            $subscriberList->setPublic((bool) $row['active']);
            $subscriberList->setCategory($row['category']);
            $subscriberList->setOwner($admin);

            $manager->persist($subscriberList);

            $this->setSubjectProperty($subscriberList, 'createdAt', new DateTime($row['entered']));
            $this->setSubjectProperty($subscriberList, 'updatedAt', new DateTime($row['modified']));
        } while (true);

        fclose($handle);
    }

    public function getDependencies(): array
    {
        return [
            AdministratorFixture::class,
        ];
    }
}
