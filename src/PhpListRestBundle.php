<?php

declare(strict_types=1);

namespace PhpList\RestBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use OpenApi\Attributes as OA;


/**
 * This bundle provides the REST API for phpList.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
#[OA\Info(
    version: "1.0.0",
    description: "This is the OpenAPI documentation for My API.",
    title: "My API Documentation",
    contact: new OA\Contact(
        email: "support@phplist.com"
    ),
    license: new OA\License(
        name: "AGPL-3.0-or-later",
        url: "https://www.gnu.org/licenses/agpl.txt"
    )
)]
#[OA\Server(
    url: "https://www.phplist.com/api/v2",
    description: "Production server"
)]
class PhpListRestBundle extends Bundle
{
}
