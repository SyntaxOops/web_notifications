<?php

declare(strict_types=1);

namespace SyntaxOops\WebNotifications\Domain\Repository;

use SyntaxOops\WebNotifications\Domain\Model\Notification;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Notification>
 */
final class NotificationRepository extends Repository
{
    /** @return QueryResultInterface<int, Notification> */
    public function findPendingByPid(int $pid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('status', Notification::STATUS_PENDING),
                $query->equals('pid', $pid),
            ),
        );

        return $query->execute();
    }
}
