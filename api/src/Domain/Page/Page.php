<?php

declare(strict_types=1);

namespace App\Domain\Page;

use JsonSerializable;

class Page implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $id_page;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $page_id;
    /**
     * @var string
     */
    private $instagram_user_id;
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $logo_url;
    /**
     * @var string
     */
    private $page_code;
    /**
     * @var timestamp
     */
    private $created_time;
    /**
     * @var timestamp
     */
    private $updated_time;

    /**
     * @param int|null  $id_page
     * @param array    $data
     */
    public function __construct(?int $id_page, array $data)
    {
        $this->id_page = $id_page;
        $this->page_code = $data['page_code']??'';
        $this->instagram_user_id = $data['instagram_user_id']??'';
        $this->name = $data['name']??'';
        $this->page_id = $data['fb_page_id']??'';
        $this->url = $data['url']??'';
        $this->logo_url = $data['logo_url']??'';
        $this->created_time = $data['created_time']??'';
        $this->updated_time = $data['updated_time']??'';

    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id_page;
    }
    /**
     * @return string|null
     */
    public function getPageId(): ?string
    {
        return $this->page_id;
    }
    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->page_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'page_code' => $this->page_code,
            'instagram_user_id' => $this->instagram_user_id,
            'name' => $this->name,
            'page_id' => $this->page_id,
            'url' => $this->url,
            'logo_url' => $this->logo_url,
            'created_time' => $this->created_time,
            'updated_time' => $this->updated_time,
        ];
    }
}
