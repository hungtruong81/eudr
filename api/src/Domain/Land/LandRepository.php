<?php

declare(strict_types=1);

namespace App\Domain\Land;

interface LandRepository
{
    /**
     * @param array $params
     * @return Land[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return int
     */
    public function getTotalLand(): int;
    /**
     * @param int $plot_id
     * @return Land
     * @throws LandNotFoundException
     */
    public function findLandOfId(int $plot_id): ? Land;

    /**
     * Find by ID with permission (self/own/all).
     */
    public function findLandOfIdWithPermission(int $plot_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ? Land;
    /**
     * @param string $plot_code
     * @return Land
     * @throws LandNotFoundException
     */
    public function findLandOfCode(string $plot_code): ? Land;
    /**
     * @param array $plot_ids
     * @param int $user_id
     * @return array
     */
    public function findLandIdsOfOwner(array $plot_ids, int $user_id): array;
    /**
     * @param string $plot_code
     * @param int $auth_user_id
     * @param string $scope
     * @return Land
     * @throws LandNotFoundException
     */
    public function findLandOfCodeWithPermission(string $plot_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ? Land;
    /**
     * @param array $data
     * @return Land
     */
    public function createLand(array $data): ? Land;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $plot_id
     * @param array $data_update
     * @return Land
     */
    public function updateLand(int $plot_id, array $data_update): Land;

    /**
     * Update land with permission (self/own/all).
     */
    public function updateLandWithPermission(int $plot_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Land;
    /**
     * @param int $plot_id
     * @param int $user_id
     */
    public function deleteLand(int $plot_id, int $user_id): void;

    /**
     * Delete (soft) land with permission (self/own/all).
     */
    public function deleteLandWithPermission(int $plot_id, int $deleted_by, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
    /**
     * @param array $coords_input
     * @param float $tolerance
     * @param int $plot_id
     * @return bool
     */
    public function checkDuplicateCoordinates(array $coords_input, float $tolerance, int $plot_id): bool;
    /**
     * @param array $plot_ids
     * @param int $shared_with_user_id
     * @param int $owner_user_id
     * @return void
     */
    public function shareLand(array $plot_ids, int $shared_with_user_id, int $owner_user_id): void;
    /**
     * @param int $user_id
     * @param array $params
     * @return array
     */
    public function getMySharedLands(int $user_id, array $params): array;
    /**
     * @param int $plot_id
     * @param int $user_id
     * @param array $params
     * @return array
     */
    public function getListUserSharedLand(int $plot_id, int $user_id, array $params): array;
    /**
     * @param int $plot_id
     * @param int $owner_user_id
     * @param int $shared_with_user_id
     */
    public function revokeShareLand(int $plot_id, int $owner_user_id, int $shared_with_user_id): void;

    /**
     * @param int $seller_user_id
     * @param array $params
     * @return array
     */
    public function listLandOfSeller(int $seller_user_id, array $params): array;

    /**
     * @param int $transaction_ticket_id
     * @param array $params
     * @return array
     */
    public function listLandByTransactionTicket(int $transaction_ticket_id, array $params): array;

    /**
     * @param array $params
     * @return array
     */
    public function getSharedLandByUser(array $params): array;

    /**
     * @param array $params
     * @return array
     */
    public function getAllSharedLand(array $params): array;
    
    /**
     * @param array $params
     * @return array
     */
    public function findLandSupport(array $params, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): array;

}
