<?php

declare(strict_types=1);

namespace App\Application\Actions\Land;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class RevokeShareLandAction extends LandAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        
        // Check authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('plot_code', $formData['plot_code'] ?? null, 'required|string');
        $validator->validate('shared_with_user_code', $formData['shared_with_user_code'] ?? null, 'required|string');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        // Sanitize and extract data
        $sanitizeRules = [
            'plot_code' => 'string',
            'shared_with_user_code' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $plot_code = $cleanData['plot_code'];
        $shared_with_user_code = $cleanData['shared_with_user_code'];

        $land = $this->landRepository->findLandOfCode($plot_code);
        if (empty($land)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô đất");
        }

        $plot_id = $land->getId();
        // Find user by user code
        $shared_with_user = $this->userRepository->findUserOfCode($shared_with_user_code);
        if (empty($shared_with_user)) { 
            throw new HttpNotFoundException($this->request, "Không tìm thấy người dùng được chia sẻ");
        }

        $shared_with_user_id = $shared_with_user->getId();

        // Revoke share
        $this->landRepository->revokeShareLand($plot_id, $this->auth_data['user_id'], $shared_with_user_id);

        $action = 'revoke_share';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'land',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$land->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
