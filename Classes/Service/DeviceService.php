<?php

declare(strict_types=1);

namespace SyntaxOops\WebNotifications\Service;

use SyntaxOops\WebNotifications\Domain\Model\Device;
use SyntaxOops\WebNotifications\Domain\Repository\DeviceRepository;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

final readonly class DeviceService
{
    private const ALLOWED_CONTENT_ENCODINGS = ['aes128gcm', 'aesgcm'];

    public function __construct(
        private DeviceRepository $deviceRepository,
        private PersistenceManagerInterface $persistenceManager,
    ) {}

    public function addDevice(
        string $endpoint,
        string $publicKey,
        string $authToken,
        string $contentEncoding,
    ): void {
        $endpoint = trim($endpoint);
        $publicKey = trim($publicKey);
        $authToken = trim($authToken);
        $contentEncoding = trim($contentEncoding);

        if (
            filter_var($endpoint, FILTER_VALIDATE_URL) === false
            || !str_starts_with($endpoint, 'https://')
            || strlen($endpoint) > 2048
        ) {
            throw new \InvalidArgumentException('The push subscription endpoint is invalid.', 1755249870);
        }
        if ($publicKey === '' || strlen($publicKey) > 255) {
            throw new \InvalidArgumentException('The push subscription public key is invalid.', 1755249871);
        }
        if ($authToken === '' || strlen($authToken) > 255) {
            throw new \InvalidArgumentException('The push subscription authentication token is invalid.', 1755249872);
        }
        if (!in_array($contentEncoding, self::ALLOWED_CONTENT_ENCODINGS, true)) {
            throw new \InvalidArgumentException('The push subscription content encoding is not supported.', 1755249873);
        }

        $device = $this->deviceRepository->findByEndpoint($endpoint);
        $isNew = $device === null;
        if ($isNew) {
            $device = new Device();
            $device->setPid(0);
        }

        $device->setIdentifier(hash('sha256', $endpoint));
        $device->setEndpoint($endpoint);
        $device->setPublicKey($publicKey);
        $device->setAuthToken($authToken);
        $device->setContentEncoding($contentEncoding);

        if ($isNew) {
            $this->deviceRepository->add($device);
        } else {
            $this->deviceRepository->update($device);
        }

        $this->persistenceManager->persistAll();
    }
}
