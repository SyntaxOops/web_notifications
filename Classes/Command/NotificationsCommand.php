<?php

declare(strict_types=1);

namespace SyntaxOops\WebNotifications\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SyntaxOops\WebNotifications\Service\SendNotificationService;

#[AsCommand(
    name: 'webnotifications:send',
    description: 'Send pending web push notifications from a storage folder',
)]
final class NotificationsCommand extends Command
{
    public function __construct(
        private readonly SendNotificationService $sendNotificationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('uid', InputArgument::REQUIRED, 'UID of the folder containing notifications');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $folderUid = filter_var(
            $input->getArgument('uid'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        if ($folderUid === false) {
            $io->error('The folder UID must be a positive integer.');

            return self::INVALID;
        }

        try {
            if (!$this->sendNotificationService->sendNotification($folderUid)) {
                $io->error('At least one notification could not be sent. Check the TYPO3 log for details.');

                return self::FAILURE;
            }
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        $io->success('All pending notifications were processed.');

        return self::SUCCESS;
    }
}
