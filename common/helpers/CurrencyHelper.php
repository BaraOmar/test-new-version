<?php

namespace common\helpers;

use Yii;

class CurrencyHelper
{
    public static function code(): string
    {
        return (string)(Yii::$app->params['currencyCode'] ?? 'JD');
    }

    public static function symbol(): string
    {
        return (string)(Yii::$app->params['currencySymbol'] ?? self::code());
    }

    public static function amount(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals);
    }

    public static function format(float $value, int $decimals = 2, bool $appendSymbol = true): string
    {
        $formatted = self::amount($value, $decimals);

        return $appendSymbol ? $formatted . ' ' . self::symbol() : $formatted;
    }

    public static function labelWithSymbol(string $label): string
    {
        return $label . ' (' . self::symbol() . ')';
    }
}
