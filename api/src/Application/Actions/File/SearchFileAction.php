<?php

declare(strict_types=1);

namespace App\Application\Actions\File;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\File\FileErrorException;

use App\Application\Utility\Utils;

class SearchFileAction extends FileAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        // Validate API type
        if (empty($this->auth_data['user_id'])) {
            throw new FileErrorException("PERMISSION_DENIED", 113);
        }

        $formData = $this->request->getQueryParams();

        // Validate data fields
        $required_fields = ['query'];
        $missing_fields = Utils::validFields($required_fields,$formData);
        if (!empty($missing_fields)) {
            throw new FileErrorException("MISSING ".implode(", ",$missing_fields), 101);
        }
        $text_query = trim($formData['query']);


        $file_code = trim($this->resolveArg('code'));
        $file = $this->fileRepository->findFileOfCode($file_code, 0);
        if (empty($file)){
            throw new FileErrorException("FILE_NOT_FOUND", 101);
        }

        $db_embeddings = $this->fileRepository->getEmbeddings($file->getId());

        // Query vector
        // Embedding text vector
        $query_embeddings = Utils::getEmbedding($text_query,$this->openAIClient);

        /* $text_splitter = new RecursiveCharacterTextSplitter(["chunk_size"=>1000,"chunk_overlap"=> 200]);
        $texts = $text_splitter->splitText($text_query);
        $query_embeddings   = [];
        $chunkSize = 1000;
        $documentModelName = 'text-embedding-3-small';

        for ($i = 0; $i < count($texts); $i += $chunkSize) {
            $input = array_slice($texts, $i, $chunkSize);
            $response = $this->embedWithRetry(
                [
                    'input' => $input,
                    'model' => $documentModelName
                ]
            );

            foreach ($response['data'] as $data) {
                $query_embeddings[] = ["text"=>$input,"embedding"=>$data['embedding']];
            }
        } */

        $res = $this->getAnswer($db_embeddings, $query_embeddings);
        print_r($res);die();

        $action = 'search';
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

    private function getAnswer($db_embeddings, $query_embeddings)
    {
        // loops through all the inputs and compare on a cosine similarity to the question and output the correct answer
        $results = [];
        for ($i = 0; $i < count($db_embeddings); $i++) {
            $similarity = $this->cosineSimilarity($db_embeddings[$i]['embedding'], $query_embeddings[0]["embedding"]);
            // store the similarity and index in an array and sort by the similarity
            $results[] = [
                'similarity' => $similarity,
                'index' => $i,
                'input' => $db_embeddings[$i]['text'],
            ];
        }
        usort($results, function ($a, $b) {
            return $a['similarity'] <=> $b['similarity'];
        });
        return end($results);
    }
    private function cosineSimilarity($u, $v)
    {
        $dotProduct = 0;
        $uLength = 0;
        $vLength = 0;
        for ($i = 0; $i < count($u); $i++) {
            $dotProduct += $u[$i] * $v[$i];
            $uLength += $u[$i] * $u[$i];
            $vLength += $v[$i] * $v[$i];
        }
        $uLength = sqrt($uLength);
        $vLength = sqrt($vLength);
        return $dotProduct / ($uLength * $vLength);
    }
    /* private function embedWithRetry(array $params): array
    {
        $maxRetries = 6;
        $backoff = new Backoff($maxRetries, 'exponential', 10000, true);
        $result = $backoff->run(function () use ($params) {
            return $this->openAIClient->embeddings()->create($params);
        });

        return $result->toArray();
    } */
}
