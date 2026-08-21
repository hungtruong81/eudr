<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Page;

use App\Domain\Page\Page;
use App\Domain\Page\PageNotFoundException;
use App\Domain\Page\PageRepository;
use App\Application\Utility\Utils;

class InDatabasePageRepository implements PageRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * InDatabasePageRepository constructor.
     *
     * @param MysqliDb $db
     */
    public function __construct(\MysqliDb $db)
    {
        $this->db = $db;
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
        if (!empty($params['workspace_id'])) {
            $this->db->where("workspace_id", $params['workspace_id']);
        }
        if (!empty($params['fb_page_id'])) {
            $this->db->where("fb_page_id", $params['fb_page_id']);
        }
        if (!empty($params['search'])) {
            $this->db->where("(name LIKE '%".$params['search']."%' OR fb_page_id LIKE '%".$params['search']."%')");
        }

        $cols = "t.*";

        $this->db->where("t.is_deleted", 0);
        if (!empty($params['order_by'])) {
            $this->db->orderBy('t.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("t.updated_time", "DESC");
        }
        $records = $this->db->arraybuilder()->paginate("w_page t", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Page($item['id_page'], $item);
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
    public function findPageOfId(int $id_page): ?Page
    {
        $this->db->where("t.id_page", $id_page);
        $this->db->where("t.is_deleted", 0);
        $fields = "t.*";
        $item = $this->db->getOne("w_page t",$fields);
        if (empty($item)) {
            return null;
        }
        return new Page($item['id_page'], $item);
    }
    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'page_'.Utils::generateRandomString(25);
            $item = $this->findPageOfCode($code,0);
            if (!$item)
                break;
        }
        return $code;
    }
    /**
     * {@inheritdoc}
     */
    public function findPageOfCode(string $code, int $workspace_id): ?Page
    {
        $this->db->where("t.is_deleted", 0);
        $this->db->where("t.page_code", $code);
        if ($workspace_id) {
            $this->db->where("t.workspace_id", $workspace_id);
        }
        $fields = "t.*";
        $item = $this->db->getOne("w_page t",$fields);
        // die(var_dump($this->db->getLastQuery()));

        if (empty($item)) {
            return null;
        }
        return new Page($item['id_page'], $item);
    }
    /**
     * {@inheritdoc}
     */
    public function findPageOfPageId(string $page_id, int $workspace_id): ?Page
    {
        $this->db->where("t.is_deleted", 0);
        $this->db->where("t.fb_page_id", $page_id);
        $this->db->where("t.workspace_id", $workspace_id);
        $fields = "t.*";
        $item = $this->db->getOne("w_page t",$fields);
        // die(var_dump($this->db->getLastQuery()));

        if (empty($item)) {
            return null;
        }
        return new Page($item['id_page'], $item);
    }


    /**
     * {@inheritdoc}
     */
    public function createPage(array $data_item): Page
    {
        $id = $this->db->insert('w_page', $data_item);

        $this->db->where("id_page", $id);
        $item = $this->db->getOne("w_page");
        if (empty($item)) {
            throw new PageNotFoundException();
        }
        return new Page($item['id_page'], $item);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalPages(int $workspace_id): int
    {
        $total = 0;
        $this->db->where("is_deleted", 0);
        $this->db->where("workspace_id", $workspace_id);
        $total = $this->db->getValue("w_page","count(*)");

        return $total;
    }

    /**
     * {@inheritdoc}
     */
    public function updatePage(int $id_page, array $data_item): Page
    {
        $this->db->where("id_page", $id_page);
        $this->db->update('w_page', $data_item);

        $this->db->where("id_page", $id_page);
        $item = $this->db->getOne("w_page");
        if (empty($item)) {
            throw new PageNotFoundException();
        }
        return new Page($item['id_page'], $item);
    }

    /**
     * {@inheritdoc}
     */
    public function deletePage(int $id_page)
    {
        $this->db->where("id_page", $id_page);
        $item = $this->db->getOne("w_page");
        if ($item) {
            $this->db->where("id_page", $id_page);
            $this->db->update('w_page', ['is_deleted'=>1]);
        }

    }

}
