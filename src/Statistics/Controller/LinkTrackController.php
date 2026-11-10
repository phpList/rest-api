<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Analytics\Service\LinkClickService;
use PhpList\Core\Domain\Identity\Service\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Public endpoint behind the links of sent campaigns, the equivalent of phplist3's lt.php.
 */
class LinkTrackController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly LinkClickService $linkClickService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('/lt', name: 'link_track', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/lt',
        description: 'Click-tracking endpoint that campaign links point to. Records the click for the subscriber'
        . ' and redirects to the original url. Requires the `tid` token that is part of the link, and `hm` when'
        . ' signed links are enabled.',
        summary: 'Track a link click and redirect',
        tags: ['tracking'],
        parameters: [
            new OA\Parameter(
                name: 'tid',
                description: 'Token identifying the link, the campaign and the subscriber',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'hm',
                description: 'Signature of the token, required when signed links are enabled',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 303, description: 'Redirect to the original url'),
            new OA\Response(response: 404, description: 'Invalid token or unknown link'),
        ]
    )]
    public function trackLinkClick(
        Request $request,
        #[MapQueryParameter(name: 'tid')] ?string $token = null,
        #[MapQueryParameter(name: 'hm')] ?string $signature = null,
    ): Response {
        if ($token === null || $token === '') {
            return $this->notFound();
        }

        $metadata = [
            'HTTP_USER_AGENT' => $request->server->get('HTTP_USER_AGENT'),
            'HTTP_REFERER' => $request->server->get('HTTP_REFERER'),
            'client_ip' => $request->getClientIp(),
        ];

        try {
            $url = $this->linkClickService->trackClick($token, $signature, $metadata);
        } catch (Throwable $e) {
            $this->logger->error('Failed to track link click', ['exception' => $e]);

            return $this->notFound();
        }

        if ($url === null) {
            return $this->notFound();
        }

        try {
            $this->entityManager->flush();
        } catch (Throwable $e) {
            $this->logger->error('Failed to store link click', ['exception' => $e]);
        }

        $response = new RedirectResponse($url, Response::HTTP_SEE_OTHER);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }

    private function notFound(): Response
    {
        return new Response('Not found', Response::HTTP_NOT_FOUND, ['Content-Type' => 'text/plain']);
    }
}
