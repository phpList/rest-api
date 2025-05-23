<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Service\SubscriberCsvExporter;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Subscription\Request\SubscribersExportRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/subscribers', name: 'subscriber_export_')]
class SubscriberExportController extends BaseController
{
    private SubscriberCsvExporter $exportManager;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SubscriberCsvExporter $exportManager
    ) {
        parent::__construct($authentication, $validator);
        $this->exportManager = $exportManager;
    }

    #[Route('/export', name: 'csv', methods: ['GET'])]
    #[OA\Get(
        path: '/subscribers/export',
        description: 'Export subscribers to CSV file.',
        summary: 'Export subscribers',
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'date_type',
                description: 'What date needs to be used for filtering (any, signup, changed, changelog, subscribed)',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'any',
                    enum: ['any', 'signup', 'changed', 'changelog', 'subscribed']
                )
            ),
            new OA\Parameter(
                name: 'list_id',
                description: 'List ID from where to export',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'date_from',
                description: 'Start date for filtering (format: Y-m-d)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'date_to',
                description: 'End date for filtering (format: Y-m-d)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'columns',
                description: 'Columns to include in the export (comma-separated)',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'id,email,confirmed,blacklisted,bounceCount,createdAt,updatedAt,uniqueId,htmlEmail,disabled,extraData'
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function exportSubscribers(Request $request): Response
    {
        $this->requireAuthentication($request);

        /** @var SubscribersExportRequest $exportRequest */
        $exportRequest = $this->validator->validate($request, SubscribersExportRequest::class);

        return $this->exportManager->exportToCsv($exportRequest->getDto());
    }
}
