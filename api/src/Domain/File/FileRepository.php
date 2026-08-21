<?php

declare(strict_types=1);

namespace App\Domain\File;

interface FileRepository
{
    /**
     * @param array $params
     * @return File[]
     */
    public function findAll(array $params = []): array;

    /**
     * @return int
     */
    public function getTotalFiles(): int;
    /**
     * @param int $file_id
     * @return File
     * @throws FileNotFoundException
     */
    public function findFileOfId(int $file_id): ?File;
    /**
     * @param string $code
     * @return File
     * @throws FileNotFoundException
     */
    public function findFileOfCode(string $code): ?File;
    /**
     * @return File
     * @throws FileNotFoundException
     */
    public function findFileNotEmbedding(): ?File;

    /**
     * @param array $data
     * @return File
     */
    public function createFile(array $data): ?File;

    /**
     * @param array $data_update
     * @param int $file_id
     * @return File
     */
    public function updateFile(int $file_id, array $data_update): File;
    /**
     * @param int $file_id
     * @return File
     */
    public function deleteFile(int $file_id);
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $file_id
     * @param array $embeddings
     */
    public function insertEmbeddings(int $file_id, array $embeddings);
    /**
     * @param int $file_id
     * @return array $embeddings
     */
    public function getEmbeddings(int $file_id): array;
    /**
     * @param array $file_ids
     * @return array
     */
    public function mapFileIdsToMap(array $file_ids): array;
}
