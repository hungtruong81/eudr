<?php

declare(strict_types=1);

namespace App\Domain\RubberBlock;

interface RubberBlockRepository
{
    /**
     * @param array $params
     * @return array
     */
    public function findAll(array $params = []): array;

    /**
     * @param int $rubber_block_id
     * @return RubberBlock|null
     */
    public function findRubberBlockOfId(int $rubber_block_id): ?RubberBlock;

    /**
     * @param string $code
     * @return RubberBlock|null
     */
    public function findRubberBlockOfCode(string $code): ?RubberBlock;

    /**
     * @param int $production_order_id
     * @return RubberBlock[]
     */
    public function findRubberBlocksByProductionOrderId(int $production_order_id): array;

    /**
     * @param array $data
     * @return RubberBlock|null
     */
    public function createRubberBlock(array $data): ?RubberBlock;

    /**
     * @param array $items Array of rubber block data arrays
     * @return RubberBlock[]
     */
    public function createRubberBlocks(array $items): array;

    /**
     * @param int $rubber_block_id
     * @param array $data_update
     * @return RubberBlock
     */
    public function updateRubberBlock(int $rubber_block_id, array $data_update): RubberBlock;

    /**
     * @param array $rubber_block_ids
     * @param string $status
     * @return void
     */
    public function updateRubberBlocksStatus(array $rubber_block_ids, string $status): void;

    /**
     * @return string
     */
    public function generateCode(): string;
}
