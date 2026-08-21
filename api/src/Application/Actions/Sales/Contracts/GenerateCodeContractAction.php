<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Contracts;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use App\Application\Utility\Utils;

class GenerateCodeContractAction extends ContractAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }
        /*
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'sales_contract', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }
        */
        $contract_code = $this->salesContractRepository->generateCode();

        $action = 'generate_code';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'sales_contract',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$contract_code,
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = ['contract_code' => $contract_code];

        return $this->respondWithData($res_return);
    }
}
