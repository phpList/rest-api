<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Attachment;
use PhpList\Core\Domain\Messaging\Service\AttachmentDownloadService;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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

    #[Route('/download/{id}', name: 'download', requirements: ['id' => '\\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/attachments/download/{id}',
        description: 'Download an attachment by ID. `uid` query parameter is required.',
        summary: 'Download attachment',
        tags: ['attachments'],
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
                description: 'Download token (subscriber email or word "forwarded")',
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
    public function download(
        #[MapEntity(mapping: ['id' => 'id'])] Attachment $attachment,
        #[MapQueryParameter] string $uid
    ): StreamedResponse {
        $downloadable = $this->attachmentDownloadService->getDownloadable($attachment, $uid);

        $headers = [
            'Content-Type' => $downloadable->mimeType,
            'Content-Disposition' => ResponseHeaderBag::makeDisposition(
                disposition: ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                fileName: $downloadable->filename
            ),
        ];

        if ($downloadable->size !== null) {
            $headers['Content-Length'] = (string) $downloadable->size;
        }

        return new StreamedResponse(
            callback: function () use ($downloadable) {
                $stream = $downloadable->content;

                if ($stream->isSeekable()) {
                    $stream->rewind();
                }

                while (!$stream->eof()) {
                    echo $stream->read(8192);
                    flush();
                }
            },
            status: 200,
            headers:  $headers
        );
    }
}
