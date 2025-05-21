<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use Exception;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\Dto\SubscriberImportOptions;
use PhpList\Core\Domain\Subscription\Service\SubscriberCsvImportManager;
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
    private SubscriberCsvImportManager $importManager;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SubscriberCsvImportManager $importManager
    ) {
        parent::__construct($authentication, $validator);
        $this->importManager = $importManager;
    }

    #[Route('/import', name: 'csv', methods: ['POST'])]
    #[OA\Post(
        path: '/subscribers/import',
        description: 'Import subscribers from CSV file.',
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
                            property: 'request_confirmation',
                            description: 'Whether to request confirmation from imported subscribers',
                            type: 'boolean',
                            default: false
                        ),
                        new OA\Property(
                            property: 'html_email',
                            description: 'Whether imported subscribers prefer HTML emails',
                            type: 'boolean',
                            default: true
                        )
                    ],
                    type: 'object'
                )
            )
        ),
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
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
        $this->requireAuthentication($request);

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['success' => false, 'message' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getClientMimeType() !== 'text/csv' && $file->getClientOriginalExtension() !== 'csv') {
            return $this->json(['success' => false, 'message' => 'File must be a CSV'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $options = new SubscriberImportOptions();

            $stats = $this->importManager->importFromCsv($file, $options);

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
