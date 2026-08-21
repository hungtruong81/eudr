<?php

declare(strict_types=1);

namespace App\Application\Actions\Land;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ShareLandAction extends LandAction
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

        $validator->validate('plot_ids', $formData['plot_ids'] ?? null, 'required|array');
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
            //'plot_ids' => 'array',
            'shared_with_user_code' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $plot_ids = $formData['plot_ids'] ?? [];
        $shared_with_user_code = $cleanData['shared_with_user_code'];

        // Find user to share with
        $user_to_share = $this->userRepository->findUserOfCode($shared_with_user_code);
        if (empty($user_to_share)) {
            throw new HttpNotFoundException($this->request, "Thông tin người chia sẽ không tìm thấy");
        }

        // Check connection exists
        $connection = $this->connectionRepository->findConnectionBetweenUsers($this->auth_data['user_id'], $user_to_share->getId(), $status = "accepted");
        if (empty($connection)) {
            throw new HttpForbiddenException($this->request, "Chưa có thông tin kết nối, Vui lòng kết nối trước khi chia sẻ");
        }

        $plot_ids = $this->landRepository->findLandIdsOfOwner($plot_ids, $this->auth_data['user_id']);
        if(empty($plot_ids)) {
            throw new HttpBadRequestException($this->request, "Không có mảnh đất hợp lệ để chia sẻ");
        }

        $this->landRepository->shareLand($plot_ids, $user_to_share->getId(), $this->auth_data['user_id']);
        
        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        
        return $this->respondWithData($res_return);

    }
}
