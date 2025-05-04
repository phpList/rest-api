<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Model\Messaging\Template;
use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Repository\Messaging\TemplateRepository;
use PhpList\RestBundle\Entity\Dto\ValidationContext;
use PhpList\RestBundle\Entity\Request\CreateTemplateRequest;
use PhpList\RestBundle\Entity\Request\UpdateSubscriberRequest;
use PhpList\RestBundle\Validator\TemplateImageValidator;
use PhpList\RestBundle\Validator\TemplateLinkValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TemplateManager
{
    private TemplateRepository $templateRepository;
    private EntityManagerInterface $entityManager;
    private TemplateImageManager $templateImageManager;
    private TemplateLinkValidator $templateLinkValidator;
    private TemplateImageValidator $templateImageValidator;

    public function __construct(
        TemplateRepository $templateRepository,
        EntityManagerInterface $entityManager,
        TemplateImageManager $templateImageManager,
        TemplateLinkValidator $templateLinkValidator,
        TemplateImageValidator $templateImageValidator
    ) {
        $this->templateRepository = $templateRepository;
        $this->entityManager = $entityManager;
        $this->templateImageManager = $templateImageManager;
        $this->templateLinkValidator = $templateLinkValidator;
        $this->templateImageValidator = $templateImageValidator;
    }

    public function create(CreateTemplateRequest $request): Template
    {
        $template = (new Template($request->title))
            ->setContent($request->content)
            ->setText($request->text);

        if ($request->file instanceof UploadedFile) {
            $template->setContent(file_get_contents($request->file->getPathname()));
        }

        $context = (new ValidationContext())
            ->set('checkLinks', $request->checkLinks)
            ->set('checkImages', $request->checkImages)
            ->set('checkExternalImages', $request->checkExternalImages);

        $this->templateLinkValidator->validate($template->getContent() ?? '', $context);

        $imageUrls = $this->templateImageManager->extractAllImages($template->getContent() ?? '');
        $this->templateImageValidator->validate($imageUrls, $context);

        $this->templateRepository->save($template);

        $this->templateImageManager->createImagesFromImagePaths($imageUrls, $template);

        return $template;
    }

    public function update(UpdateSubscriberRequest $subscriberRequest): Subscriber
    {
        /** @var Subscriber $subscriber */
        $subscriber = $this->templateRepository->find($subscriberRequest->subscriberId);

        $subscriber->setEmail($subscriberRequest->email);
        $subscriber->setConfirmed($subscriberRequest->confirmed);
        $subscriber->setBlacklisted($subscriberRequest->blacklisted);
        $subscriber->setHtmlEmail($subscriberRequest->htmlEmail);
        $subscriber->setDisabled($subscriberRequest->disabled);
        $subscriber->setExtraData($subscriberRequest->additionalData);

        $this->entityManager->flush();

        return $subscriber;
    }

    public function delete(Template $template): void
    {
        $this->templateRepository->remove($template);
    }
}
