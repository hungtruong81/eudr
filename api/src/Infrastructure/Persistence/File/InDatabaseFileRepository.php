<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\File;

use App\Domain\File\File;
use App\Domain\File\FileNotFoundException;
use App\Domain\File\FileRepository;
use App\Application\Utility\Utils;
use App\Application\Settings\SettingsInterface;

class InDatabaseFileRepository implements FileRepository
{
    /**
     * @var MysqliDb
     */
    private $db;
    /**
     * @var SettingsInterface
     */
    private $settings;

    /**
     * InDatabaseFileRepository constructor.
     *
     * @param MysqliDb $db
     */
    public function __construct(\MysqliDb $db , SettingsInterface $settings)
        
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($params = []): array
    {
        $page = 1;
        // Limit default 20
        if (!empty($params['page_limit'])) {
            $this->db->pageLimit = intval($params['page_limit']);
        }

        $cols = "f.*,t.thread_code";

        $this->db->where("f.is_deleted", 0);
        if (!empty($params['order_by'])) {
            $this->db->orderBy('f.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("f.updated_at", "DESC");
        }
        $this->db->join("w_thread t", "t.thread_id=f.thread_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_general_files f", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            $files = [];
            foreach ($records as $item) {
                $items[] = new File($item['file_id'], $item);
            }
        }
        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
        ];
        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function findFileOfId(int $file_id): ?File
    {
        $this->db->where("f.file_id", $file_id);
        $this->db->where("f.is_deleted", 0);
        $fields = "f.*";
        $item = $this->db->getOne("eudr_general_files f",$fields);
        if (empty($item)) {
            return null;
        }
        return new File($item['file_id'], $item);
    }
    /**
     * {@inheritdoc}
     */
    public function getEmbeddings(int $file_id): array
    {
        $embeddings = [];
        $this->db->where("fb.file_id", $file_id);
        $fields = "fb.*";
        $rs = $this->db->get("w_file_embedding fb",null,$fields);
        if ($this->db->count > 0) {
            foreach ($rs as $record) {
                $embeddings[] = [
                    "text" => $record['text'],
                    "embedding" => json_decode($record['embedding'], true),
                ];
            }
        }
        return $embeddings;
    }
    /**
     * {@inheritdoc}
     */
    public function findFileOfCode(string $code): ?File
    {
        $this->db->where("f.is_deleted", 0);
        $this->db->where("f.file_code", $code);
        $fields = "f.*";
        $item = $this->db->getOne("eudr_general_files f",$fields);

        if (empty($item)) {
            return null;
        }
        return new File($item['file_id'], $item);
    }
    /**
     * {@inheritdoc}
     */
    public function findFileNotEmbedding(): ?File
    {
        $this->db->where("f.is_deleted", 0);
        $this->db->where("f.is_embedded", 0);
        $fields = "f.*";
        $item = $this->db->getOne("eudr_general_files f",$fields);

        if (empty($item)) {
            return null;
        }
        return new File($item['file_id'], $item);
    }

    /**
     * {@inheritdoc}
     */
    public function createFile(array $data_item): File
    {
        $id = $this->db->insert('eudr_general_files', $data_item);

        $this->db->where("file_id", $id);
        $this->db->where("f.is_deleted", 0);
        $fields = "f.*";
        $item = $this->db->getOne("eudr_general_files f",$fields);
        if (empty($item)) {
            throw new FileNotFoundException();
        }
        return new File($item['file_id'], $item);
    }
    /**
     * {@inheritdoc}
     */
    public function insertEmbeddings(int $file_id, array $embeddings)
    {
        foreach ($embeddings as $da) {
            $data_item = [
                "file_id" => $file_id,
                "text" => $da['text'],
                "embedding" => json_encode($da['embedding']),
            ];
            $this->db->insert('w_file_embedding', $data_item);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalFiles(): int
    {
        $total_files = 0;
        $this->db->where("is_deleted", 0);
        $total_files = $this->db->getValue("eudr_general_files","count(*)");

        return $total_files;
    }

    /**
     * {@inheritdoc}
     */
    public function updateFile(int $file_id, array $data_item): File
    {
        $this->db->where("file_id", $file_id);
        $this->db->update('eudr_general_files', $data_item);

        $this->db->where("file_id", $file_id);
        $item = $this->db->getOne("eudr_general_files");
        if (empty($item)) {
            throw new FileNotFoundException();
        }
        return new File($item['file_id'], $item);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFile(int $file_id)
    {
        $this->db->where("file_id", $file_id);
        $this->db->update('eudr_general_files', ['is_deleted'=>1]);
    }
    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "file-".date("ymd").'-'.Utils::generateRandomString(8);
            $thread = $this->findFileOfCode($code);
            if (!$thread)
                break;
        }
        return $code;
    }
    /**
     * {@inheritdoc}
     */
    public function mapFileIdsToMap(array $file_ids): array
    {
        if (empty($file_ids)) return [];

        $file_ids = array_unique(array_map('intval', $file_ids));
        $this->db->where('file_id', $file_ids, 'IN');
        $files = $this->db->get('eudr_general_files', null, ['file_id', 'file_path']);

        $file_map = [];
        foreach ($files as $file) {
            $file_map[$file['file_id']] = $this->settings->get('url_cdn') . '/' . ltrim($file['file_path'], '/');
        }

        return $file_map;
    }
}
