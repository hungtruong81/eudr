<?php

declare(strict_types=1);

namespace App\Domain\ProductLot;

interface ProductLotRepository
{
    /**
     * @param array $params
     * @return array
     */
    public function findAll(array $params = []): array;

    /**
     * @param int $product_lot_id
     * @return ProductLot|null
     */
    public function findProductLotOfId(int $product_lot_id): ?ProductLot;

    /**
     * @param string $code
     * @return ProductLot|null
     */
    public function findProductLotOfCode(string $code): ?ProductLot;

    /**
     * @param array $data
     * @return ProductLot|null
     */
    public function createProductLot(array $data): ?ProductLot;

    /**
     * @param int $product_lot_id
     * @param array $data_update
     * @return ProductLot
     */
    public function updateProductLot(int $product_lot_id, array $data_update): ProductLot;

    /**
     * @param int $product_lot_id
     * @param array $items Array of product lot item data arrays
     * @return void
     */
    public function addProductLotItems(int $product_lot_id, array $items): void;

    /**
     * @param int $product_lot_id
     * @return array
     */
    public function getProductLotItems(int $product_lot_id): array;

    /**
     * @param int $product_lot_id
     * @return void
     */
    public function removeProductLotItems(int $product_lot_id): void;

    /**
     * @param array $data
     * @param array $rubber_block_ids
     * @return ProductLot|null
     */
    public function createProductLotWithItems(array $data, array $rubber_block_ids): ?ProductLot;

    /**
     * @param int $product_lot_id
     * @param array $data_update
     * @param array $rubber_block_ids
     * @return ProductLot|null
     */
    public function updateProductLotWithItems(int $product_lot_id, array $data_update, array $rubber_block_ids): ?ProductLot;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param array $product_lot_ids
     * @return ProductLot[]
     */
    public function findProductLotsByIds(array $product_lot_ids): array;

    /**
     * @param array $product_lot_ids
     * @param int $owner_company_id
     * @param int $owner_id
     * @return void
     */
    public function transferOwnership(array $product_lot_ids, int $owner_company_id, int $owner_id = 0): void;

    /**
     * @param array $params
     * @return array
     */
    public function getProductLotSummary(array $params = []): array;

    /**
     * Trace a product lot back to all related farms/gardens (lands).
     * Automatically detects lot_type (internal/external) and uses the appropriate tracing strategy.
     *
     * @param int $product_lot_id
     * @return array
     */
    public function traceProductLotToFarms(int $product_lot_id): array;

    /**
     * Create an external product lot with lands and transport data.
     *
     * @param array $data
     * @return ProductLot|null
     */
    public function createExternalProductLot(array $data): ?ProductLot;

    /**
     * Update an external product lot with lands and transport data.
     *
     * @param int $product_lot_id
     * @param array $data
     * @return ProductLot|null
     */
    public function updateExternalProductLot(int $product_lot_id, array $data): ?ProductLot;

    /**
     * Soft delete a product lot (external only, status must be draft).
     *
     * @param int $product_lot_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductLot(int $product_lot_id, int $deleted_by): void;

    /**
     * Confirm a product lot (draft → confirmed).
     *
     * @param int $product_lot_id
     * @param int $confirmed_by
     * @return ProductLot|null
     */
    public function confirmProductLot(int $product_lot_id, int $confirmed_by): ?ProductLot;

    /**
     * Cancel a product lot (draft/confirmed → cancelled).
     *
     * @param int $product_lot_id
     * @param int $cancelled_by
     * @return ProductLot|null
     */
    public function cancelProductLot(int $product_lot_id, int $cancelled_by): ?ProductLot;

    /**
     * Get lands linked to a product lot (for external lots).
     *
     * @param int $product_lot_id
     * @return array
     */
    public function findLandsByProductLotId(int $product_lot_id): array;

    /**
     * Get transport data for a product lot (for external lots).
     *
     * @param int $product_lot_id
     * @return array|null
     */
    public function findTransportByProductLotId(int $product_lot_id): ?array;

    /**
     * Generate code for external product lot.
     *
     * @return string
     */
    public function generateExternalCode(): string;

    /**
     * Get non-EUDR quantity items (repeater) for a product lot.
     *
     * @param int $product_lot_id
     * @return array
     */
    public function findNonEudrItemsByProductLotId(int $product_lot_id): array;

    /**
     * Get file attachments (contract files + signature) for a product lot.
     *
     * @param int $product_lot_id
     * @return array
     */
    public function findAttachmentsByProductLotId(int $product_lot_id): array;
}
