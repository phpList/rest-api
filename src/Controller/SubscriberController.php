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
     * @OA\Post(
     *     path="/subscriber",
     *     tags={"subscribers"},
     *     summary="Create a subscriber list",
     *     description="Creates a new subscriber (if the provided data is valid and there is no subscriber with the given email
     *     address yet).",
     * @OA\Parameter(
     *          name="session",
     *          in="header",
     *          description="Session ID obtained from authentication",
     *          required=true,
     * @OA\Schema(
     *             type="string"
     *         )
     *      ),
     * @OA\RequestBody(
     *        required=true,
     *        description="Pass session credentials",
     * @OA\JsonContent(
     *           required={"email"},
     * @OA\Property(property="email", type="string", format="string", example="admin"),
     * @OA\Property(property="confirmed", type="string", format="boolean", example="eetIc/Gropvoc1"),
     * @OA\Property(property="blacklisted", type="string", format="boolean", example="eetIc/Gropvoc1"),
     * @OA\Property(property="html_entail", type="string", format="boolean", example="eetIc/Gropvoc1"),
     * @OA\Property(property="disabled", type="string", format="boolean", example="eetIc/Gropvoc1"),
     *        ),
     *     ),
     * @OA\Response(
     *        response=201,
     *        description="Success",
     * @OA\JsonContent(
     * @OA\Property(property="creation_date", type="integer", example="2017-12-16T18:44:27+00:00"),
     * @OA\Property(property="email", type="string", example="subscriber@example.com"),
     * @OA\Property(property="confirmed", type="boolean", example="false"),
     * @OA\Property(property="blacklisted", type="boolean", example="false"),
     * @OA\Property(property="bounced", type="integer", example="0"),
     * @OA\Property(property="unique_id", type="string", example="69f4e92cf50eafca9627f35704f030f4"),
     * @OA\Property(property="html_entail", type="boolean", example="false"),
     * @OA\Property(property="disabled", type="boolean", example="false"),
     * @OA\Property(property="id", type="integer", example="1")
     *        )
     *     ),
     * @OA\Response(
     *        response=403,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="No valid session key was provided as basic auth password.")
     *        )
     *     ),
     * @OA\Response(
     *        response="409",
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="This resource already exists.")
     *        )
     *     ),
     * @OA\Response(
     *        response="422",
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Some fields invalid: email, confirmed, html_email")
     *        )
     *     )
     * )
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
