<?php

declare(strict_types=1);

namespace App\Application\Actions\General;

use Psr\Http\Message\ResponseInterface as Response;
use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\RawDocument;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\ApiCore\ApiException;
use App\Application\Utility\Utils;

class GoogleDocumentExtractorAction extends GeneralAction
{
    protected function action(): Response
    {

        // Cấu hình thông tin
        $projectId = 'eudr-project'; // 196979076798
        $location = 'us'; // hoặc 'asia-southeast1' nếu dùng trong khu vực châu Á
        $processorId = 'a975d9197e538ec4';
        $credentialsPath = __DIR__ . '/../../../../config/eudr-project-5cc9d13718d6.json';
        //$filePath = __DIR__ . '/../../../../config/BM_631363_250616_144137.pdf';
        $filePath = __DIR__ . '/../../../../config/BM_631365_250616_144234.pdf';
        //$filePath = __DIR__ . '/../../../../config/example.jpg';

        putenv("GOOGLE_APPLICATION_CREDENTIALS={$credentialsPath}"); 

        try {
            $client = new DocumentProcessorServiceClient();
            $processorName = $client->processorName($projectId, $location, $processorId);

            $content = file_get_contents($filePath);
            $rawDocument = new RawDocument([
                'content' => $content,
                //'mime_type' => 'image/jpeg', // hoặc 'application/pdf'
                'mime_type' => 'application/pdf',
            ]);

            $request = new ProcessRequest([
                'name' => $processorName,
                'raw_document' => $rawDocument,
            ]);

            $response = $client->processDocument($request);
            $document = $response->getDocument();
            $text = $document->getText();
            $entities = $document->getEntities();
            // Log toàn bộ nội dung và entity
            $coordinates = [];
            $d_text = '';
            
            foreach ($entities as $entity) {
                if ($entity->getType() === 'coordinates') {
                    $d_text = $entity->getMentionText();
                    break;
                }
            }

            $coordinates = Utils::extractCoordinates($d_text);
            

            $results = [
                'full_text' => $text ?? 'No text found',
                'entities' => [],
                'coordinates' => $coordinates,
            ];

            return $this->respondWithData($results);
        } catch (ApiException $e) {
            $data_return = [
                "result" => "error",
                "message" => "Google API error: " . $e->getMessage(),
            ];
            return $this->respondWithData($data_return);
        } catch (\Exception $e) {
            $data_return = [
                "result" => "error",
                "message" => "Internal error: " . $e->getMessage(),
            ];
            return $this->respondWithData($data_return);
        }
    }
}
