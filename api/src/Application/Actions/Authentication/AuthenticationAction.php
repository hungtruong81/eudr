<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use App\Application\Actions\Action;
use Psr\Log\LoggerInterface;
use App\Application\Settings\SettingsInterface;
use GuzzleHttp\Client;
use Predis\Client as PredisClient;
use App\Domain\User\UserRepository;
use App\Domain\Sms\SmsRepository;
use App\Domain\Company\CompanyRepository;

abstract class AuthenticationAction extends Action
{
    /**
     * @var UserRepository
    */
    protected $userRepository;
    /**
     * @var SmsRepository
     */
    protected $smsRepository;
    /**
     * @var CompanyRepository
     */
    protected $companyRepository;
    /**
     * @var client
     */
    protected $httpClient;
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var MysqliDb
     */
    protected $db;

    /**
     * @var Memcached
     */
    // protected $memcached;
    /**
     * @var PredisClient
     */
    // protected $predis;

    /**
     * @param Client $httpClient
     * @param UserRepository $userRepository
     * @param SmsRepository $smsRepository
     * @param CompanyRepository $companyRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger,
        SettingsInterface $settings,
        \MysqliDb $db,
        Client $httpClient,
        UserRepository $userRepository,
        SmsRepository $smsRepository,
        CompanyRepository $companyRepository
        // \Memcached $memcached
        // PredisClient $predis
    ) {
        parent::__construct($logger);

        $this->httpClient = $httpClient;
        $this->settings = $settings;
        // $this->memcached = $memcached;
        $this->db = $db;
        // $this->predis = $predis;
        $this->userRepository = $userRepository;
        $this->smsRepository = $smsRepository;
        $this->companyRepository = $companyRepository;
    }
}
