<?php

namespace common\components;

use Yii;
use yii\base\Component;
use yii\web\TooManyRequestsHttpException;

class RateLimiter extends Component
{
    public $defaultLimit = 20;
    public $defaultWindow = 60;

    public function allow($key, $limit = null, $window = null, $identity = null)
    {
        $limit = $limit ?: $this->defaultLimit;
        $window = $window ?: $this->defaultWindow;
        $cacheKey = $this->cacheKey($key, $identity);
        $now = time();
        $cutoff = $now - $window;

        $timestamps = Yii::$app->cache->get($cacheKey);
        if (!is_array($timestamps)) {
            $timestamps = [];
        }

        $timestamps = array_values(array_filter($timestamps, static fn($timestamp) => (int)$timestamp > $cutoff));
        if (count($timestamps) >= $limit) {
            Yii::warning([
                'key' => $key,
                'identity' => $this->identity($identity),
                'limit' => $limit,
                'window' => $window,
            ], 'security.rateLimit');
            return false;
        }

        $timestamps[] = $now;
        Yii::$app->cache->set($cacheKey, array_slice($timestamps, -$limit), $window);

        return true;
    }

    public function check($key, $limit = null, $window = null, $identity = null)
    {
        if (!$this->allow($key, $limit, $window, $identity)) {
            throw new TooManyRequestsHttpException('Too many requests. Please try again later.');
        }
    }

    private function cacheKey($key, $identity = null)
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$key);
        return ['rateLimit', $safeKey, md5($this->identity($identity))];
    }

    private function identity($identity = null)
    {
        if ($identity !== null) {
            return (string)$identity;
        }

        return Yii::$app->request->userIP ?: '0.0.0.0';
    }
}
