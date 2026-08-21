<?php

declare(strict_types=1);

namespace App\Domain\Page;

interface PageRepository
{
    /**
     * @param array $params
     * @return Page[]
     */
    public function findAll(array $params = []): array;

    /**
     * @param int $workspace_id
     * @return int
     */
    public function getTotalPages(int $workspace_id): int;
    /**
     * @param int $id_page
     * @return Page
     * @throws PageNotFoundException
     */
    public function findPageOfId(int $id_page): ?Page;

    /**
     * @param string $application_id
     * @param int $workspace_id
     * @return Page
     * @throws PageNotFoundException
     */
    public function findPageOfPageId(string $application_id, int $workspace_id): ?Page;
    /**
     * @param string $app_code
     * @param int $workspace_id
     * @return Page
     * @throws PageNotFoundException
     */
    public function findPageOfCode(string $app_code, int $workspace_id): ?Page;

    /**
     * @param array $data
     * @return Page
     */
    public function createPage(array $data): ?Page;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param array $data_update
     * @param int $id_page
     * @return Page
     */
    public function updatePage(int $id_page, array $data_update): Page;
    /**
     * @param int $id_page
     * @return Page
     */
    public function deletePage(int $id_page);

}
