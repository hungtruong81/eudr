<?php

declare(strict_types=1);

namespace App\Application\Utility;

use Firebase\JWT\JWT;
use App\Application\Libraries\TextSplitter\RecursiveCharacterTextSplitter;
use STS\Backoff\Backoff;
use \DateTime;

class Utils
{
    public static function clearGroupCache($group, $memcached) {
        $keys = $memcached->getAllKeys();
        foreach ($keys as $key) {
            if (strpos($key, $group) !== false) {
                $memcached->delete($key);
            }
        }
    }
    public static function getFacebookPageLogo($pageUrl) {
        // Extract username from the given URL
        preg_match('/facebook\.com\/([^\/?]+)/', $pageUrl, $matches);

        if (!isset($matches[1])) {
            return "Invalid Facebook page URL.";
        }

        $pageUsername = $matches[1];

        // Facebook profile picture URL
        return "https://graph.facebook.com/{$pageUsername}/picture?type=large";
    }

    public static function getAppLogo(string $url): ?string
    {
        // Determine if the URL is for Google Play Store or Apple App Store
        if (strpos($url, 'play.google.com') !== false) {
            return self::getGooglePlayLogo($url);
        } elseif (strpos($url, 'apps.apple.com') !== false) {
            return self::getAppleAppStoreLogo($url);
        } else {
            return ''; // Invalid URL
        }
    }

    public static function getGooglePlayLogo(string $url): ?string
    {
        // Extract the app ID from the URL
        preg_match('/id=([a-zA-Z0-9\.]+)/', $url, $matches);
        if (!isset($matches[1])) {
            return null; // Could not extract app ID
        }
        $appId = $matches[1];

        // Construct the API URL for the Google Play Store
        $apiUrl = "https://play.google.com/store/apps/details?id={$appId}&hl=en";

        // Fetch the HTML content of the app page
        $html = @file_get_contents($apiUrl);

        if ($html === false) {
            return null; // Failed to fetch the content
        }

        // Parse the HTML to find the logo URL
        preg_match('/<img[^>]+src="([^"]+)"[^>]+itemprop="image"[^>]*>/', $html, $logoMatches);

        if (isset($logoMatches[1])) {
            return $logoMatches[1]; // Return the logo URL
        } else {
            return null; // Logo URL not found
        }
    }

    public static function getAppleAppStoreLogo(string $url): ?string {
        $html = @file_get_contents($url);
        if ($html === false) {
            return null;
        }

        // Pattern to match the entire picture element
        if (preg_match('/<picture[^>]+class="[^"]*product-hero__artwork[^"]*"[^>]*>(.*?)<\/picture>/s', $html, $pictureMatches)) {
            $pictureHtml = $pictureMatches[0];

            $logos = [];

            // Extract WebP versions
            if (preg_match_all('/<source[^>]+srcset="([^"]+)"[^>]*>/', $pictureHtml, $sourceMatches)) {
                foreach ($sourceMatches[1] as $srcset) {
                    // Get all URLs from srcset
                    $urls = explode(',', $srcset);
                    foreach ($urls as $url) {
                        $cleanUrl = trim(preg_replace('/\s+\d+w$/', '', $url));
                        if (strpos($cleanUrl, 'mzstatic.com') !== false) {
                            $logos['webp'][] = $cleanUrl;
                        }
                    }
                }
            }

            // Extract PNG versions
            if (preg_match_all('/<source[^>]+srcset="([^"]+)"[^>]*type="image\/png"[^>]*>/', $pictureHtml, $pngMatches)) {
                foreach ($pngMatches[1] as $srcset) {
                    $urls = explode(',', $srcset);
                    foreach ($urls as $url) {
                        $cleanUrl = trim(preg_replace('/\s+\d+w$/', '', $url));
                        if (strpos($cleanUrl, 'mzstatic.com') !== false) {
                            $logos['png'][] = $cleanUrl;
                        }
                    }
                }
            }

            return array_unique($logos['png'] ?? [])[0];
        }

        return null;
    }



