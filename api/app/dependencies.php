<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use MysqliDb as MysqliDb;
use App\Application\Utility\FileLogger;
use OpenAI\Client as OpenAIClient;
use Aws\S3\S3Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PHPMailer\PHPMailer\PHPMailer as PHPMailer;
use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\RequestCurrentUserContext;

include_once __DIR__."/../library/ScribeLib/LogAgent.php";


return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $logAgent = new FileLogger();
            return $logAgent;
        },
        /* LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $loggerSettings = $settings->get('scriberLogger');
            $logAgent = new LogAgent($loggerSettings['scribe_servers'], $loggerSettings['scribe_ports'], $loggerSettings['allow']);

            return $logAgent;
        }, */

        // LoggerInterface::class => \DI\autowire(FileLogger::class),
        Client::class => function (ContainerInterface $c) {
            $client = new Client();
            return $client;
        },
        S3Client::class => function (ContainerInterface $c) {
            $version = 'latest';
            $region = 'ap-southeast-1';
            $settings = $c->get(SettingsInterface::class);
            $AWS_S3_key = $settings->get('s3')["AWS_S3_key"];
            $AWS_S3_secret = $settings->get('s3')["AWS_S3_secret"];
            define("AWS_S3_bucket", $settings->get('s3')["AWS_S3_bucket"]);

            $s3 = new \Aws\S3\S3Client([
                'version'  => $version,
                'region'   => $region,
                'credentials' => [
                    'key'    => $AWS_S3_key,
                    'secret' => $AWS_S3_secret,
                ]
            ]);
            return $s3;
        },
        /*
        Memcached::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $memcachedSettings = $settings->get('memcached');
            $memcached = new Memcached();
            $memcached->addServer($memcachedSettings['host'], intval($memcachedSettings['port']));
            return $memcached;
        },
        */
        OpenAIClient::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $yourApiKey = $settings->get('openApiKey');
            $client = OpenAI::factory()
                ->withApiKey($yourApiKey)
                // ->withOrganization('your-organization') // default: null
                // ->withBaseUri('openai.example.com/v1') // default: api.openai.com/v1
                ->withHttpClient($client = new \GuzzleHttp\Client([])) // default: HTTP client found using PSR-18 HTTP Client Discovery
                // ->withHttpHeader('X-My-Header', 'foo')
                // ->withQueryParam('my-param', 'bar')
                ->withStreamHandler(fn (RequestInterface $request): ResponseInterface => $client->send($request, [
                    'stream' => true // Allows to provide a custom stream handler for the http client.
                ]))
                ->make();

            return $client;
        },
        /* PredisClient::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $redisSettings = $settings->get('redis');

            $client = new Predis\Client([
                'scheme' => 'tcp',
                'host'   => $redisSettings['host'],
                'port'   => $redisSettings['port'],
            ]);
            return $client;
        }, */
        MysqliDb::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $dbSettings = $settings->get('db');

            $db = new MysqliDb(
                array(
                'host' => $dbSettings['host'],
                'username' => $dbSettings['user'],
                'password' => $dbSettings['password'],
                'db'=> $dbSettings['database'],
                'port' => $dbSettings['port'],
                'prefix' => $dbSettings['prefix'],
                'charset' => 'utf8mb4')
            );
            $db->query("SET time_zone = '+00:00';");

            return $db;
        },
        PHPMailer::class => function (ContainerInterface $c) {
            
            $settings = $c->get(SettingsInterface::class);
            $mailSettings = $settings->get('mail');
            
            $phpMailer = new PHPMailer(true);
            $phpMailer->CharSet = "UTF-8";
            $phpMailer->isSMTP();
            $phpMailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $phpMailer->SMTPDebug = 0;
            $phpMailer->Debugoutput = 'html';
            $phpMailer->Host = $mailSettings["host"];
            $phpMailer->Port = $mailSettings["port"];
            $phpMailer->SMTPAuth = true;
            $phpMailer->Username = $mailSettings["username"];
            $phpMailer->Password = $mailSettings["password"];
            $phpMailer->SMTPSecure = 'tls';
            //Set who the message is to be sent from
            $phpMailer->setFrom($mailSettings["send_from"], $mailSettings["send_name"]);
            return $phpMailer;
        },
        // Get current user context from request
        CurrentUserContext::class => function (ContainerInterface $c) {
            return new RequestCurrentUserContext();
        },
    ]);
};
