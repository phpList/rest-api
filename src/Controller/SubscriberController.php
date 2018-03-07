<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * This controller provides REST API access to subscribers.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SubscriberController extends FOSRestController implements ClassResourceInterface
{
    use AuthenticationTrait;

    /**
     * @var SubscriberRepository
     */
    private $subscriberRepository = null;

    /**
     * @param Authentication $authentication
     * @param SubscriberRepository $repository
     */
    public function __construct(Authentication $authentication, SubscriberRepository $repository)
    {
        $this->authentication = $authentication;
        $this->subscriberRepository = $repository;
    }

    /**
     * Creates a new subscriber (if the provided data is valid and there is no subscriber with the given email
     * address yet).
     *
     * @param Request $request
     *
     * @return View
     *
     * @throws ConflictHttpException
     */
    public function postAction(Request $request): View
    {
        $this->requireAuthentication($request);

        $this->validateSubscriber($request);

        $email = $request->get('email');
        if ($this->subscriberRepository->findOneByEmail($email) !== null) {
            throw new ConflictHttpException('This resource already exists.', null, 1513439108);
        }

        $subscriber = new Subscriber();
        $subscriber->setEmail($email);
        $subscriber->setConfirmed((bool)$request->get('confirmed'));
        $subscriber->setBlacklisted((bool)$request->get('blacklisted'));
        $subscriber->setHtmlEmail((bool)$request->get('html_email'));
        $subscriber->setDisabled((bool)$request->get('disabled'));
        $this->subscriberRepository->save($subscriber);

        return View::create()->setStatusCode(Response::HTTP_CREATED)->setData($subscriber);
    }

    /**
     * @param Request $request
     *
     * @return void
     *
     * @throws UnprocessableEntityHttpException
     */
    private function validateSubscriber(Request $request)
    {
        /** @var string[] $invalidFields */
        $invalidFields = [];
        if (filter_var($request->get('email'), FILTER_VALIDATE_EMAIL) === false) {
            $invalidFields[] = 'email';
        }

        $booleanFields = ['confirmed', 'blacklisted', 'html_email', 'disabled'];
        foreach ($booleanFields as $fieldKey) {
            if ($request->get($fieldKey) !== null && !is_bool($request->get($fieldKey))) {
                $invalidFields[] = $fieldKey;
            }
        }

        if (!empty($invalidFields)) {
            throw new UnprocessableEntityHttpException(
                'Some fields invalid:' . implode(', ', $invalidFields),
                null,
                1513446736
            );
        }
    }
}
