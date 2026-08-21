<?php

declare(strict_types=1);

namespace App\Application\Actions\Driver;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Driver\DriverErrorException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Slim\Exception\HttpBadRequestException;

class ListDriverAction extends DriverAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        
        if (empty($this->auth_data['user_id'])) {
            throw new DriverErrorException("Thiếu quyền truy cập", 113);
        }

        // Check permission
        /*
        $permission_status = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'vehicle', 'view');

        if (empty($permission_status)) {
            throw new DriverErrorException("Thiếu quyền truy cập", 113);
        }
        */
        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');

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
            'page' => 'integer',
            'limit' => 'integer',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "user_id" => $this->auth_data['user_id'],
        ];

        $drivers = $this->driverRepository->findAll($params);

        $res_return = ["result" => "success"];
        $res_return['data'] = $drivers;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
