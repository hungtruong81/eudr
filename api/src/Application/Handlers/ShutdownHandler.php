<?php
declare(strict_types=1);

namespace App\Application\Handlers;

use App\Application\ResponseEmitter\ResponseEmitter;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpInternalServerErrorException;

class ShutdownHandler
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var HttpErrorHandler
     */
    private $errorHandler;

    /**
     * @var bool
     */
    private $displayErrorDetails;

    /**
     * ShutdownHandler constructor.
     *
     * @param Request           $request
     * @param HttpErrorHandler  $errorHandler
     * @param bool              $displayErrorDetails
     */
    public function __construct(
        Request $request,
        HttpErrorHandler $errorHandler,
        bool $displayErrorDetails
    ) {
        $this->request = $request;
        $this->errorHandler = $errorHandler;
        $this->displayErrorDetails = $displayErrorDetails;
    }

    public function __invoke()
    {
        $error = error_get_last();
        if ($error) {
            $errorFile = $error['file'];
            $errorLine = $error['line'];
            $errorMessage = $error['message'];
            $errorType = $error['type'];
            $message = 'An error while processing your request. Please try again later.';

            if ($this->displayErrorDetails) {
                switch ($errorType) {
                    case E_USER_ERROR:
                        $message = "FATAL ERROR: {$errorMessage}. ";
                        $message .= " on line {$errorLine} in file {$errorFile}.";
                        break;

                    case E_USER_WARNING:
                        $message = "WARNING: {$errorMessage}";
                        $message .= " on line {$errorLine} in file {$errorFile}.";
                        break;
                    case E_WARNING:
                        $message = "WARNING: {$errorMessage}";
                        $message .= " on line {$errorLine} in file {$errorFile}.";
                        break;

                    case E_USER_NOTICE:
                        $message = "NOTICE: {$errorMessage}";
                        break;

                    default:
                        $message = "ERROR: {$errorMessage}";
                        $message .= " on line {$errorLine} in file {$errorFile}.";
                        break;
                }
            }

            /* $log = array(
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
                "log_action" => 'SERVER_ERROR',
                "log_group" =>  "Error",
                "extra1" =>  $errorType,
                "extra2" =>  $errorFile,
                "extra3" =>  $errorLine,
                "extra4" => str_replace(array("\r\n","\r"),"",$errorMessage),
            );

            $log_save = implode("|", array_values($log));
            $this->logger->notice($log_save); */

            $exception = new HttpInternalServerErrorException($this->request, $message);
            $response = $this->errorHandler->__invoke($this->request, $exception, $this->displayErrorDetails, false, false);

            $responseEmitter = new ResponseEmitter();
            $responseEmitter->emit($response);
        }
    }
}
