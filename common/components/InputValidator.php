<?php

namespace common\components;

use yii\base\Component;
use yii\helpers\Html;

class InputValidator extends Component
{
    public function encode($value)
    {
        return Html::encode((string)$value);
    }

    public function whitelist($value, array $allowed, $default = '')
    {
        $value = trim((string)$value);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    public function string($value, $maxLength = 10000, $strict = false)
    {
        $value = trim((string)$value);
        if ($maxLength > 0 && mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }

        if ($strict && $value !== '' && !preg_match('/^[\p{L}\p{N}\p{P}\p{S}\s\-_.,;:!?\'"()\n\r\t]+$/u', $value)) {
            return '';
        }

        return $value;
    }

    public function email($value)
    {
        $value = trim((string)$value);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
    }

    public function id($value, $max = 2147483647)
    {
        $value = (int)$value;
        return $value > 0 && $value <= $max ? $value : 0;
    }

    public function date($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }

    public function search($value, $maxLength = 200)
    {
        $value = trim((string)$value);
        if (mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }

        return preg_replace('/[^\p{L}\p{N}\s\-_.,@]/u', '', $value);
    }
}
