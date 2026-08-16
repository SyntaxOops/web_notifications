<?php

declare(strict_types=1);

namespace SyntaxOops\WebNotifications\Command;

use Minishlink\WebPush\VAPID;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

#[AsCommand(
    name: 'webnotifications:vapid:generate',
    description: 'Generate and store the VAPID key pair used for web push notifications',
)]
final class GenerateVapidKeysCommand extends Command
{
    private const EXTENSION_KEY = 'web_notifications';

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $configuration = $this->extensionConfiguration->get(self::EXTENSION_KEY);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }
        $configuration = is_array($configuration) ? $configuration : [];

        $publicKey = trim((string)($configuration['publicKey'] ?? ''));
        $privateKey = trim((string)($configuration['privateKey'] ?? ''));

        if ($publicKey !== '' || $privateKey !== '') {
            if ($publicKey !== '' && $privateKey !== '') {
                $io->note('A VAPID key pair is already configured. No changes were made.');

                return self::SUCCESS;
            }

            $io->error('The VAPID configuration contains only one key. Clear both key fields before generating a new pair.');

            return self::FAILURE;
        }

        try {
            $keys = VAPID::createVapidKeys();
            $configuration['publicKey'] = $keys['publicKey'];
            $configuration['privateKey'] = $keys['privateKey'];
            $this->extensionConfiguration->set(self::EXTENSION_KEY, $configuration);
        } catch (\Throwable $exception) {
            $io->error('The VAPID key pair could not be generated or stored: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $io->success('The VAPID key pair was generated and stored in TYPO3 extension configuration.');

        return self::SUCCESS;
    }
}
