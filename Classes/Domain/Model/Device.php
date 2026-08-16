<?php

declare(strict_types=1);

namespace SyntaxOops\WebNotifications\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Device extends AbstractEntity
{
    protected string $identifier = '';

    protected string $endpoint = '';

    protected string $publicKey = '';

    protected string $authToken = '';

    protected string $contentEncoding = 'aes128gcm';

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    public function getAuthToken(): string
    {
        return $this->authToken;
    }

    public function setAuthToken(string $authToken): void
    {
        $this->authToken = $authToken;
    }

    public function getContentEncoding(): string
    {
        return $this->contentEncoding;
    }

    public function setContentEncoding(string $contentEncoding): void
    {
        $this->contentEncoding = $contentEncoding;
    }
}
