<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Request;

use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\RestBundle\Subscription\Request\PublicSubscriptionRequest;
use PHPUnit\Framework\TestCase;

class PublicSubscriptionRequestTest extends TestCase
{
    public function testGetDtoReturnsSelfAndTrimsEmail(): void
    {
        $request = new PublicSubscriptionRequest();
        $request->email = '  test@example.com  ';

        $dto = $request->getDto();

        $this->assertSame($request, $dto);
        $this->assertSame('test@example.com', $dto->email);
    }

    public function testGetDtoKeepsNullEmail(): void
    {
        $request = new PublicSubscriptionRequest();

        $dto = $request->getDto();

        $this->assertSame($request, $dto);
        $this->assertNull($dto->email);
        $this->assertSame([], $dto->attributes);
    }

    public function testSetSubscribePageStoresAndReturnsSelf(): void
    {
        $request = new PublicSubscriptionRequest();
        $subscribePage = new SubscribePage();

        $result = $request->setSubscribePage($subscribePage);

        $this->assertSame($request, $result);
        $this->assertSame($subscribePage, $request->getSubscribePage());
    }
}
