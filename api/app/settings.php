<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => ($_ENV['ENV']!="production" || !empty($_GET["debug"])), // Should be set to false in production
                'logError'            => true,
                'logErrorDetails'     => true,
                'logger' => [
                    'name' => 'eudr_2025_apis',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                'scriberLogger' => [
                    'scribe_servers' => ['127.0.0.1'],
                    'scribe_ports' => ['1448'],
                    'allow' => false,
                ],
                'env' => $_ENV['ENV'],
                'url_api' => $_ENV['API_URL'],
                'url_web' => $_ENV['APP_URL'],
                'url_cdn' => $_ENV['CDN_URL'],
                'dir_home' => $_ENV['DIR_HOME'],
                'secretKey' => $_ENV['SECRET_KEY'],
                'authentication_private_key' => $_ENV['AUTHENTICATION_PRIVATE_KEY'],
                'authentication_public_key' => $_ENV['AUTHENTICATION_PUBLIC_KEY'],
                'openApiKey' => $_ENV['OPENAI_KEY'],
                'googleAiApiKey' => $_ENV['GOOGLE_AI'],
                'reCAPTCHA_key' => $_ENV['reCAPTCHA_key'],
                'reCAPTCHA_secret_key' => $_ENV['reCAPTCHA_secret_key'],
                'memcached' => [
                    'host' => $_ENV['MEMCACHED_HOST'],
                    'port' => $_ENV['MEMCACHED_PORT'],
                ],
                'db' => [
                    'host' => $_ENV['MYSQL_HOST'],
                    'prefix' => '',
                    'port' => $_ENV['MYSQL_PORT'],
                    'database' => $_ENV['MYSQL_DATABASE'],
                    'user' => $_ENV['MYSQL_USERNAME'],
                    'password' => $_ENV['MYSQL_PASSWORD']
                ],
                's3' => [
                    'AWS_S3_key' => $_ENV['AWS_S3_key'],
                    'AWS_S3_secret' => $_ENV['AWS_S3_secret'],
                    'AWS_S3_bucket' => $_ENV['AWS_S3_bucket'],
                ],
                'google' => [
                    'project_id' => $_ENV['GOOGLE_PROJECT_ID'],
                    'location' => $_ENV['GOOGLE_LOCATION'],
                    'processor_id' => $_ENV['GOOGLE_PROCESSOR_ID'],
                    'application_credentials' => $_ENV['GOOGLE_APPLICATION_CREDENTIALS'],
                ],
                'mail' => [
                    'host' => $_ENV['MAIL_HOST'],
                    'port' => $_ENV['MAIL_PORT'],
                    'username' => $_ENV['MAIL_USERNAME'],
                    'password' => $_ENV['MAIL_PASSWORD'],
                    'send_from' => 'tuongbuidev@gmail.com',
                    'send_name' => 'EUDR System',
                ],
                'sms' => [
                    'api_url' => $_ENV['SMS_API_URL'],
                    'api_key' => $_ENV['SMS_API_KEY'],
                    'secret_key' => $_ENV['SMS_SECRET_KEY'],
                    'brand_name' => $_ENV['SMS_BRAND_NAME'],
                    'templates' => [
                        'test' => 'Cam on ban da dang ky thanh vien {code}. Tu nay, ban se nhan duoc cac thong bao, tu van nong nghiep.',
                        'otp' => '{code} Ma xac thuc tai khoan tai SustainAgri. Ma het han sau 5 phut',
                    ]
                ],
            ]);
        }
    ]);
};
