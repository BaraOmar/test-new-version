<?php

/**
 * @var yii\web\View $this
 * @var string $endDateId
 */

use yii\web\View;

$endDateId = $endDateId ?? 'endDate';

$this->registerJs(
    'function updateEndDateMin() {'
    . ' var s = document.querySelector(\'[name="Survey[start_date]"]\');'
    . ' var e = document.getElementById(' . json_encode($endDateId) . ');'
    . ' if (s?.value && e) e.min = s.value;'
    . '}'
    . 'function setDuration(days) {'
    . ' var s = document.querySelector(\'[name="Survey[start_date]"]\');'
    . ' var e = document.getElementById(' . json_encode($endDateId) . ');'
    . ' let d = s?.value ? new Date(s.value) : new Date();'
    . ' if (s && !s.value) s.value = d.toISOString().split("T")[0];'
    . ' d = new Date(d); d.setDate(d.getDate() + days);'
    . ' if (e) e.value = d.toISOString().split("T")[0];'
    . ' updateEndDateMin();'
    . '}'
    . 'function clearDuration() {'
    . ' var e = document.getElementById(' . json_encode($endDateId) . ');'
    . ' if (e) e.value = "";'
    . ' var t = document.querySelector(\'[name="Survey[end_time]"]\');'
    . ' if (t) t.value = "";'
    . '}'
    . 'updateEndDateMin();',
    View::POS_END
);
