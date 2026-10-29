<?php

namespace common\helpers;

/**
 * Turns free-form change-request text into structured blocks for HR review UI.
 */
class ChangeRequestContentFormatter
{
    /**
     * @return array{
     *   blocks: list<array{type:string,label?:string,value?:string,text?:string}>,
     *   fields: array<string, string>,
     *   plain: string
     * }
     */
    public static function parse(string $text): array
    {
        $text = self::normalizeRaw($text);
        if ($text === '') {
            return ['blocks' => [], 'fields' => [], 'plain' => ''];
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $lines = array_values(array_filter(array_map(static fn($line) => trim((string)$line), $lines), static fn($line) => $line !== ''));

        $blocks = [];
        $fields = [];
        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = $lines[$i];

            if (preg_match('/^change\s+(?:my\s+)?(.+?)\s+to$/i', $line, $changeMatch)) {
                $subject = trim((string)$changeMatch[1]);
                $next = $i + 1 < $count ? $lines[$i + 1] : '';
                if ($next !== '' && !str_contains($next, ':')) {
                $key = self::fieldKeyFromLabel($subject, 'proposed');
                $blocks[] = ['type' => 'intent', 'label' => self::formatLabel($subject), 'value' => $next];
                $fields[$key] = $next;
                    $i += 2;
                    continue;
                }
                $blocks[] = ['type' => 'note', 'text' => $line];
                $i++;
                continue;
            }

            if (preg_match('/^([^:]{2,80}):\s*(.+)$/u', $line, $fieldMatch)) {
                $label = trim((string)$fieldMatch[1]);
                $value = trim((string)$fieldMatch[2]);
                $key = self::fieldKeyFromLabel($label);
                $blocks[] = ['type' => 'field', 'label' => self::formatLabel($label), 'value' => $value];
                $fields[$key] = $value;
                $i++;
                continue;
            }

            $blocks[] = ['type' => 'text', 'text' => $line];
            $i++;
        }

        return [
            'blocks' => $blocks,
            'fields' => $fields,
            'plain' => $text,
        ];
    }

    /**
     * @param array<string, string> $oldFields
     * @param array<string, string> $newFields
     * @return list<array{key:string,label:string,current:string,proposed:string,changed:bool}>
     */
    public static function buildComparisonRows(array $oldFields, array $newFields): array
    {
        $grouped = [];

        foreach ($oldFields as $key => $value) {
            $base = self::baseFieldName((string)$key);
            $grouped[$base][self::slotForKey((string)$key, 'old')] = trim((string)$value);
        }
        foreach ($newFields as $key => $value) {
            $base = self::baseFieldName((string)$key);
            $grouped[$base][self::slotForKey((string)$key, 'new')] = trim((string)$value);
        }

        ksort($grouped);
        $rows = [];
        foreach ($grouped as $base => $pair) {
            $current = trim((string)($pair['current'] ?? ''));
            $proposed = trim((string)($pair['proposed'] ?? ''));
            if ($current === '' && $proposed === '') {
                continue;
            }
            $rows[] = [
                'key' => $base,
                'label' => self::formatLabel($base),
                'current' => $current !== '' ? $current : '—',
                'proposed' => $proposed !== '' ? $proposed : '—',
                'changed' => strcasecmp($current, $proposed) !== 0,
            ];
        }

        return $rows;
    }

    public static function normalizeRaw(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    public static function fieldKeyFromLabel(string $label, ?string $forcePrefix = null): string
    {
        $lower = strtolower(trim($label));
        $prefix = $forcePrefix;

        if ($prefix === null) {
            if (preg_match('/^current\s+(.+)$/u', $lower, $match)) {
                $prefix = 'current';
                $lower = trim((string)$match[1]);
            } elseif (preg_match('/^(new|proposed|updated)\s+(.+)$/u', $lower, $match)) {
                $prefix = 'proposed';
                $lower = trim((string)$match[2]);
            }
        } else {
            $lower = preg_replace('/^(current|new|old|proposed|updated)\s+/u', '', $lower) ?? $lower;
        }

        $base = self::normalizeFieldKey($lower);

        return $prefix !== null ? $prefix . '_' . $base : 'field_' . $base;
    }

    public static function normalizeFieldKey(string $label): string
    {
        $label = strtolower(trim($label));
        $label = preg_replace('/[^a-z0-9]+/u', '_', $label) ?? $label;

        return trim($label, '_');
    }

    public static function baseFieldName(string $key): string
    {
        return preg_replace('/^(current|proposed|new|old|field)_/u', '', strtolower(trim($key))) ?? $key;
    }

    private static function slotForKey(string $key, string $source): string
    {
        if (str_starts_with($key, 'current_')) {
            return 'current';
        }
        if (str_starts_with($key, 'proposed_')) {
            return 'proposed';
        }

        return $source === 'old' ? 'current' : 'proposed';
    }

    public static function formatLabel(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return 'Detail';
        }

        $label = preg_replace('/^(current|new|old|proposed)\s+/iu', '', $label) ?? $label;

        return ucwords(str_replace(['_', '-'], ' ', $label));
    }

    public static function hasStructuredContent(array $parsed): bool
    {
        if ($parsed['fields'] !== []) {
            return true;
        }

        foreach ($parsed['blocks'] as $block) {
            if (($block['type'] ?? '') !== 'text') {
                return true;
            }
        }

        return false;
    }
}
