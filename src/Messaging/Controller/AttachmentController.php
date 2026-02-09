<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Attachment;
use PhpList\Core\Domain\Messaging\Service\AttachmentDownloadService;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/attachments', name: 'attachments_')]
class AttachmentController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly AttachmentDownloadService $attachmentDownloadService,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('/{id}/download', name: 'download', requirements: ['id' => '\\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/attachments/{id}/download',
        description: 'Download an attachment by ID. `uid` query parameter is required.',
        summary: 'Download attachment',
        tags: ['campaigns'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Attachment ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'uid',
                description: 'Download token',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File stream'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function download(Attachment $attachment): BinaryFileResponse
    {
        $this->attachmentDownloadService->getDownloadable($attachment);
    }
}
