<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Messaging\Model\Attachment;
use PhpList\Core\TestingSupport\Traits\ModelTestTrait;

class AttachmentFixture extends Fixture
{
    use ModelTestTrait;

    public const ATTACHMENT_ID = 1;
    public const FILENAME = 'attachment.txt';

    public function load(ObjectManager $manager): void
    {
        $attachment = new Attachment(
            filename: self::FILENAME,
            remoteFile: null,
            mimeType: 'text/plain',
            description: 'Test attachment',
            size: null,
        );

        $this->setSubjectId($attachment, self::ATTACHMENT_ID);
        $manager->persist($attachment);
        $manager->flush();
    }
}
