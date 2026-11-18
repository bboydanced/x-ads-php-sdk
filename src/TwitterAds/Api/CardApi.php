<?php

namespace Hborras\TwitterAdsSDK\TwitterAds\Api;

use InvalidArgumentException;

/**
 * accounts/{account_id}/cards            (cards built from components, JSON body)
 * accounts/{account_id}/cards/{type}     (typed cards, e.g. video_conversation / image_conversation / poll)
 */
class CardApi extends AbstractCrudApi
{
    const TYPE_DEFAULT = '';
    const TYPE_VIDEO_CONVERSATION = 'video_conversation';
    const TYPE_IMAGE_CONVERSATION = 'image_conversation';
    const TYPE_POLL = 'poll';

    /** @var string */
    private $type = self::TYPE_DEFAULT;

    /**
     * Returns a copy bound to cards/{type}; the current instance is unchanged. '' = cards.
     */
    public function forType(string $type): self
    {
        if ($type !== '' && !preg_match('/^[a-z0-9_]+$/', $type)) {
            throw new InvalidArgumentException('Invalid card type: ' . $type);
        }
        $clone = clone $this;
        $clone->type = $type;

        return $clone;
    }

    public function getType(): string
    {
        return $this->type;
    }

    protected function collection(): string
    {
        return $this->type === '' ? 'cards' : 'cards/' . $this->type;
    }
}
