<?php

declare(strict_types=1);

namespace App\Application\Actions\General;

use Psr\Http\Message\ResponseInterface as Response;

class ClearCacheMemcacheAction extends GeneralAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $prefix = htmlspecialchars($_GET['prefix']??'');
        $keys = $this->memcached->getAllKeys();

        $prefixLength = strlen($prefix);
        $total = 0;

        /**
         * Clears the Memcache cache by deleting all keys with a given prefix or flushing the entire cache if no prefix is provided.
         *
         * @param string|null $prefix The prefix to search for in the cache keys.
         * @param array $keys The array of cache keys to search through.
         * @return void
         */
        if (!empty($prefix)) {
            foreach ($keys as $key) {
                if (substr_compare($key, $prefix, 0, $prefixLength) === 0) {
                    $this->memcached->delete(substr($key, 0, -1));
                    $total++;
                }
            }
        } else {
            $this->memcached->flush(1);
        }

        $data_return = array('clearItems' => $total);

        return $this->respondWithData($data_return);
    }
}
