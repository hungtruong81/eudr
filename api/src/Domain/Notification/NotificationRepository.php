<?php

declare(strict_types=1);

namespace App\Domain\Notification;

interface NotificationRepository
{
    /**
     * @param array $params
     * @return Notification[]
     */
    public function findAll(array $params): array;

    /**
     * @param array $params
     * @return int New notification ID
     */
    public function createNotification(array $params) : int;

    /**
     * @param int $user_id
     * @param array $notification_ids
     * @return bool
     */
    public function markAsRead(int $user_id, array $notification_ids): bool;

    /**
     * @param array $params
     * @return array
     */
    public function getRelatedTypes(array $params): array;

}
