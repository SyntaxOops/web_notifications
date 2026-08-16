<?php

declare(strict_types=1);

namespace SyntaxOops\WebNotifications\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SyntaxOops\WebNotifications\Service\DeviceService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class FrontendMiddleware implements MiddlewareInterface
{
    private const SUBSCRIPTION_PATH = '/web-notifications/subscribe';
    private const SERVICE_WORKER_PATH = '/web-notifications-service-worker.js';
    private const SERVICE_WORKER_SOURCE = 'EXT:web_notifications/Resources/Public/JavaScript/ServiceWorker.js';

    public function __construct(
        private AssetCollector $assetCollector,
        private DeviceService $deviceService,
        private ExtensionConfiguration $extensionConfiguration,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $basePath = $this->getBasePath($request);
        $requestPath = $request->getUri()->getPath();

        if ($requestPath === $basePath . self::SERVICE_WORKER_PATH) {
            return $this->createServiceWorkerResponse($basePath);
        }
        if ($requestPath === $basePath . self::SUBSCRIPTION_PATH) {
            return $this->createSubscriptionResponse($request);
        }

        if (in_array(strtoupper($request->getMethod()), ['GET', 'HEAD'], true)) {
            $this->addFrontendAssets($basePath);
        }

        return $handler->handle($request);
    }

    private function createServiceWorkerResponse(string $basePath): ResponseInterface
    {
        $sourcePath = GeneralUtility::getFileAbsFileName(self::SERVICE_WORKER_SOURCE);
        $source = $sourcePath !== '' ? file_get_contents($sourcePath) : false;
        if ($source === false) {
            return $this->createJsonResponse(['error' => 'Service worker is unavailable.'], 500);
        }

        $scope = $basePath === '' ? '/' : $basePath . '/';

        return $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/javascript; charset=utf-8')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Service-Worker-Allowed', $scope)
            ->withBody($this->streamFactory->createStream($source));
    }

    private function createSubscriptionResponse(ServerRequestInterface $request): ResponseInterface
    {
        if (strtoupper($request->getMethod()) !== 'POST') {
            return $this->createJsonResponse(['error' => 'Method not allowed.'], 405)
                ->withHeader('Allow', 'POST');
        }

        try {
            $payload = json_decode((string)$request->getBody(), true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                throw new \InvalidArgumentException('The request body must contain a JSON object.', 1755252210);
            }

            $subscription = $payload['subscription'] ?? null;
            $keys = is_array($subscription) ? ($subscription['keys'] ?? null) : null;
            if (!is_array($subscription) || !is_array($keys)) {
                throw new \InvalidArgumentException('The push subscription payload is incomplete.', 1755252211);
            }

            $this->deviceService->addDevice(
                (string)($subscription['endpoint'] ?? ''),
                (string)($keys['p256dh'] ?? ''),
                (string)($keys['auth'] ?? ''),
                (string)($payload['contentEncoding'] ?? 'aes128gcm'),
            );
        } catch (\JsonException|\InvalidArgumentException $exception) {
            return $this->createJsonResponse(['error' => $exception->getMessage()], 400);
        }

        return $this->createJsonResponse(['result' => true]);
    }

    private function addFrontendAssets(string $basePath): void
    {
        $configuration = $this->extensionConfiguration->get('web_notifications');
        $configuration = is_array($configuration) ? $configuration : [];
        $publicKey = trim((string)($configuration['publicKey'] ?? ''));
        if ($publicKey === '') {
            return;
        }

        $scope = $basePath === '' ? '/' : $basePath . '/';
        $this->assetCollector->addJavaScript(
            'web-notifications',
            'EXT:web_notifications/Resources/Public/JavaScript/PushNotifications.js',
            [
                'defer' => 'defer',
                'data-application-server-key' => $publicKey,
                'data-service-worker-url' => $basePath . self::SERVICE_WORKER_PATH,
                'data-service-worker-scope' => $scope,
                'data-subscription-url' => $basePath . self::SUBSCRIPTION_PATH,
            ],
        );
    }

    /** @param array<string, bool|string> $payload */
    private function createJsonResponse(array $payload, int $status = 200): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store')
            ->withBody($this->streamFactory->createStream((string)json_encode($payload, JSON_THROW_ON_ERROR)));
    }

    private function getBasePath(ServerRequestInterface $request): string
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return '';
        }

        return rtrim($site->getBase()->getPath(), '/');
    }
}
