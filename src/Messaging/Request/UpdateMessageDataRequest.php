<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Subscription\Validator\Constraint\ListExists;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Campaign settings that are kept in the message data, apart from the campaign itself. Only the fields that are
 * sent are changed; an empty string clears a text setting.
 */
#[OA\Schema(
    schema: 'UpdateMessageDataRequest',
    properties: [
        new OA\Property(
            property: 'google_track',
            description: 'Add analytics tracking parameters to the links of the campaign.',
            type: 'boolean',
            example: true
        ),
        new OA\Property(property: 'utm_source', description: 'Google Analytics source.', type: 'string'),
        new OA\Property(property: 'utm_medium', description: 'Google Analytics medium.', type: 'string'),
        new OA\Property(property: 'utm_campaign', description: 'Google Analytics campaign.', type: 'string'),
        new OA\Property(property: 'pk_source', description: 'Matomo source.', type: 'string'),
        new OA\Property(property: 'pk_medium', description: 'Matomo medium.', type: 'string'),
        new OA\Property(property: 'pk_campaign', description: 'Matomo campaign.', type: 'string'),
        new OA\Property(
            property: 'notify_start',
            description: 'Email addresses, separated by a comma, to alert when sending starts.',
            type: 'string',
            example: 'admin@example.com'
        ),
        new OA\Property(
            property: 'notify_end',
            description: 'Email addresses, separated by a comma, to alert when sending finishes.',
            type: 'string',
            example: 'admin@example.com'
        ),
        new OA\Property(
            property: 'exclude_lists',
            description: 'IDs of the lists whose subscribers are left out of the campaign.',
            type: 'array',
            items: new OA\Items(type: 'integer', minimum: 1),
            example: [4, 5]
        ),
    ],
    type: 'object'
)]
class UpdateMessageDataRequest implements RequestInterface
{
    #[Assert\Type('bool')]
    public ?bool $googleTrack = null;

    #[Assert\Length(max: 255)]
    public ?string $utmSource = null;

    #[Assert\Length(max: 255)]
    public ?string $utmMedium = null;

    #[Assert\Length(max: 255)]
    public ?string $utmCampaign = null;

    #[Assert\Length(max: 255)]
    public ?string $pkSource = null;

    #[Assert\Length(max: 255)]
    public ?string $pkMedium = null;

    #[Assert\Length(max: 255)]
    public ?string $pkCampaign = null;

    #[Assert\Length(max: 255)]
    public ?string $notifyStart = null;

    #[Assert\Length(max: 255)]
    public ?string $notifyEnd = null;

    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\Sequentially([
            new Assert\Type('integer'),
            new Assert\Positive(),
            new ListExists(),
        ]),
    ])]
    public ?array $excludeLists = null;

    #[Assert\Callback]
    public function validateNotifyAddresses(ExecutionContextInterface $context): void
    {
        foreach (['notifyStart' => $this->notifyStart, 'notifyEnd' => $this->notifyEnd] as $property => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            foreach (array_map('trim', explode(',', $value)) as $address) {
                $context->getValidator()
                    ->inContext($context)
                    ->atPath($property)
                    ->validate($address, [new Assert\NotBlank(), new Assert\Email()]);
            }
        }
    }

    /**
     * The settings that were sent, by their message data name.
     *
     * @return array<string, bool|string|array<int, int>>
     */
    public function getDto(): array
    {
        return array_filter([
            'google_track' => $this->googleTrack,
            'utm_source' => $this->utmSource,
            'utm_medium' => $this->utmMedium,
            'utm_campaign' => $this->utmCampaign,
            'pk_source' => $this->pkSource,
            'pk_medium' => $this->pkMedium,
            'pk_campaign' => $this->pkCampaign,
            'notify_start' => $this->notifyStart,
            'notify_end' => $this->notifyEnd,
            // like phplist3, keyed by list id
            'excludelist' => $this->excludeLists === null
                ? null
                : array_combine($this->excludeLists, $this->excludeLists),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
