<?php

declare(strict_types=1);

namespace App\Application\Actions\File;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\File\FileErrorException;
use App\Application\Utility\Utils;
use Slim\Exception\HttpUnauthorizedException;

class DeleteFileAction extends FileAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        // Validate data fields
        $required_fields = ['file_code'];
        $missing_fields = Utils::validFields($required_fields,$formData);
        if (!empty($missing_fields)) {
            throw new FileErrorException("MISSING ".implode(", ",$missing_fields), 101);
        }

        $file_code = trim($formData['file_code']);
        $file = $this->fileRepository->findFileOfCode($file_code, 0);
        if (empty($file)) {
            throw new FileErrorException("FILE_NOT_FOUND", 101);
        }

        $this->fileRepository->deleteFile($file->getId());

        $action = 'delete';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'file',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$file->getId(),
        );
        Utils::save_log($this->logger,$log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
