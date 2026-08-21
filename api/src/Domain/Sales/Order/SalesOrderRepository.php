<?php

declare(strict_types=1);

namespace App\Domain\Sales\Order;

interface SalesOrderRepository
{
    /**
     * @param array $params
     * @return array{current_page:int,total_pages:int,total_records:int,page_limit:int,records:SalesOrder[]}
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
    * @param int $sale_order_id
     */
    public function findOrderOfId(int $sale_order_id): ?SalesOrder;

    /**
    * @param int $sale_order_id
     */
    public function findOrderOfIdWithPermission(int $sale_order_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesOrder;

    /**
    * @param string $sale_order_code
     */
    public function findOrderOfCode(string $sale_order_code): ?SalesOrder;

    /**
    * @param string $sale_order_code
     */
    public function findOrderOfCodeWithPermission(string $sale_order_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesOrder;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param array $data
     * @param array<int,array<string,mixed>> $items
     */
    public function createOrder(array $data, array $items): ?SalesOrder;

    /**
     * @param int $sale_order_id
     * @param array $data
     * @param array<int,array<string,mixed>> $items
     */
    public function updateOrderWithPermission(int $sale_order_id, array $data, array $items, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesOrder;

    /**
     * @param int $sale_order_id
     */
    public function deleteOrderWithPermission(int $sale_order_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): bool;

    /**
     * @param int $transaction_ticket_id
     * @param array $params
     * @return array{current_page:int,total_pages:int,total_records:int,page_limit:int,records:SalesOrder[]}
     */
    public function findOrdersByTransactionTicket(int $transaction_ticket_id, array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * List purchase orders where buyer_company_id matches.
     *
     * @param array $params
     * @return array{current_page:int,total_pages:int,total_records:int,page_limit:int,records:SalesOrder[]}
     */
    public function findPurchaseOrders(array $params = [], ?int $auth_user_id = null, ?int $buyer_company_id = null): array;
}
