<?php

declare(strict_types=1);

namespace App\Domain\TransportationRoute;

interface TransportationRouteRepository
{
    /**
     * @param array $params
     * @return TransportationRoute[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $transportation_route_id
     * @return TransportationRoute|null
     * @throws TransportationRouteNotFoundException
     */
    public function findTransportationRouteOfId(int $transportation_route_id): ?TransportationRoute;

    /**
     * Find by ID with permission (self/own/all).
     */
    public function findTransportationRouteOfIdWithPermission(int $transportation_route_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?TransportationRoute;
    /**
     * @param string $code
     * @return TransportationRoute|null
     */
    public function findTransportationRouteOfCode(string $code): ?TransportationRoute;

    /**
     * Find by code with permission (self/own/all).
     */
    public function findTransportationRouteOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?TransportationRoute;
    /**
     * @param array $data
     * @return TransportationRoute|null
     */
    public function createTransportationRoute(array $data): ?TransportationRoute;
    /**
     * @param int $transportation_route_id
     * @param array $data_update
     * @return TransportationRoute
     */
    public function updateTransportationRoute(int $transportation_route_id, array $data_update): TransportationRoute;

    /**
     * Update transportation route with permission (self/own/all).
     */
    public function updateTransportationRouteWithPermission(int $transportation_route_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): TransportationRoute;
    /**
     * @param int $transportation_route_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteTransportationRoute(int $transportation_route_id, int $deleted_by): void;

    /**
     * Delete (soft) transportation route with permission (self/own/all).
     */
    public function deleteTransportationRouteWithPermission(int $transportation_route_id, int $deleted_by, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
    /**
     * @param int $transportation_route_id
     * @param array $data_update
     * @return TransportationRoute
     */
    public function unloadTransportationRoute(int $transportation_route_id, array $data_update): ?TransportationRoute;

    /**
     * Unload transportation route with permission (self/own/all).
     */
    public function unloadTransportationRouteWithPermission(int $transportation_route_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?TransportationRoute;

    /**
     * @param array $purchase_ticket_ids
     * @return int
     */
    public function countUnroutedPurchaseTickets(array $purchase_ticket_ids): int;

}
