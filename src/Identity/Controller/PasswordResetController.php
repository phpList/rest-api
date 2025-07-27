<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Service\PasswordManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Identity\Request\RequestPasswordResetRequest;
use PhpList\RestBundle\Identity\Request\ResetPasswordRequest;
use PhpList\RestBundle\Identity\Request\ValidateTokenRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides methods to reset admin passwords.
 */
#[Route('/password-reset', name: 'password_reset_')]
class PasswordResetController extends BaseController
{
    private PasswordManager $passwordManager;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        PasswordManager $passwordManager,
    ) {
        parent::__construct($authentication, $validator);

        $this->passwordManager = $passwordManager;
    }

    #[Route('/request', name: 'request', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/password-reset/request',
        description: 'Request a password reset token for an administrator account.',
        summary: 'Request a password reset.',
        requestBody: new OA\RequestBody(
            description: 'Administrator email',
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                ]
            )
        ),
        tags: ['password-reset'],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Password reset token generated',
            ),
            new OA\Response(
                response: 400,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function requestPasswordReset(Request $request): JsonResponse
    {
        /** @var RequestPasswordResetRequest $resetRequest */
        $resetRequest = $this->validator->validate($request, RequestPasswordResetRequest::class);
        
        $this->passwordManager->generatePasswordResetToken($resetRequest->email);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/validate', name: 'validate', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/password-reset/validate',
        description: 'Validate a password reset token.',
        summary: 'Validate a password reset token.',
        requestBody: new OA\RequestBody(
            description: 'Password reset token',
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'a1b2c3d4e5f6'),
                ]
            )
        ),
        tags: ['password-reset'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'valid', type: 'boolean', example: true),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
            )
        ]
    )]
    public function validateToken(Request $request): JsonResponse
    {
        /** @var ValidateTokenRequest $validateRequest */
        $validateRequest = $this->validator->validate($request, ValidateTokenRequest::class);
        
        $administrator = $this->passwordManager->validatePasswordResetToken($validateRequest->token);

        return $this->json([ 'valid' => $administrator !== null]);
    }

    #[Route('/reset', name: 'reset', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/password-reset/reset',
        description: 'Reset an administrator password using a token.',
        summary: 'Reset password with token.',
        requestBody: new OA\RequestBody(
            description: 'Password reset information',
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'newPassword'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'a1b2c3d4e5f6'),
                    new OA\Property(
                        property: 'newPassword',
                        type: 'string',
                        format: 'password',
                        example: 'newSecurePassword123',
                    ),
                ]
            )
        ),
        tags: ['password-reset'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Password updated successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid or expired token',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
            )
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        /** @var ResetPasswordRequest $resetRequest */
        $resetRequest = $this->validator->validate($request, ResetPasswordRequest::class);
        
        $success = $this->passwordManager->updatePasswordWithToken(
            $resetRequest->token,
            $resetRequest->newPassword
        );
        
        if ($success) {
            return $this->json([ 'message' => 'Password updated successfully']);
        }

        return $this->json(['message' => 'Invalid or expired token'], Response::HTTP_BAD_REQUEST);
    }
}
