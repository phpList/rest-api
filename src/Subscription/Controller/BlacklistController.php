<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscriberBlacklistManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Subscription\Request\AddToBlacklistRequest;
use PhpList\RestBundle\Subscription\Serializer\UserBlacklistNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API access to subscriber blacklist functionality.
 */
#[Route('/blacklist', name: 'blacklist_')]
class BlacklistController extends BaseController
{
    private SubscriberBlacklistManager $blacklistManager;
    private UserBlacklistNormalizer $normalizer;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SubscriberBlacklistManager $blacklistManager,
        UserBlacklistNormalizer $normalizer,
    ) {
        parent::__construct($authentication, $validator);
        $this->authentication = $authentication;
        $this->blacklistManager = $blacklistManager;
        $this->normalizer = $normalizer;
    }

    #[Route('/check/{email}', name: 'check', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/blacklist/check/{email}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Check if email is blacklisted',
        tags: ['blacklist'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'email',
                description: 'Email address to check',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'blacklisted', type: 'boolean'),
                        new OA\Property(property: 'reason', type: 'string', nullable: true)
                    ]
                ),
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
        ]
    )]
    public function checkEmailBlacklisted(Request $request, string $email): JsonResponse
    {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to check blacklisted emails.');
        }

        $isBlacklisted = $this->blacklistManager->isEmailBlacklisted($email);
        $reason = $isBlacklisted ? $this->blacklistManager->getBlacklistReason($email) : null;

        return $this->json([
            'blacklisted' => $isBlacklisted,
            'reason' => $reason,
        ]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/blacklist/add',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Adds an email address to the blacklist.',
        requestBody: new OA\RequestBody(
            description: 'Email to blacklist',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'reason', type: 'string', nullable: true)
                ]
            )
        ),
        tags: ['blacklist'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string')
                    ]
                ),
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function addEmailToBlacklist(Request $request): JsonResponse
    {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to add emails to blacklist.');
        }

        /** @var AddToBlacklistRequest $definitionRequest */
        $definitionRequest = $this->validator->validate($request, AddToBlacklistRequest::class);

        $userBlacklisted = $this->blacklistManager->addEmailToBlacklist($definitionRequest->email, $definitionRequest->reason);
        $json = $this->normalizer->normalize($userBlacklisted, 'json');

        return $this->json($json, Response::HTTP_CREATED);
    }

    #[Route('/remove/{email}', name: 'remove', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/blacklist/remove/{email}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Removes an email address from the blacklist.',
        tags: ['blacklist'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'email',
                description: 'Email address to remove from blacklist',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string')
                    ]
                ),
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
        ]
    )]
    public function removeEmailFromBlacklist(Request $request, string $email): JsonResponse
    {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to remove emails from blacklist.');
        }

        $this->blacklistManager->removeEmailFromBlacklist($email);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/info/{email}', name: 'info', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/blacklist/info/{email}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Gets detailed information about a blacklisted email.',
        tags: ['blacklist'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'email',
                description: 'Email address to get information for',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'added', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(property: 'reason', type: 'string', nullable: true)
                    ]
                ),
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')
            ),
        ]
    )]
    public function getBlacklistInfo(Request $request, string $email): JsonResponse
    {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to view blacklist information.');
        }

        $blacklistInfo = $this->blacklistManager->getBlacklistInfo($email);
        if (!$blacklistInfo) {
            return $this->json([
                'error' => sprintf('Email %s is not blacklisted', $email)
            ], Response::HTTP_NOT_FOUND);
        }

        $reason = $this->blacklistManager->getBlacklistReason($email);

        return $this->json([
            'email' => $blacklistInfo->getEmail(),
            'added' => $blacklistInfo->getAdded()?->format('c'),
            'reason' => $reason,
        ]);
    }
}
