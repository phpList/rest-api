<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * This controller provides REST API access to subscribers.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SubscriberController extends AbstractController
{
    use AuthenticationTrait;

    private SubscriberRepository $subscriberRepository;
    private SerializerInterface $serializer;

    /**
     * @param Authentication $authentication
     * @param SubscriberRepository $repository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Authentication $authentication,
        SubscriberRepository $repository,
        SerializerInterface $serializer
    ) {
        $this->authentication = $authentication;
        $this->subscriberRepository = $repository;
        $this->serializer = $serializer;
    }

    /**
     * Creates a new subscriber (if the provided data is valid and there is no subscriber with the given email
     * address yet).
     */
    #[Route('/subscribers', name: 'create_subscriber', methods: ['POST'])]
    public function postAction(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);
        $data = $request->getPayload();
        $this->validateSubscriber($request);

        $email = $data->get('email');
        if ($this->subscriberRepository->findOneByEmail($email) !== null) {
            throw new ConflictHttpException('This resource already exists.', null, 1513439108);
        }

        $subscriber = new Subscriber();
        $subscriber->setEmail($email);
        $subscriber->setConfirmed((bool)$data->get('confirmed'));
        $subscriber->setBlacklisted((bool)$data->get('blacklisted'));
        $subscriber->setHtmlEmail((bool)$data->get('html_email'));
        $subscriber->setDisabled((bool)$data->get('disabled'));
        $this->subscriberRepository->save($subscriber);

        $json = $this->serializer->serialize($subscriber, 'json');

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    /**
     * @param Request $request
     *
     * @return void
     *
     * @throws UnprocessableEntityHttpException
     */
    private function validateSubscriber(Request $request): void
    {
        /** @var string[] $invalidFields */
        $invalidFields = [];
        if (filter_var($request->getPayload()->get('email'), FILTER_VALIDATE_EMAIL) === false) {
            $invalidFields[] = 'email';
        }

        $booleanFields = ['confirmed', 'blacklisted', 'html_email', 'disabled'];
        foreach ($booleanFields as $fieldKey) {
            if ($request->getPayload()->get($fieldKey) !== null && !is_bool($request->getPayload()->get($fieldKey))) {
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
