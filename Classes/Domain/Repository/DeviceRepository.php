<?php

declare(strict_types=1);

namespace SyntaxOops\WebNotifications\Domain\Repository;

use SyntaxOops\WebNotifications\Domain\Model\Device;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Device>
 */
final class DeviceRepository extends Repository
{
    public function findByEndpoint(string $endpoint): ?Device
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $result = $query
            ->matching($query->equals('endpoint', $endpoint))
            ->execute()
            ->getFirst();

        return $result instanceof Device ? $result : null;
    }

    /** @return QueryResultInterface<int, Device> */
    public function findAllWithoutStoragePage(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->execute();
    }
}