    public static function signed_url($s3, $bucket, $key, $expires = 3600)
    {
        $presignedUrl = '';
        if ($s3) {
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $key
            ]);

            $request = $s3->createPresignedRequest($cmd, '+'.$expires.' seconds');
            // Get the actual presigned-url
            $presignedUrl = (string)$request->getUri();
        }
        return $presignedUrl;
    }

    public static function searchKnowledge($knowledge_embeddings, $search_query)
    {
        $use_knowledge = [];
        $knowledge = [];
        $knowledge_text_id = [];
        $knowledge_scores = [];
        foreach ($search_query as $query_embedding) {
            // loops through all the inputs and compare on a cosine similarity to the question and output the correct answer
            $results = [];
            for ($i = 0; $i < count($knowledge_embeddings); $i++) {
                $item = $knowledge_embeddings[$i];
                $similarity = self::cosineSimilarity($item['embedding'], $query_embedding);
                // store the similarity and index in an array and sort by the similarity
                $results[] = [
                    'similarity' => $similarity,
                    'index' => $i,
                    'text_id' => $item["text_id"],
                    'input' => $item['text'],
                ];
            }
            usort($results, function ($a, $b) {
                return $a['similarity'] <=> $b['similarity'];
            });
            $results = array_reverse($results);
            $get_result = array_slice($results, 0, 3);
            $use_knowledge = array_merge($use_knowledge, $get_result);
        }
        foreach ($use_knowledge as $v) {
            $knowledge[] = $v['input'];
            $knowledge_text_id[] = $v['text_id'];
            $knowledge_scores[] = $v['similarity'];
        }
        // $knowledge = array_unique($knowledge);
        // $knowledge_text_id = array_unique($knowledge_text_id);

        // Get last 3 results
        // $knowledge = array_slice($knowledge, 0, 3);
        // $knowledge_text_id = array_slice($knowledge_text_id, 0, 3);

        return ["texts" => array_values($knowledge),"text_ids" => array_values($knowledge_text_id),"scores" => array_values($knowledge_scores)];
    }
    public static function cosineSimilarity($u, $v)
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
    public static function getEmbedding($text, $client)
    {
        $embeddings = [];
        // Embedding text vector
        $text_splitter = new RecursiveCharacterTextSplitter(["chunk_size" => 1000,"chunk_overlap" => 200]);
        $texts = $text_splitter->splitText($text);
        $chunkSize = 1000;
        $documentModelName = 'text-embedding-3-small';
        for ($i = 0; $i < count($texts); $i += $chunkSize) {
            $input = array_slice($texts, $i, $chunkSize);

            $maxRetries = 6;
            $backoff = new Backoff($maxRetries, 'exponential', 10000, true);
            $params = [
                    'input' => $input,
                    'model' => $documentModelName
                ];
            $result = $backoff->run(function () use ($params, $client) {
                return $client->embeddings()->create($params);
            });

            $response = $result->toArray();


            foreach ($response['data'] as $k => $data) {
                $embeddings[] = ["text" => $texts[$k],"embedding" => $data['embedding']];
            }
        }
        return $embeddings;
    }
    public static function getEmbeddingFormatted($text, $client, $split = '==========')
    {
        $embeddings = [];
        // Embedding text vector
        $texts = explode($split, $text);
        $documentModelName = 'text-embedding-3-small';
        foreach ($texts as $input) {
            $input = trim($input);
            if (empty($input)) {
                continue;
            }
            $maxRetries = 6;
            $backoff = new Backoff($maxRetries, 'exponential', 10000, true);
            $params = [
                    'input' => $input,
                    'model' => $documentModelName
                ];
            $result = $backoff->run(function () use ($params, $client) {
                return $client->embeddings()->create($params);
            });

            $response = $result->toArray();


            foreach ($response['data'] as $k => $data) {
                $embeddings[] = ["text" => $input,"embedding" => $data['embedding']];
            }
        }
        return $embeddings;
    }
    public static function validFields($required_fields, $formData)
    {
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($formData[$field])) {
                $missing_fields[] = $field;
            } else if ($field=='email' && !filter_var($formData[$field], FILTER_VALIDATE_EMAIL)) {
                $missing_fields[] = $field;
            } else if ($field=='phone' && !preg_match('/^\+?[0-9]{10}$/', $formData[$field])) {
                $missing_fields[] = $field;
            }
        }
        return $missing_fields;
    }

    public static function isValidEmails($emails)
    {
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false; // Invalid email found
            }
        }
        return true; // All emails are valid
    }

    public static function isValidDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public static function isValidDateTime($dateTime)
    {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
        return $d && $d->format('Y-m-d H:i:s') === $dateTime;
    }

    public static function isValidTime($time, $format = 'H:i')
    {
        $d = DateTime::createFromFormat($format, $time);
        return $d && $d->format($format) === $time;
    }

    public static function extractCoordinatesFromEntityText(string $text): array
    {
        $coordinates = [];

        // Regex tìm các cặp số float: X Y
        $pattern = '/(\d{6,}\.\d+)\s+(\d{6,}\.\d+)/';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $coordinates[] = [
                    'lat' => (float) $match[1],
                    'lng' => (float) $match[2],
                ];
            }
        }

        return $coordinates;
    }

    public static function extractCoordinates($text) {
        // Loại bỏ khoảng trắng đầu và cuối
        $text = trim($text);

        // Kiểm tra xem có 'X' ở đầu và 'Y' ở cuối không
        if (!(str_starts_with($text, 'X') && str_ends_with($text, 'Y'))) {
            return []; // Không đúng format thì trả về mảng rỗng
        }

        // Xóa ký tự X và Y ở hai đầu
        $text = substr($text, 1, -1);

        // Tìm tất cả các số thập phân (vẫn giữ số 0 ở cuối)
        preg_match_all('/\d+\.\d+/', $text, $matches);

        $coords = [];
        $numbers = $matches[0];

        // Gom thành cặp X,Y
        for ($i = 0; $i < count($numbers); $i += 2) {
            if (isset($numbers[$i + 1])) {
                $coords[] = [
                    'x' => (float) $numbers[$i],
                    'y' => (float) ($numbers[$i + 1])
                ];
            }
        }

        return $coords;
    }

    public static function getText($block, $blockMap) {
        $text = '';
        if (!empty($block['Relationships'])) {
            foreach ($block['Relationships'] as $rel) {
                if ($rel['Type'] === 'CHILD') {
                    foreach ($rel['Ids'] as $id) {
                        $word = $blockMap[$id] ?? null;
                        if ($word && $word['BlockType'] === 'WORD') {
                            $text .= $word['Text'] . ' ';
                        }
                    }
                }
            }
        }
        return trim($text);
    }

    public static function validatePassword($password) {
        // Kiểm tra độ dài tối thiểu
        if (strlen($password) < 6) {
            return false;
        }

        // Kiểm tra có ít nhất một chữ cái viết hoa
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Kiểm tra có ít nhất một chữ cái viết thường
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Kiểm tra có ít nhất một chữ số
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Kiểm tra có ít nhất một ký tự đặc biệt
        if (!preg_match('/[\W_]/', $password)) {
            return false;
        }

        return true;
    }

    public static function get_gravatar($email, $s = 80, $d = 'mp', $r = 'g', $img = false, $atts = array())
    {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";
        return $url;
    }

    /**
     * Resolve scope (self/own/all) for a module/action from a provided permission list.
     */
    public static function resolveScope(array $permissions, string $module, string $action): string
    {
        $hasWildcard = in_array("$module.*", $permissions, true);

        if ($action === 'create') {
            if ($hasWildcard || in_array("$module.$action.all", $permissions, true)) {
                return 'all';
            }
            if (in_array("$module.$action.own", $permissions, true)) {
                return 'own';
            }
            if (in_array("$module.$action.self", $permissions, true)) {
                return 'self';
            }
            if (in_array("$module.$action", $permissions, true)) {
                return 'own';
            }
            return '';
        }

        if ($hasWildcard || in_array("$module.$action.all", $permissions, true)) {
            return 'all';
        }
        if (in_array("$module.$action.own", $permissions, true)) {
            return 'own';
        }
        if (in_array("$module.$action.self", $permissions, true)) {
            return 'self';
        }

        return '';
    }

    /**
     * Allowed quality types used in production quality output flows.
     *
     * @return string[]
     */
    public static function getAllowedQualityTypes(): array
    {
        return ['L1', 'L2', 'L3', 'Mix', 'NA'];
    }

    // create a function create random string
    public static function generateRandomString($length = 24)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        //ABCDEFGHIJKLMNOPQRSTUVWXYZ
        $charactersLength = strlen($characters) - 1;
        $randomString = '';
        // $length = 24;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength)];
        }
        return $randomString;
    }

    public static function generateRandomNumber($length = 6)
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters) - 1;
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength)];
        }
        return $randomString;
    }
    
    public static function generateTokenAuth($data_user, $secret_jwt)
    {
        $token_data = [];
        $token_data['type'] = 'auth';
        $token_data['user_code'] = $data_user['user_code'];
        $token_data['company_code'] = $data_user['company_code'];
        $token_data['email'] = $data_user['email'];
        $token_data['phone'] = $data_user['phone'];
        $token_data['avatar'] = $data_user['avatar'];
        $token_data['full_name'] = $data_user['full_name'];
        $token_data['permissions'] = $data_user['permissions'] ?? [];
        $token_data['iat'] = time();
        $token_data['exp'] = time() + 3600 * 24 * 30 * 365;

        $access_token = JWT::encode($token_data, base64_decode($secret_jwt), 'RS256');
        return $access_token;
    }
    public static function generateExternalTokenAuth($token_data, $secret_jwt)
    {
        $token_data['type'] = 'app';
        $token_data['iat'] = time();
        $token_data['exp'] = time() + 60 * 15;

        $access_token = JWT::encode($token_data, $secret_jwt, 'HS256');
        return $access_token;
    }
    public static function generateTransactionCode($id)
    {
        $str = 'ZP';
        $str .= date('Ymd', time());
        $str .= str_pad((string)$id, 8, '0', STR_PAD_LEFT);
        return $str;
    }
    public static function paymentMessage($status, $displayErrorDetails = false)
    {
        $list = array(
            1 => "Thành công",
            -100 => "Sai mã xác thực hash".($displayErrorDetails ? ' (-100)' : ''),
            -1 => "Tạo giao dịch không thành công".($displayErrorDetails ? ' (-1)' : ''),
            -2 => "Sai loại hình thanh toán".($displayErrorDetails ? ' (-2)' : ''),
            -3 => "Lỗi không xác định".($displayErrorDetails ? ' (-3)' : ''),
            -5 => "Sai số điện thoại".($displayErrorDetails ? ' (-5)' : ''),
            -6 => "Sai giá trị thanh toán".($displayErrorDetails ? ' (-6)' : ''),
            -7 => "Sai mã hash đối tác".($displayErrorDetails ? ' (-7)' : ''),
            -9 => "Thanh toán đã vượt giới hạn".($displayErrorDetails ? ' (-9)' : ''),
            -10 => "Tạo giao dịch không thành công từ đối tác".($displayErrorDetails ? ' (-10)' : ''),
            -11 => "Mã thẻ sai".($displayErrorDetails ? ' (-11)' : ''),
            -12 => "Yêu cầu bị giới hạn".($displayErrorDetails ? ' (-12)' : ''),
            -20 => "Giao dịch đang thực hiện".($displayErrorDetails ? ' (-20)' : ''), // Payment sẽ ko gọi websocket
            -21 => "Chưa hỗ trợ ngân hàng ngày".($displayErrorDetails ? ' (-21)' : ''),
            -22 => "Tài khoản Zalopay không đủ số dư để thực hiện giao dịch".($displayErrorDetails ? ' (-22)' : ''),
            -23 => "Tài khoản chưa liên kết tự động".($displayErrorDetails ? ' (-23)' : ''),
            -24 => "Thanh toán không hợp lệ (ưu đãi hết hạn)".($displayErrorDetails ? ' (-24)' : ''),
            -25 => "Tài khoản Zalo Pay chưa xác thực",
            -221 => "Tài khoản chưa được xác thực".($displayErrorDetails ? ' (-221)' : ''),
            -999 => "Vui lòng làm mới trang web".($displayErrorDetails ? ' (-999)' : ''), // Quang
        );
        /* $list = array(
            1 => "Success",
            -100 => "Wrong hash",
            -1 => "Create transaction fail (DB)",
            -2 => "Wrong payment method",
            -3 => "Exception",
            -5 => "Wrong phone number",
            -6 => "Wrong amount",
            -7 => "Wrong hash partner",
            -9 => "Payment limit exceeded",
            -10 => "Create transaction fail do service partner",
            -11 => "Card isn't valid",
            -12 => "Request limit exceeded",
            -20 => "Transaction is processing",
            -21 => "Bank isn't support",
            -22 => "Missing require info",
            -23 => "Account wasn't binding",
            -24 => "Payment not valid (offer ZaloPay expired)",
            -221 => "Account wasn't verify yet",
        ); */
        if (!isset($list[$status])) {
            return "Lỗi không xác định".($displayErrorDetails ? ' ('.$status.')' : '');
        }
        return $list[$status];
    }
    public static function generateToken($fields, $secret)
    {
        ksort($fields);
        $newFields = array_values($fields);
        foreach ($newFields as &$v) {
            if (is_array($v)) {
                $v = json_encode($v);
            }
        }
        $token = md5(implode('', $newFields).$secret);
        return $token;
    }
    public static function camelCaseKeys($array, $arrayHolder = array())
    {
        $camelCaseArray = !empty($arrayHolder) ? $arrayHolder : array();
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if (is_numeric($key)) {
                    continue;
                }

                $newKey = @explode('_', $key);
                // array_walk($newKey, create_function('&$v', '$v = ucwords($v);'));
                //array_walk($newKey, function (&$v){ $v = ucwords($v); });
                array_walk($newKey, function (&$v) {
                    $v = ucwords($v);
                });
                $newKey = @implode('', $newKey);
                $newKey[0] = strtolower($newKey[0]);
                if (!is_array($val)) {
                    $camelCaseArray[$newKey] = $val;
                } else {
                    if (isset($array[$key])) {
                        $camelCaseArray[$newKey] = Utils::camelCaseKeys($val, $array[$key]);
                    }
                }
            }
        }
        return $camelCaseArray;
    }
    public static function underscoreKeys($array, $arrayHolder = array())
    {
        $underscoreArray = !empty($arrayHolder) ? $arrayHolder : array();
        foreach ($array as $key => $val) {
            $newKey = preg_replace('/[A-Z]/', '_$0', $key);
            $newKey = strtolower($newKey);
            $newKey = ltrim($newKey, '_');
            if (!is_array($val)) {
                $underscoreArray[$newKey] = $val;
            } else {
                if (isset($array[$key])) {
                    $underscoreArray[$newKey] = Utils::underscoreKeys($val, $array[$key]);
                }
            }
        }

        return $underscoreArray;
    }

    public static function base64_url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64_url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    public static function clean_string($str)
    {
        return trim(preg_replace('/\s\s+/', ' ', $str));
    }

    public static function calculateNetAmount($amount, $payment_data)
    {
        $net_amount = $amount;

        /* cost_transaction
        cost_percent
        vat */

        return $net_amount;
    }

    /*     public static function base64_url_encode($input)
        {
            $str = base64_encode($input);
            $str = str_replace('=', '', $str);
            return strtr($str, '+/', '-_');
        }
        public static function base64_url_decode($input)
        {
            return base64_decode(strtr($input, '-_', '+/'));
        } */

    public static function convert_file_name($file_name)
    {
        // get extension of $file_name
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        if (empty($ext)) {
            $ext = 'jpg';
        }
        $file_name = pathinfo($file_name, PATHINFO_FILENAME);
        $file_name = strtolower($file_name);
        $file_name = str_replace(" ", "-", $file_name);
        $file_name = str_replace("\'", "-", $file_name);
        $file_name = str_replace("\"", "-", $file_name);
        $allowed = "/[^a-zA-Z0-9\\-\\_]/i";
        $file_name = preg_replace($allowed, "", $file_name);
        $file_name = str_replace("---", "-", $file_name);
        $file_name = str_replace("--", "-", $file_name);
        return $file_name.'_'.rand(1000, 9999).'.'.$ext;
    }
    public static function convert_slug($str) {
        $str = self::remove_utf8($str);
        $str = str_replace(' ', '-', $str);
        $str = preg_replace('/[^A-Za-z0-9\-]/', '-', $str);
        $str = preg_replace('/-+/', '-', $str);
        $str = mb_strtolower($str, 'UTF-8');
        $str = trim($str, '-');
        return $str;
    }
    public static function remove_utf8($str)
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);

        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);


        $file_name = trim($str);
        return $file_name;
    }
    public static function folder_file($dir_home)
    {
        $folder = date("YW", time());
        /*
        if (!file_exists($dir_home.'/../../../../storage/'.$folder)) {
            mkdir($dir_home.'/../../../../storage/'.$folder);
            chmod($dir_home.'/../../../../storage/'.$folder, 0777);
        }
        
        if (!file_exists($dir_home.'/../../../../public/uploads/'.$folder)) {
            mkdir($dir_home.'/../../../../public/uploads/'.$folder);
            chmod($dir_home.'/../../../../public/uploads/'.$folder, 0777);
        }
        */
        return $folder;
    }
    public static function folder_file_data($dir_home)
    {
        $folder = date("YW", time());
        /* if (!file_exists($dir_home.'/data/'.$folder)) {
            mkdir($dir_home.'/data/'.$folder);
            chmod($dir_home.'/data/'.$folder, 0777);
        } */
        return $folder;
    }
    public static function save_log($logger, $log)
    {
        foreach ($log as $k => $v) {
            $log[$k] = str_replace(array("\n", "\r"), '', (string)$v);
        }
        $log_save = implode("|", array_values($log));
        $logger->notice($log_save);
    }
    public static function isValid($secret, $response)
    {
        try {
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = ['secret'   => $secret,
                        'response' => $response,
                        'remoteip' => $_SERVER['REMOTE_ADDR']];

            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data)
                ]
            ];

            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            return json_decode($result)->success;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function convert_tag($str)
    {
        $str = str_replace(' ', '_', $str);
        // replace all non letters or non digits by _
        $str = preg_replace('/[^\p{L}\d-]/u', '_', $str);
        // trim and lowercase utf8
        $str = trim(mb_strtolower($str, 'UTF-8'));

        return $str;
    }
    public static function write_log($params, $logger)
    {
        // Không được đảo thứ tự các key trong mảng $log, chỉ có nối thêm vào.
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "path" => htmlspecialchars((string)($params['path'] ?? '')),
            "user_code" => htmlspecialchars((string)($params['user_code'] ?? '')),
            "ad_account_id" => htmlspecialchars((string)($params['ad_account_id'] ?? '')),
            "log_action" => htmlspecialchars((string)($params['log_action'] ?? '')),
            "log_group" =>  "Info",
            "extra_1" => htmlspecialchars((string)($params['extra_1'] ?? '')),
            "extra_2" => htmlspecialchars((string)($params['extra_2'] ?? '')),
            "extra_3" => htmlspecialchars((string)($params['extra_3'] ?? '')),
            "extra_4" => htmlspecialchars((string)($params['extra_4'] ?? '')),
            "extra_5" => htmlspecialchars((string)($params['extra_5'] ?? '')),
        );

        foreach ($log as $k => $v) {
            if ($k == 'milliseconds') {
                continue;
            }
            $log[$k] = str_replace(array("\n", "\r"), '', $v);
        }

        $log['path'] = urldecode($log['path']);
        $log['path'] = str_replace('&amp;', '&', $log['path']);
        $log['path'] = strtok($log['path'], '#');

        $log_save = implode("|", array_values($log));
        $logger->notice($log_save);

    }

    public static function isValidTransaction(string $buyerAccountType, string $sellerAccountType): bool
    {
        // Định nghĩa quy tắc giao dịch
        $transactionRules = [
            'farmer' => [
                'can_buy_from' => [], // Nông hộ không tạo phiếu mua
                'can_sell_to' => ['purchaser', 'trader', 'company'] // Nông hộ bán cho Thu mua, Thương lái, Công ty
            ],
            'purchaser' => [
                'can_buy_from' => ['farmer', 'purchaser'], // Thu mua mua từ Nông hộ và Thu mua khác
                'can_sell_to' => ['purchaser', 'trader', 'company'] // Thu mua bán cho Thu mua khác, Thương lái, Công ty
            ],
            'trader' => [
                'can_buy_from' => ['farmer', 'purchaser', 'trader'], // Thương lái mua từ Nông hộ, Thu mua, Thương lái khác
                'can_sell_to' => ['trader', 'company'] // Thương lái bán cho Thương lái khác, Công ty
            ],
            'company' => [
                'can_buy_from' => ['farmer', 'purchaser', 'trader', 'company'], // Công ty mua từ tất cả
                'can_sell_to' => ['company'] // Công ty chỉ bán cho Công ty khác
            ]
        ];

        // Kiểm tra loại tài khoản có hợp lệ không
        $validAccountTypes = ['farmer', 'purchaser', 'trader', 'company'];
        if (!in_array($buyerAccountType, $validAccountTypes) || !in_array($sellerAccountType, $validAccountTypes)) {
            return false;
        }

        // Kiểm tra quy tắc giao dịch
        if (!isset($transactionRules[$buyerAccountType]['can_buy_from'])) {
            return false;
        }

        return in_array($sellerAccountType, $transactionRules[$buyerAccountType]['can_buy_from']);
    }

    /**
     * Ánh xạ account_type (dùng trong giao dịch) sang tên role trong hệ thống.
     * Một account_type có thể ứng với nhiều role.
     *
     * @param string $accountType
     * @return string[]
     */
    public static function mapAccountTypeToRoles(string $accountType): array
    {
        $mapping = [
            // Account types → role names trong eudr_roles
            'farmer'     => ['farmer'],
            'purchaser'  => ['purchaser'],
            //'trader'     => ['trader'],
            //'company'    => ['factory', 'sales'],
            'inspector'  => ['inspector'],
            // Direct role names → chính nó
            //'purchasing' => ['purchasing'],
            'transport'  => ['transport'],
            'factory'    => ['factory'],
            'sales'      => ['sales'],
            'admin'      => ['admin'],
        ];

        return $mapping[$accountType] ?? [];
    }

    /**
     * Ánh xạ role name sang account_type tương ứng.
     *
     * @param string $roleName
     * @return string
     */
    public static function mapRoleToAccountType(string $roleName): string
    {
        $mapping = [
            'farmer'     => 'farmer',
            'purchaser' => 'purchaser',
            'transport'  => 'transport',
            'factory'    => 'factory',
            'sales'      => 'sales',
            'inspector'  => 'inspector',
        ];

        return $mapping[$roleName] ?? '';
    }

    /**
     * Chuẩn hoá giá trị đầu vào thành danh sách role names hợp lệ.
     * Chấp nhận cả account_type (purchaser, trader, company) lẫn role name (purchaser, transport, factory).
     *
     * @param string|array $input  Giá trị đơn hoặc mảng
     * @return string[]  Mảng role names đã chuẩn hoá, loại trùng
     */
    public static function normalizeToRoleNames(string|array $input): array
    {
        if (!is_array($input)) {
            $input = [$input];
        }

        $validRoleNames = ['farmer', 'purchaser', 'transport', 'factory', 'sales', 'inspector', 'admin'];
        $normalizedRoles = [];

        foreach ($input as $value) {
            $value = trim((string)$value);
            if (in_array($value, $validRoleNames, true)) {
                // Đã là role name hợp lệ
                $normalizedRoles[] = $value;
            } else {
                // Thử ánh xạ từ account_type sang role name
                $mapped = self::mapAccountTypeToRoles($value);
                if (!empty($mapped)) {
                    $normalizedRoles = array_merge($normalizedRoles, $mapped);
                }
            }
        }

        return array_values(array_unique($normalizedRoles));
    }

    /**
     * Tạo điều kiện SQL subquery để lọc user theo role.
     * Trả về chuỗi SQL dùng cho WHERE clause.
     *
     * @param string $userIdColumn  Tên cột user_id (vd: 'user.user_id', 'u.user_id')
     * @param string $accountType   Account type cần lọc (farmer, purchaser, trader, company, inspector)
     * @return string  SQL condition hoặc chuỗi rỗng nếu không hợp lệ
     */
    public static function buildRoleFilterSQL(string $userIdColumn, string $accountType): string
    {
        $roleNames = self::mapAccountTypeToRoles($accountType);
        if (empty($roleNames)) {
            return '';
        }

        $roleNamesStr = "'" . implode("','", array_map(fn($r) => addslashes($r), $roleNames)) . "'";

        return "{$userIdColumn} IN (SELECT ur.user_id FROM eudr_user_roles ur JOIN eudr_roles r ON r.role_id = ur.role_id WHERE r.name IN ({$roleNamesStr}))";
    }

    /**
     * Kiểm tra xem user có role phù hợp với account_type hay không.
     *
     * @param array $userRoles  Mảng role objects (mỗi phần tử có key 'name')
     * @param string $accountType  Account type cần kiểm tra (farmer, purchaser, trader, company)
     * @return bool
     */
    public static function userHasAccountTypeRole(array $userRoles, string $accountType): bool
    {
        $requiredRoles = self::mapAccountTypeToRoles($accountType);
        if (empty($requiredRoles)) {
            return false;
        }

        $userRoleNames = array_map(fn($r) => is_array($r) ? ($r['name'] ?? '') : (string)$r, $userRoles);

        return !empty(array_intersect($requiredRoles, $userRoleNames));
    }

    /**
     * Kiểm tra xem giao dịch tự mua bán (self-transaction) có hợp lệ không.
     * User phải có cả role buyer và role seller.
     *
     * @param array $userRoles  Mảng role objects của user
     * @param string $buyerAccountType
     * @param string $sellerAccountType
     * @return bool
     */
    public static function isValidSelfTransaction(array $userRoles, string $buyerAccountType, string $sellerAccountType): bool
    {
        // Kiểm tra loại giao dịch có hợp lệ không
        if (!self::isValidTransaction($buyerAccountType, $sellerAccountType)) {
            return false;
        }

        // Kiểm tra user có role tương ứng với cả 2 phía
        $hasBuyerRole = self::userHasAccountTypeRole($userRoles, $buyerAccountType);
        $hasSellerRole = self::userHasAccountTypeRole($userRoles, $sellerAccountType);

        return $hasBuyerRole && $hasSellerRole;
    }
}
