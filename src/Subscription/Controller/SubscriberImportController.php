<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use Exception;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Domain\Subscription\Model\Dto\SubscriberImportOptions;
use PhpList\Core\Domain\Subscription\Service\SubscriberCsvImporter;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/subscribers', name: 'subscriber_import_')]
class SubscriberImportController extends BaseController
{
    private SubscriberCsvImporter $importManager;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SubscriberCsvImporter $importManager
    ) {
        parent::__construct($authentication, $validator);
        $this->importManager = $importManager;
    }

    #[Route('/import', name: 'csv', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/subscribers/import',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Import subscribers from CSV file.',
        summary: 'Import subscribers',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'file',
                            description: 'CSV file with subscribers data',
                            type: 'string',
                            format: 'binary'
                        ),
                        new OA\Property(
                            property: 'list_id',
                            description: 'List id to add imported subscribers to',
                            type: 'integer',
                            default: null
                        ),
                        new OA\Property(
                            property: 'update_existing',
                            description: 'Weather to update existing subscribers or not',
                            type: 'boolean',
                            default: false
                        )
                    ],
                    type: 'object'
                )
            )
        ),
        tags: ['subscribers'],
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
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'imported', type: 'integer'),
                        new OA\Property(property: 'skipped', type: 'integer'),
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function importSubscribers(Request $request): JsonResponse
    {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to create subscribers.');
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['message' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getClientMimeType() !== 'text/csv' && $file->getClientOriginalExtension() !== 'csv') {
            return $this->json(['message' => 'File must be a CSV'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $stats = $this->importManager->importFromCsv(
                $file,
                new SubscriberImportOptions($request->getPayload()->getBoolean('update_existing'))
            );

            return $this->json([
                'imported' => $stats['created'],
                'skipped' => $stats['skipped'],
                'errors' => $stats['errors']
            ]);
        } catch (Exception $e) {
            return $this->json([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
