<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Controller;

use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Messaging\Template;
use PhpList\Core\Domain\Repository\Messaging\TemplateRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\TemplateController;
use PhpList\RestBundle\Entity\Request\CreateTemplateRequest;
use PhpList\RestBundle\Serializer\TemplateNormalizer;
use PhpList\RestBundle\Service\Manager\TemplateManager;
use PhpList\RestBundle\Validator\RequestValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class TemplateControllerTest extends TestCase
{
    private TemplateRepository&MockObject $templateRepository;
    private TemplateNormalizer&MockObject $normalizer;
    private RequestValidator&MockObject $validator;
    private TemplateManager&MockObject $templateManager;
    private TemplateController $controller;

    protected function setUp(): void
    {
        $authentication = $this->createMock(Authentication::class);
        $authentication->method('authenticateByApiKey')->willReturn(new Administrator());
        $this->templateRepository = $this->createMock(TemplateRepository::class);
        $this->normalizer = $this->createMock(TemplateNormalizer::class);
        $this->validator = $this->createMock(RequestValidator::class);
        $this->templateManager = $this->createMock(TemplateManager::class);

        $this->controller = new TemplateController(
            $authentication,
            $this->templateRepository,
            $this->normalizer,
            $this->validator,
            $this->templateManager
        );
    }

    public function testGetTemplatesReturnsTemplates(): void
    {
        $request = $this->createMock(Request::class);

        $template = $this->createMock(Template::class);

        $this->templateRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$template]);

        $this->normalizer->expects($this->once())
            ->method('normalize')
            ->with($template)
            ->willReturn(['id' => 1, 'title' => 'Test Template']);

        $response = $this->controller->getTemplates($request);

        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals([['id' => 1, 'title' => 'Test Template']], $data);
    }

    public function testGetTemplateReturnsSingleTemplate(): void
    {
        $request = $this->createMock(Request::class);

        $template = $this->createMock(Template::class);

        $this->normalizer->expects($this->once())
            ->method('normalize')
            ->with($template)
            ->willReturn(['id' => 1, 'title' => 'Single Template']);

        $response = $this->controller->getTemplate($request, $template);

        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['id' => 1, 'title' => 'Single Template'], $data);
    }

    public function testCreateTemplateReturnsCreatedTemplate(): void
    {
        $request = $this->createMock(Request::class);

        $createTemplateRequest = $this->createMock(CreateTemplateRequest::class);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($request, CreateTemplateRequest::class)
            ->willReturn($createTemplateRequest);

        $template = $this->createMock(Template::class);

        $this->templateManager->expects($this->once())
            ->method('create')
            ->with($createTemplateRequest)
            ->willReturn($template);

        $this->normalizer->expects($this->once())
            ->method('normalize')
            ->with($template)
            ->willReturn(['id' => 1, 'title' => 'Created Template']);

        $response = $this->controller->createTemplates($request);

        $this->assertSame(201, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['id' => 1, 'title' => 'Created Template'], $data);
    }
}
