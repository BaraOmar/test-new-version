<?php

/** @var array<string, mixed> $context */
/** @var string $body */

echo "Clinic Visit Update\n\n";
echo 'Student: ' . ($context['student_name'] ?? '') . "\n";
echo 'Visit date: ' . ($context['visit_date'] ?? '') . "\n";
echo 'Action taken: ' . ($context['action_label'] ?? '') . "\n\n";
echo $body . "\n";
