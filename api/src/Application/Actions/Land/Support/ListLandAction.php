<?php

declare(strict_types=1);

namespace App\Application\Actions\Land\Support;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListLandAction extends LandSupportAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        //echo $trace_id;die;
        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }
        
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to view land support
        $scope = Utils::resolveScope($permissions, 'land.support', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $user_role = $this->userRepository->getUserRole($this->auth_data['user_id']);
        
        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);


        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('is_approved', $formData['is_approved'] ?? null, 'integer|min:-1|max:2');
        $validator->validate('province_id', $formData['province_id'] ?? null, 'integer|min:0');
        $validator->validate('eudr_status', $formData['eudr_status'] ?? null, 'integer|min:0|max:2');
        $validator->validate('farmer_user_id', $formData['farmer_user_id'] ?? null, 'integer|min:0');
        $validator->validate('search', $formData['search'] ?? null, 'string');

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
            'is_approved' => 'integer',
            'province_id' => 'integer',
            'eudr_status' => 'integer',
            'farmer_user_id' => 'integer',
            'search' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $search = $cleanData['search'] ?? null;
        $is_approved = $cleanData['is_approved'] ?? -1;
        $province_id = $cleanData['province_id'] ?? 0;
        $eudr_status = $cleanData['eudr_status'] ?? -1;
        $farmer_user_id = $cleanData['farmer_user_id'] ?? 0;

        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "search" => $search,
            "is_approved" => $is_approved,
            "province_id" => $province_id,
            "eudr_status" => $eudr_status,
            "farmer_user_id" => $farmer_user_id,
            "user_id" => $this->auth_data['user_id'],
        ];

        $lands = $this->landRepository->findLandSupport(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        
        // Prepare data for response
        $files_map = $this->fileRepository->mapFileIdsToMap((array)$lands['all_file_ids']);
        
        foreach ($lands['records'] as &$land) {
            // Convert Land object to array for JSON serialization
            $land = $land->jsonSerialize();
            if (!empty($land['land_document_detection'])) {
                $land['land_document_detection'] = $this->settings->get('url_cdn') . '/' . ltrim($land['land_document_detection'], '/');
            }
            $new_land_records = [];
            foreach ($land['land_records'] as $fid) {
                if (!empty($files_map[$fid])) {
                    $new_land_records[$fid] = $files_map[$fid];
                }
            }
            $land['land_records'] = $new_land_records;

        }
        
        unset($lands['all_file_ids']);

        $res_return = ["result" => "success"];
        $res_return['data'] = $lands;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
