<?php

declare(strict_types=1);

namespace App\Domain\File;

use JsonSerializable;

class File implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $file_id;
    /**
     * @var int|null
     */
    private $user_id;
    /**
     * @var int|null
     */
    private $is_embedded;
    /**
     * @var int|null
     */
    private $is_deleted;
    /**
     * @var string
     */
    private $file_name;
    /**
     * @var string
     */
    private $file_path;
    /**
     * @var int|null
     */
    private $file_size;
    /**
     * @var string
     */
    private $image_size;
    /**
     * @var string
     */
    private $file_code;
    /**
     * @var string
     */
    private $fb_image_hash;
    /**
     * @var string
     */
    private $fb_video_id;
    /**
     * @var string
     */
    private $video_thumbnail_path;
    /**
     * @var string
     */
    private $video_thumbnail_path_full;
    /**
     * @var string
     */
    private $video_thumbnail_error;

    /**
     * @var timestamp
     */
    private $created_at;
    /**
     * @var timestamp
     */
    private $updated_at;

    /**
     * @param int|null  $file_id
     * @param array    $data
     */
    public function __construct(?int $file_id, array $data)
    {
        $this->file_id = $file_id;
        $this->file_code = $data['file_code']??'';
        $this->user_id = $data['user_id']??0;
        $this->is_embedded = $data['is_embedded']??0;
        $this->file_name = $data['file_name']??'';
        $this->file_path = $data['file_path']??'';
        $this->file_size = $data['file_size']??0;
        $this->image_size = $data['image_size']??'';
        $this->is_deleted = $data['is_deleted']??0;
        $this->created_at = $data['created_at']??0;
        $this->updated_at = $data['updated_at']??0;
        $this->fb_image_hash = $data['fb_image_hash']??'';
        $this->fb_video_id = $data['fb_video_id']??'';
        $this->video_thumbnail_path = $data['video_thumbnail_path']??'';
        $this->video_thumbnail_error = $data['video_thumbnail_error']??'';

    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->file_id;
    }
    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->user_id;
    }
    /**
     * @return int|null
     */
    public function getIsEmbedded(): ?int
    {
        return $this->is_embedded;
    }
    /**
     * @return string|null
     */
    public function getFileCode(): ?string
    {
        return $this->file_code;
    }
    /**
     * @return string|null
     */
    public function getFileName(): ?string
    {
        return $this->file_name;
    }
    /**
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->file_path;
    }
    /**
     * @return string|null
     */
    public function getImageSize(): ?string
    {
        return $this->image_size;
    }
    /**
     * @return string|null
     */
    public function getVideoThumbnailPath(): ?string
    {
        return $this->video_thumbnail_path;
    }
    /**
     * @param string $video_thumbnail_path_full
     * @return self
     */
    public function setVideoThumbnailPathFull(string $video_thumbnail_path_full): self
    {
        $this->video_thumbnail_path_full = $video_thumbnail_path_full;
        return $this;
    }
    /**
     * @return string|null
     */
    public function getVideoThumbnailPathFull(): ?string
    {
        return $this->video_thumbnail_path_full;
    }
    /**
     * @return string|null
     */
    public function getFbImageHash(): ?string
    {
        return $this->fb_image_hash;
    }
    /**
     * @return string|null
     */
    public function getFbVideoId(): ?string
    {
        return $this->fb_video_id;
    }
    /**
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return [
            'file_id' => $this->file_id,
            'file_code' => $this->file_code,
            'file_type' => $this->getFileType(),
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'image_size' => $this->image_size,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // 'fb_image_hash' => $this->fb_image_hash,
            // 'fb_video_id' => $this->fb_video_id,
            // 'video_thumbnail_path' => $this->video_thumbnail_path,
            // 'video_thumbnail_error' => $this->video_thumbnail_error,
            // 'video_thumbnail_path_full' => $this->video_thumbnail_path_full,
        ];
    }

    public function getFileType(): string
    {
        $path_extension = pathinfo($this->file_path, PATHINFO_EXTENSION);
        if ($path_extension === 'png' || $path_extension === 'jpg' || $path_extension === 'jpeg' || $path_extension === 'gif') {
            return 'image';
        } elseif ($path_extension === 'pdf') {
            return 'pdf';
        } elseif ($path_extension === 'mp4' || $path_extension === 'avi' || $path_extension === 'mov' || $path_extension === 'wmv') {
            return 'video';
        }
        return 'unknown';
    }

}
