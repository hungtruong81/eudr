<?php

declare(strict_types=1);

namespace App\Application\Actions\Land;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListSharedLandAction extends LandAction
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

        $validator = new Validator($this->request);

        $validator->validate('shared_with_user_code', $formData['shared_with_user_code'] ?? null, 'required|string');
        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('status', $formData['status'] ?? null, 'required|string|in:all,active,revoked');

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
            'shared_with_user_code' => 'string',
            'status' => 'string',
            'page' => 'integer',
            'limit' => 'integer',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $shared_with_user_code = $cleanData['shared_with_user_code'];
        $status = $cleanData['status'];
        $page = $cleanData['page'];
        $limit = $cleanData['limit'];

        $shared_land_user = $this->userRepository->findUserOfCode($shared_with_user_code);
        if (empty($shared_land_user)) {
            throw new HttpNotFoundException($this->request, "Người dùng được chia sẻ không tồn tại");
        }
        
        $params = [
            "shared_with_user_id" => $shared_land_user->getId(),
            "user_id" => $this->auth_data['user_id'],
            "page" => $page,
            "page_limit" => $limit,
            "status" => $status,
        ];

        $lands = $this->landRepository->getSharedLandByUser($params);

        // Prepare data for response
        /*
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
        */
        $res_return = ["result" => "success"];
        $res_return['data'] = $lands;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
