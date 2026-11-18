<?php

namespace App\Support;

class PmHtml
{
    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/<\/?(?:script|style|iframe|object|embed|form)[^>]*>/is', '', $html) ?? '';
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s+(?:style|data-[a-z0-9_-]+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace_callback('/\s(href|src)\s*=\s*("[^"]*"|\'[^\']*\')/i', function (array $match): string {
            $quote = $match[2][0];
            $value = trim($match[2], "\"'");
            if (preg_match('/^(?:javascript|vbscript|data):/i', $value)) {
                $value = '#';
            }

            return ' '.$match[1].'='.$quote.e($value).$quote;
        }, $html) ?? '';

        return trim(strip_tags($html, '<p><br><b><strong><i><em><u><s><strike><ol><ul><li><blockquote><pre><code><h1><h2><h3><h4><h5><h6><a><img><span><div><table><thead><tbody><tfoot><tr><th><td><figure><figcaption>'));
    }

    public static function text(?string $html): string
    {
        return trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
