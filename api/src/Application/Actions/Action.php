<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\DomainException\DomainRecordErrorException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

abstract class Action
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var object
     */
    protected $auth;
    /**
     * @var object
     */
    protected $auth_data;

    /**
     * @var object
     */
    protected $permission;
    /**
     * @var array
     */
    protected $settings;
    /**
     * @var array
     */
    protected $cache;
    /**
     * @var string
     */
    protected $token;

    /**
     * @var memcached
     */
    protected $memcached;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        $this->permission = array();
        if (!empty($request->getHeader('Authorization'))) {
            $this->token = $this->request->getAttribute('token') ?? null;
            $this->permission = $this->request->getAttribute('permission') ?? array();
            $this->auth = $this->request->getAttribute('auth') ?? array();
            $this->auth_data = $this->request->getAttribute('auth_data') ?? [];
            $this->cache = $this->request->getAttribute('cache') ?? [];
        }
        $this->token = $this->request->getAttribute('token') ?? null;
        $this->cache = $this->request->getAttribute('cache') ?? [];

        try {
            return $this->action();
        } catch (DomainRecordNotFoundException $e) {
            // throw new HttpNotFoundException($this->request, $e->getMessage());
            $error = new ActionError(
                ActionError::RESOURCE_NOT_FOUND,
                $e->getMessage(),
                $e->getCode()
            );
            $payload = new ActionPayload(200, null, $error, false);
            return $this->respond($payload);
        } catch (DomainRecordErrorException $e) {
            $error = new ActionError(
                ActionError::RESOURCE_ERROR,
                $e->getMessage(),
                $e->getCode()
            );
            $payload = new ActionPayload(200, null, $error, false);
            return $this->respond($payload);
        }
    }

    /**
     * @return Response
     * @throws DomainRecordNotFoundException
     * @throws HttpBadRequestException
     */
    abstract protected function action(): Response;

    /**
     * @return array|object
     * @throws HttpBadRequestException
     */
    protected function getFormData()
    {
        $input_php = file_get_contents('php://input');
        $input = array();

        if (!empty($_POST)) {
            $input = $_POST;
        } elseif ($input_php) {
            $input = json_decode($input_php, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new HttpBadRequestException($this->request, 'Malformed JSON input.');
            }
        }
        // $input = Utils::camelCaseKeys($input);

        return $input;
    }

    /**
     * @param  string $name
     * @return mixed
     * @throws HttpBadRequestException
     */
    protected function resolveArg(string $name)
    {
        if (!isset($this->args[$name])) {
            throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
        }

        return $this->args[$name];
    }

    /**
     * @param array|object|null $data
     * @param int $statusCode
     * @return Response
     */
    protected function respondWithData($data = null, int $statusCode = 200, bool $cache = false): Response
    {
        $payload = new ActionPayload($statusCode, $data);

        return $this->respond($payload);
    }


    /**
     * @param ActionPayload $payload
     * @return Response
     */
    protected function respond(ActionPayload $payload): Response
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($json);

        if ($payload->getStatusCode()==500 || $payload->getStatusCode()==400) {
            $error_data = $payload->getData();

            $errorType = $errorMessage = $trace_id = '';
            if (!empty($error_data['error'])) {
                $errorType = $error_data['error']['type'] ?? '';
                $errorMessage = $error_data['error']['description'] ?? '';
                $trace_id = $error_data['trace_id'] ?? '';
            }

            $log = array(
                "milliseconds" => floor(microtime(true) * 1000),
                "path" => '',
                "region" => '',
                "game_id" => '',
                "user_id" => '',
                "ga_id" => '',
                "utm_source" => '',
                "utm_medium" => '',
                "utm_campaign" => '',
                "payment_category" => '',
                "channel" => '',
                "amount" => '',
                "message" => '',
                "log_action" => 'ERROR_DATA',
                "log_group" =>  "Error",
                "extra1" => $errorType,
                "extra2" => $payload->getStatusCode(),
                "extra3" => str_replace("\n", " ", $errorMessage),
                "extra4" => $trace_id,
            );

            $log_save = implode("|", array_values($log));
            $this->logger->notice($log_save);
        }

        return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus($payload->getStatusCode());
    }
}
