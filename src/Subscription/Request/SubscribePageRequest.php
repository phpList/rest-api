<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SubscribePageRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    public string $title;

    #[Assert\Type(type: 'bool')]
    public bool $active = false;

    /**
     * @var array<int, array{key: string, value: ?string}>|null
     */
    #[Assert\Type(type: 'array')]
    #[Assert\All(constraints: [
        new Assert\Collection(
            fields: [
                'key' => new Assert\Required([
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'string'),
                ]),
                'value' => new Assert\Required([
                    new Assert\Type(type: 'string'),
                ]),
            ],
            allowExtraFields: false,
            allowMissingFields: false
        ),
    ])]
    private ?array $data = null;

    private bool $dataProvided = false;

    public function setData(?array $data): void
    {
        $this->data = $data;
        $this->dataProvided = true;
    }

    public function hasData(): bool
    {
        return $this->dataProvided;
    }

    /** @return array<int, array{key: string, value: ?string}>|null */
    public function getData(): ?array
    {
        return $this->data;
    }

    /** @return array<string, ?string> */
    public function getDataMap(): array
    {
        if ($this->data === null) {
            return [];
        }

        $result = [];
        foreach ($this->data as $item) {
            $result[$item['key']] = $item['value'];
        }

        return $result;
    }

    public function getDto(): SubscribePageRequest
    {
        return $this;
    }
}
