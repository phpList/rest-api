<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\Filter\SubscriberFilter;
use PhpList\Core\Domain\Subscription\Service\SubscriberCsvExportManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/subscribers', name: 'subscriber_export_')]
class SubscriberExportController extends BaseController
{
    private SubscriberCsvExportManager $exportManager;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SubscriberCsvExportManager $exportManager
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
                name: 'batch_size',
                description: 'Number of subscribers to process in each batch (default: 1000)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1000)
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

        $batchSize = (int)$request->query->get('batch_size', 1000);
        
        $filter = new SubscriberFilter();
        
        return $this->exportManager->exportToCsv($filter, $batchSize);
    }
}
