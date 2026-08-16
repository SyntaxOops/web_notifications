<?php

declare(strict_types=1);

namespace SyntaxOops\WebNotifications\Service;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerInterface;
use SyntaxOops\WebNotifications\Domain\Model\Device;
use SyntaxOops\WebNotifications\Domain\Model\Notification;
use SyntaxOops\WebNotifications\Domain\Repository\DeviceRepository;
use SyntaxOops\WebNotifications\Domain\Repository\NotificationRepository;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Service\ImageService;

final class SendNotificationService
{
    private const DEFAULT_IMAGE_MAX_WIDTH = 290;
    private const DEFAULT_IMAGE_MAX_HEIGHT = 290;

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly DeviceRepository $deviceRepository,
        private readonly ImageService $imageService,
        private readonly NotificationRepository $notificationRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly LinkService $linkService,
        private readonly SiteFinder $siteFinder,
        LogManager $logManager,
    ) {
        $this->logger = $logManager->getLogger(self::class);
    }

    public function sendNotification(int $folderUid): bool
    {
        $site = $this->resolveSite($folderUid);
        $webPush = $this->createWebPush($site);
        $allSuccessful = true;

        foreach ($this->notificationRepository->findPendingByPid($folderUid) as $notification) {
            $this->setNotificationStatus($notification, Notification::STATUS_PROCESSING);

            try {
                $successful = $this->send($webPush, $notification, $site);
                $allSuccessful = $successful && $allSuccessful;
                $this->setNotificationStatus(
                    $notification,
                    $successful ? Notification::STATUS_SENT : Notification::STATUS_FAILED,
                );
            } catch (\Throwable $exception) {
                $allSuccessful = false;
                $this->logger->error($exception->getMessage(), [
                    'exception' => $exception,
                    'notificationUid' => $notification->getUid(),
                    'notificationTitle' => $notification->getTitle(),
                ]);
                $this->setNotificationStatus($notification, Notification::STATUS_FAILED);
            }
        }

        return $allSuccessful;
    }

    private function createWebPush(Site $site): WebPush
    {
        $configuration = $this->extensionConfiguration->get('web_notifications');
        $configuration = is_array($configuration) ? $configuration : [];

        $publicKey = trim((string)($configuration['publicKey'] ?? ''));
        $privateKey = trim((string)($configuration['privateKey'] ?? ''));
        $subject = (string)$site->getBase();

        if ($publicKey === '' || $privateKey === '') {
            throw new \RuntimeException('The VAPID public and private keys must be configured.', 1755251110);
        }
        if (!str_starts_with($subject, 'https://') || filter_var($subject, FILTER_VALIDATE_URL) === false) {
            throw new \RuntimeException(
                'The TYPO3 site base must be a valid HTTPS URL for web push notifications.',
                1755251112,
            );
        }

        return new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);
    }

    private function resolveSite(int $pageId): Site
    {
        try {
            return $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException $exception) {
            throw new \RuntimeException(
                'The notification folder must be located within a configured TYPO3 site.',
                1755251111,
                $exception,
            );
        }
    }

    private function setNotificationStatus(Notification $notification, int $status): void
    {
        $notification->setStatus($status);
        $this->notificationRepository->update($notification);
        $this->persistenceManager->persistAll();
    }

    private function send(WebPush $webPush, Notification $notification, Site $site): bool
    {
        $payload = json_encode(
            $this->buildNotificationPayload($notification, $site),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
        $queuedDevices = [];
        $allSuccessful = true;

        foreach ($this->deviceRepository->findAllWithoutStoragePage() as $device) {
            $subscription = $this->convertDeviceToSubscription($device);
            $webPush->queueNotification($subscription, $payload);
            $queuedDevices[$subscription->getEndpoint()] = $device;
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            $allSuccessful = false;
            $device = $queuedDevices[$report->getEndpoint()] ?? null;
            if ($device instanceof Device && $report->isSubscriptionExpired()) {
                $this->deviceRepository->remove($device);
            }
            $this->logger->warning('A push notification delivery failed.', [
                'endpoint' => $report->getEndpoint(),
                'reason' => $report->getReason(),
                'notificationUid' => $notification->getUid(),
            ]);
        }

        $this->persistenceManager->persistAll();

        return $allSuccessful;
    }

    /** @return array{title: string, options: array<string, mixed>} */
    private function buildNotificationPayload(Notification $notification, Site $site): array
    {
        $settings = $site->getSettings();
        $options = [
            'body' => $notification->getBodytext(),
        ];
        $icon = trim((string)($settings->get('webNotifications.icon', '') ?? ''));
        if ($icon !== '') {
            $options['icon'] = $icon;
        }

        $image = $this->getImageUrl(
            $notification,
            max(1, (int)($settings->get('webNotifications.image.maxWidth', self::DEFAULT_IMAGE_MAX_WIDTH)
                ?? self::DEFAULT_IMAGE_MAX_WIDTH)),
            max(1, (int)($settings->get('webNotifications.image.maxHeight', self::DEFAULT_IMAGE_MAX_HEIGHT)
                ?? self::DEFAULT_IMAGE_MAX_HEIGHT)),
        );
        if ($image !== '') {
            $options['image'] = $image;
        }
        $notificationUrl = trim($notification->getUrl());
        if ($notificationUrl !== '') {
            $options['data'] = ['url' => $this->resolveNotificationUrl($notificationUrl)];
        }

        return [
            'title' => $notification->getTitle(),
            'options' => $options,
        ];
    }

    private function resolveNotificationUrl(string $link): string
    {
        $linkDetails = $this->linkService->resolve($link);
        $linkType = $linkDetails['type'] ?? null;

        if ($linkType === LinkService::TYPE_URL) {
            $url = $linkDetails['url'] ?? null;
            if (!is_string($url) || $url === '') {
                throw new \RuntimeException('The notification URL is invalid.', 1786903200);
            }

            return $url;
        }

        if ($linkType !== LinkService::TYPE_PAGE) {
            throw new \RuntimeException(
                'Notification links must point to a TYPO3 page or an external URL.',
                1786903201,
            );
        }

        $pageUid = $linkDetails['pageuid'] ?? null;
        if (!is_int($pageUid) && !(is_string($pageUid) && ctype_digit($pageUid))) {
            throw new \RuntimeException('The linked TYPO3 page UID is invalid.', 1786903202);
        }
        $pageUid = (int)$pageUid;
        if ($pageUid <= 0) {
            throw new \RuntimeException('The linked TYPO3 page UID is invalid.', 1786903202);
        }

        /** @var array<string, mixed> $parameters */
        $parameters = [];
        $encodedParameters = $linkDetails['parameters'] ?? null;
        if (is_string($encodedParameters) && $encodedParameters !== '') {
            parse_str($encodedParameters, $parameters);
        }
        $pageType = $linkDetails['pagetype'] ?? null;
        if (is_scalar($pageType) && (string)$pageType !== '') {
            $parameters['type'] = (string)$pageType;
        }
        $fragment = $linkDetails['fragment'] ?? '';
        $fragment = is_string($fragment) ? $fragment : '';

        try {
            $site = $this->siteFinder->getSiteByPageId($pageUid);
        } catch (SiteNotFoundException $exception) {
            throw new \RuntimeException(
                'The linked TYPO3 page must be located within a configured site.',
                1786903203,
                $exception,
            );
        }

        return (string)$site->getRouter()->generateUri($pageUid, $parameters, $fragment);
    }

    private function getImageUrl(Notification $notification, int $maxWidth, int $maxHeight): string
    {
        $media = $notification->getMedia();
        if ($media === null) {
            return '';
        }

        $processedImage = $this->imageService->applyProcessingInstructions(
            $media->getOriginalResource(),
            ['maxWidth' => $maxWidth, 'maxHeight' => $maxHeight],
        );

        return $this->imageService->getImageUri($processedImage);
    }

    private function convertDeviceToSubscription(Device $device): Subscription
    {
        return Subscription::create([
            'endpoint' => $device->getEndpoint(),
            'publicKey' => $device->getPublicKey(),
            'authToken' => $device->getAuthToken(),
            'contentEncoding' => $device->getContentEncoding(),
        ]);
    }
}
