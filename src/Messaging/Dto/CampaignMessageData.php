<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Dto;

/**
 * The message data of a campaign, as loaded by core's MessageDataLoader.
 */
class CampaignMessageData
{
    /** @param array<string, mixed> $values */
    public function __construct(public readonly array $values)
    {
    }
}
