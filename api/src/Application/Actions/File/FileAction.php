<?php

declare(strict_types=1);

namespace App\Application\Actions\File;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\File\FileRepository;
use Psr\Log\LoggerInterface;
use Aws\S3\S3Client;

abstract class FileAction extends Action
{
    /**
     * @var db
     */
    protected $db;
    /**
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * @var Aws\S3\S3Client
     */
    protected $s3;


    /**
     * @param LoggerInterface $logger
     * @param FileRepository $fileRepository
     * @param SettingsInterface $settings
     * @param \MysqliDb $db
     * @param S3Client $s3Client
     */
    public function __construct(
        LoggerInterface $logger,
        FileRepository $fileRepository,
        SettingsInterface $settings,
        \MysqliDb $db,
        S3Client $s3Client,
    ) {
        parent::__construct($logger);
        $this->fileRepository = $fileRepository;
        $this->settings = $settings;
        $this->db = $db;
        $this->s3 = $s3Client;
    }
}
