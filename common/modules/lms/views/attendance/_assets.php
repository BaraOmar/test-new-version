<?php

/**
 * @var yii\web\View $this
 */

$this->registerCssFile('@web/css/lms-attendance.css');
$this->registerJs(<<<JS
window.lmsAttendanceNotify = function(message, type) {
  type = type || 'warning';
  var existing = document.querySelector('.lms-attendance-toast');
  if (existing) existing.remove();
  var toast = document.createElement('div');
  toast.className = 'lms-attendance-toast lms-attendance-toast--' + type;
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(function() {
    if (toast.parentNode) toast.parentNode.removeChild(toast);
  }, 3500);
};
JS
, \yii\web\View::POS_HEAD);
