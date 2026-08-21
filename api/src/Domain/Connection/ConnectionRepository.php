<?php

declare(strict_types=1);

namespace App\Domain\Connection;

interface ConnectionRepository
{
    /**
     * @param array $params
     * @return Connection[]
     */
    public function findAll(array $params = []): array;
    /**
     * @param array $data
     * @return int|array New connection ID or empty array on failure
     */
    public function createConnectionRequest(array $data): int|array;
    /**
     * @param int $user_request_id
     * @param int $user_target_id
     * @param string $status
     * @return array
     */
    public function findConnectionBetweenUsers(int $user_request_id, int $user_target_id, string $status = ""): array;
    /**
     * @param int $connection_request_id
     * @param int $user_request_id
     * @return bool
     */
    public function cancelConnectionRequest(int $connection_id, int $user_request_id): bool;
    /**
     * @param int $connection_id
     * @param int $target_user_id
     * @param array $data_update
     * @return bool
     */
    public function respondConnectionRequest(int $connection_id, int $target_user_id, array $data_update): bool;
    /**
     * @param int $connection_id
     * @param int $user_id
     * @param string $action
     * @return bool
     */
    public function updateConnectionStatus(int $connection_id, int $user_id, string $action): bool;
    /**
     * @param string $phone
     * @return array
     */
    public function searchUserOfPhone(string $phone, int $current_user_id=0): array;

}
