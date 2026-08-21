<?php

declare(strict_types=1);

namespace App\Application\Actions\Cron;

use App\Application\Settings\SettingsInterface;
use App\Application\Actions\Action;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use Predis\Client as PredisClient;

abstract class CronAction extends Action
{
    /**
     * @var db
     */
    protected $db;

    /**
     * @var settings
     */
    protected $settings;

    /**
     * @var client
     */
    protected $httpClient;
    /**
     * @var PredisClient
     */
    // protected $predis;
    /**
     * @var Memcached
     */
    protected $memcached;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger,
        SettingsInterface $settings,
        \MysqliDb $db,
        Client $httpClient,
        //\Memcached $memcached
        // PredisClient $predis
    ) {
        parent::__construct($logger);
        $this->db = $db;
        $this->httpClient = $httpClient;
        $this->settings = $settings;
        //$this->memcached = $memcached;
        // $this->predis = $predis;

        // $this->csrf = $csrf;


        if (!empty($this->request) && !empty($this->request->getHeader('Authorization'))) {
            $this->token = $this->request->getAttribute('token') ?? null;
        }
    }
}
