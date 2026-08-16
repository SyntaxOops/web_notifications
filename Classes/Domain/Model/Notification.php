<?php

declare(strict_types=1);

namespace SyntaxOops\WebNotifications\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

final class Notification extends AbstractEntity
{
    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_SENT = 2;
    public const STATUS_FAILED = 3;

    protected string $title = '';

    protected string $bodytext = '';

    protected ?FileReference $media = null;

    protected string $url = '';

    protected int $status = self::STATUS_PENDING;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBodytext(): string
    {
        return $this->bodytext;
    }

    public function setBodytext(string $bodytext): void
    {
        $this->bodytext = $bodytext;
    }

    public function getMedia(): ?FileReference
    {
        return $this->media;
    }

    public function setMedia(?FileReference $media): void
    {
        $this->media = $media;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }
}
