<?php

declare(strict_types=1);

namespace App\Application\Actions\Land;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListUserSharedLandAction extends LandAction
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

        $formData = $this->request->getQueryParams();

        $plot_code = addslashes(trim((string)$this->resolveArg('code')));
        if(empty($plot_code)) {
            throw new HttpBadRequestException($this->request, "Thiếu mã lô đất");
        }

        $land = $this->landRepository->findLandOfCode($plot_code);
        if (empty($land)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô đất");
        }
        
        $plot_id = $land->getId();

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
        ];

        $list_user_shared = $this->landRepository->getListUserSharedLand($plot_id, $this->auth_data['user_id'], $params);

        return $this->respondWithData($list_user_shared);
    }

}
