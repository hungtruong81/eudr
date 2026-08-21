<?php

declare(strict_types=1);

namespace App\Domain\TransactionTicket;

use JsonSerializable;

class TransactionTicket implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $transaction_ticket_id;
    /**
     * @var string
    */
    private $transaction_ticket_code;
    /**
     * @var string
    */
    private $transaction_ticket_type;
    /**
     * @var string
     */
    private $contract_code;
    /**
     * @var int
    */
    private $connection_id;
    /**
     * @var int
    */
    private $buyer_user_id;
    /**
     * @var int
    */
    private $buyer_company_id;
    /**
     * @var string
     */
    private $buyer_company_short_name;
    /**
     * @var string
     */
    private $buyer_name;
    /**
     * @var string
     */
    private $buyer_phone;
    /**
     * @var string
     */
    private $buyer_account_type;
    /**
     * @var string
     */
    private $buyer_address;
    /**
     * @var int
     */
    private $seller_user_id;
    /**
     * @var int
    */
    private $seller_company_id;
    /**
     * @var string
     */
    private $seller_company_short_name;
    /**
     * @var string
     */
    private $seller_name;
    /**
     * @var string
     */
    private $seller_phone;
    /**
     * @var string
     */
    private $seller_account_type;
    /**
     * @var string
     */
    private $seller_address;
    /**
     * @var float
     */
    private $latex_weight;
    /**
     * @var float
     */
    private $latex_tsc_grade;
    /**
     * @var float
     */
    private $latex_price_per_tsc;
    /**
     * @var float
     */
    private $latex_total_amount;
    /**
     * @var string
     */
    private $latex_notes;
    /**
     * @var float
     */
    private $scrap_rubber_weight;
    /**
     * @var float
     */
    private $scrap_rubber_drc_grade;
    /**
     * @var float
     */
    private $scrap_rubber_price_per_drc;
    /**
     * @var float
     */
    private $scrap_rubber_total_amount;
    /**
     * @var string
     */
    private $scrap_rubber_notes;
    /**
     * @var string
     */
    private $payment_terms;
    /**
     * @var string
     */
    private $delivery_terms;
    /**
     * @var string
     */
    private $status;
    /**
     * @var string
     */
    private $created_at;
    /**
     * @var int
     */
    private $created_by;
    /**
     * @var int
     */
    private $usage_count;

    /**
     * @param int|null  $transaction_ticket_id
     * @param array    $data
     */
    public function __construct(?int $transaction_ticket_id, array $data)
    {
        $this->transaction_ticket_id = $transaction_ticket_id;
        $this->transaction_ticket_code = $data['transaction_ticket_code'] ?? '';
        $this->transaction_ticket_type = $data['transaction_ticket_type'] ?? '';
        $this->contract_code = $data['contract_code'] ?? '';
        $this->connection_id = $data['connection_id'] ?? 0;
        $this->buyer_user_id = $data['buyer_user_id'] ?? 0;
        $this->buyer_name = $data['buyer_name'] ?? '';
        $this->buyer_phone = $data['buyer_phone'] ?? '';
        $this->buyer_account_type = $data['buyer_account_type'] ?? '';
        $this->buyer_address = $data['buyer_address'] ?? '';
        $this->buyer_company_id = $data['buyer_company_id'] ?? 0;
        $this->buyer_company_short_name = $data['buyer_company_short_name'] ?? '';
        $this->seller_user_id = $data['seller_user_id'] ?? 0;
        $this->seller_name = $data['seller_name'] ?? '';
        $this->seller_phone = $data['seller_phone'] ?? '';
        $this->seller_account_type = $data['seller_account_type'] ?? '';
        $this->seller_address = $data['seller_address'] ?? '';
        $this->seller_company_id = $data['seller_company_id'] ?? 0;
        $this->seller_company_short_name = $data['seller_company_short_name'] ?? '';
        $this->latex_weight = $data['latex_weight'] ?? 0.0;
        $this->latex_tsc_grade = $data['latex_tsc_grade'] ?? 0.0;
        $this->latex_price_per_tsc = $data['latex_price_per_tsc'] ?? 0;
        $this->latex_total_amount = $data['latex_total_amount'] ?? 0;
        $this->latex_notes = $data['latex_notes'] ?? '';
        $this->scrap_rubber_weight = $data['scrap_rubber_weight'] ?? 0.0;
        $this->scrap_rubber_drc_grade = $data['scrap_rubber_drc_grade'] ?? 0.0;
        $this->scrap_rubber_price_per_drc = $data['scrap_rubber_price_per_drc'] ?? 0;
        $this->scrap_rubber_total_amount = $data['scrap_rubber_total_amount'] ?? 0;
        $this->scrap_rubber_notes = $data['scrap_rubber_notes'] ?? '';
        $this->payment_terms = $data['payment_terms'] ?? '';
        $this->delivery_terms = $data['delivery_terms'] ?? '';
        $this->status = $data['status'] ?? 'draft';
        $this->created_at = $data['created_at'] ?? '';
        $this->created_by = $data['created_by'] ?? 0;
        $this->usage_count = $data['usage_count'] ?? 0;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->transaction_ticket_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->transaction_ticket_code;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->transaction_ticket_type;
    }

    /**
     * @return string
     */
    public function getContractCode(): string
    {
        return $this->contract_code;
    }

    /**
     * @return int
     */
    public function getConnectionId(): int
    {
        return $this->connection_id;
    }

    /**
     * @return int
     */
    public function getBuyerUserId(): int
    {
        return $this->buyer_user_id;
    }

    /**
     * @return int
     */
    public function getBuyerCompanyId(): int
    {
        return $this->buyer_company_id;
    }

    /**
     * @return int
     */
    public function getSellerUserId(): int
    {
        return $this->seller_user_id;
    }

    /**
     * @return int
     */
    public function getSellerCompanyId(): int
    {
        return $this->seller_company_id;
    }

    /**
     * @return int
     */
    public function getCreatedBy(): int
    {
        return $this->created_by;
    }

    /**
     * Get the target user ID based on the current user ID.
     * @param int $current_user_id
     * @return int
     */
    public function getTargetUserId(int $current_user_id): int
    {
        if($current_user_id == $this->getBuyerUserId()) {
            return $this->getSellerUserId();
        }
        if($current_user_id == $this->getSellerUserId()) {
            return $this->getBuyerUserId();
        }
        return 0;
    }

    /**
     *  * @return string
     */
    public function getBuyerAccountType(): string
    {
        return $this->buyer_account_type;
    }

    /**
     *  * @return string
     */
    public function getSellerAccountType(): string
    {
        return $this->seller_account_type;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'transaction_ticket_id' => $this->transaction_ticket_id,
            'transaction_ticket_code' => $this->transaction_ticket_code,
            'transaction_ticket_type' => $this->transaction_ticket_type,
            'contract_code' => $this->contract_code,
            'connection_id' => $this->connection_id,
            'buyer_user_id' => $this->buyer_user_id,
            'buyer_name' => $this->buyer_name,
            'buyer_phone' => $this->buyer_phone,
            'buyer_account_type' => $this->buyer_account_type,
            'buyer_address' => $this->buyer_address,
            'buyer_company_id' => $this->buyer_company_id,
            'buyer_company_short_name' => $this->buyer_company_short_name,
            'seller_user_id' => $this->seller_user_id,
            'seller_name' => $this->seller_name,
            'seller_phone' => $this->seller_phone,
            'seller_account_type' => $this->seller_account_type,
            'seller_address' => $this->seller_address,
            'seller_company_id' => $this->seller_company_id,
            'seller_company_short_name' => $this->seller_company_short_name,
            'latex_weight' => $this->latex_weight,
            'latex_tsc_grade' => $this->latex_tsc_grade,
            'latex_price_per_tsc' => $this->latex_price_per_tsc,
            'latex_total_amount' => $this->latex_total_amount,
            'latex_notes' => $this->latex_notes,
            'scrap_rubber_weight' => $this->scrap_rubber_weight,
            'scrap_rubber_drc_grade' => $this->scrap_rubber_drc_grade,
            'scrap_rubber_price_per_drc' => $this->scrap_rubber_price_per_drc,
            'scrap_rubber_total_amount' => $this->scrap_rubber_total_amount,
            'scrap_rubber_notes' => $this->scrap_rubber_notes,
            'payment_terms' => $this->payment_terms,
            'delivery_terms' => $this->delivery_terms,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'usage_count' => $this->usage_count,
        ];
    }
    
}
