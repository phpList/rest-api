<?php
declare(strict_types=1);

namespace PhpList\RestBundle\ViewHandler;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This class is used to add headers to the default response.
 *
 * @author Xheni Myrtaj <xheni@phplist.com>
 */
class SecuredViewHandler
{
    /**
     * @param ViewHandler $viewHandler
     * @param View $view
     * @param Request $request
     * @param string $format
     *
     * @return Response
     */
    public function createResponse(ViewHandler $handler, View $view, Request $request, string $format): Response
    {
        $view->setHeaders(
            [
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'",
                'X-Frame-Options' => 'DENY',
            ]
        );

        return $handler->createResponse($view, $request, $format);
    }
}
