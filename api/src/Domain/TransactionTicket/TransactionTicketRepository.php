<?php

declare(strict_types=1);

namespace App\Domain\TransactionTicket;

interface TransactionTicketRepository
{
    /**
     * @param array $params
     * @return TransactionTicket[]
     */
    public function findAll(array $params = []): array;

    /**
     * @param string $code
     * @return TransactionTicket |null
     */
    public function findTransactionTicketOfCode(string $code): ?TransactionTicket;

    /**
     * @param string $contract_code
     * @return TransactionTicket |null
     */
    public function findTransactionTicketOfContractCode(string $contract_code): ?TransactionTicket;
    
    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param int $id
     * @return TransactionTicket |null
     */
    public function findTransactionTicketOfId(int $id): ?TransactionTicket;

    /**
     * @param array $data
     * @return TransactionTicket |null
     */
    public function createTransactionTicket(array $data): ?TransactionTicket;

    /**
     * @param int $id
     * @param array $data
     * @return TransactionTicket |null
     */
    public function updateTransactionTicket(int $id, array $data): ?TransactionTicket;

    /**
     * @param array $ids
     * @param int $user_id
     * @return array
     */
    public function findPurchaseTicketsByIds(array $ids, int $user_id): array;

    /**
     * @param array $ids
     * @return float
     */
    public function sumWeightOfTransactionTickets(array $ids): float;
    /**
     * @param array $ids
     * @param int $user_id
     * @return array
     */
    public function findSaleTicketsByIds(array $ids, int $user_id): array;

    /**
     * @param int $sale_ticket_id
     * @param array $params
     * @return array
     */
    public function getPurchaseTicketsBySaleTicket(int $sale_ticket_id, array $params = []): array;

    /**
     * @param array $params
     * @return TransactionTicket[]
     */
    public function findPurchaseTicketsUnrouted(array $params = []): array;

}
