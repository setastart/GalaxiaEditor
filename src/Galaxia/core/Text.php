<?php
/* Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

namespace Galaxia;


use DateTimeInterface;
use DOMDocument;
use IntlDateFormatter;
use Normalizer;
use Transliterator;


class Text {

    public const ALLOWED_TAGS           = '<a><h1><h2><strong><small><p><br><em><del><blockquote><pre><ul><ol><li>';
    public const HTMLSPECIALCHARS_FLAGS = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5;

    public const TEST_HTML = <<<HTML
<h1>Example <strong>Test Heading 1</strong> that <del>is</del> 49 <em>characters</em> long</h1><p>First paragraph, a bit <strong>long so it wraps around</strong>, to see how it <em>looks if it wraps</em>. This first paragraph <del>doesn't have</del> any newlines and is 155 characters long.</p><p>Second short paragraph with newine here.<br>This line is a soft break from the previous.<br>Another line with a <a href="https://setastart.com">hyperlink</a>.</p><h2>Example <strong>Test Heading 2</strong> that <del>is</del> 49 <em>characters</em> long</h2><p>Third short paragraph.</p><ul><li>Unordered list item one.<br>Soft break into a long line to see how it wraps. Lorem ipsum dolor sit amet everything bla bla bla andalongfakewordwhynot.</li><li>Unordered list item two.<ul><li>Subitem one.<ul><li>Subsubitem one. I'm making this one longer so it wraps to see where it ends up. This item is 113 characters long.</li><li>Subsubitem two.</li></ul></li></ul></li></ul><ol><li>Unordered list item one.<br>Soft break into a long line to see how it wraps. Lorem ipsum dolor sit amet everything bla bla bla andalongfakewordwhynot.</li><li>Unordered list item two.<ol><li>Subitem one.<ol><li>Subsubitem one. I'm making this one longer so it wraps to see where it ends up. This item is 113 characters long.</li><li>Subsubitem two.</li></ol></li></ol></li></ol><pre>This is a code fragment or something. First line, a bit long so it wraps around, to see how it looks if it wraps. This first line ends in a newline after the dot.
Second Line.</pre><blockquote>This is a quotation or something. First line, a bit long so it wraps around, to see how it looks if it wraps. This first line ends in a newline after the dot.<br>Second Line.</blockquote>
HTML;

    public static array $translation      = [];
    public static array $translationAlias = [];

    private static ?Transliterator $transliterator      = null;
    private static ?Transliterator $transliteratorLower = null;
    private static array           $intlDateFormatters  = [];


    static function unsafe(string $text, bool $condition = true) {
        if ($condition) return $text;

        return null;
    }

    static function unsafeg(array $arr, string $key = null, string $lang = '') {
        if (is_null($key)) {
            $key = 'temp';
            $arr = [$key => $arr];
        }

        if (empty($arr)) return null;
        if (!isset($arr[$key])) return null;
        $text = null;

        switch (gettype($arr[$key])) {
            case 'integer':
            case 'double':
                $text = (string)$arr[$key];
                break;

            case 'string':
                $text = $arr[$key];
                break;

            case 'array':
                $langs = G::langs();
                if ($lang && in_array($lang, $langs)) {
                    $langKey = array_search($lang, $langs);
                    if ($langKey > 0) {
                        unset($langs[$langKey]);
                        array_unshift($langs, $lang);
                    }
                }
                foreach ($langs as $lang) {
                    if (!isset($arr[$key][$lang])) continue;
                    if (!is_string($arr[$key][$lang])) continue;
                    if (empty($arr[$key][$lang])) continue;
                    $text = (string)$arr[$key][$lang];
                    break;
                }
                break;
        }

        if ($text !== '0' && empty($text)) return null;

        return $text;
    }




    static function h($text, bool $condition = true) {
        if ($condition) return htmlspecialchars((string)$text, self::HTMLSPECIALCHARS_FLAGS);

        return null;
    }

    static function hg(array $arr, string $key = null, string $lang = '') {
        if (is_null($key)) {
            $key = 'temp';
            $arr = [$key => $arr];
        }
        if (empty($arr)) return null;
        if (!isset($arr[$key])) return null;
        $text = null;

        switch (gettype($arr[$key])) {
            case 'integer':
            case 'double':
                $text = (string)$arr[$key];
                break;

            case 'string':
                $text = htmlspecialchars($arr[$key], self::HTMLSPECIALCHARS_FLAGS) ?: null;
                break;

            case 'array':
                $langs = G::langs();
                if ($lang && in_array($lang, $langs)) {
                    $langKey = array_search($lang, $langs);
                    if ($langKey > 0) {
                        unset($langs[$langKey]);
                        array_unshift($langs, $lang);
                    }
                }
                foreach ($langs as $lang) {
                    if (!isset($arr[$key][$lang])) continue;
                    if (!is_string($arr[$key][$lang])) continue;
                    if (empty($arr[$key][$lang])) continue;
                    $text = htmlspecialchars($arr[$key][$lang], self::HTMLSPECIALCHARS_FLAGS);
                    break;
                }
                break;
        }

        if ($text !== '0' && empty($text)) return null;

        return $text;
    }




    static function st(string $text, int $h1 = 0, int $f1 = 0, int $f2 = 0) {
        // if (G::$dev) $text = TEST_HTML;
        if (empty($text)) return '';
        $text = trim(strip_tags($text, self::ALLOWED_TAGS));
        if (empty($text)) return '';
        if ($h1 == 0) return $text;

        // add target="_blank" rel="noopener" to outgoing links
        $host = explode('.', G::$req->host);
        $host = implode('\.', $host);
        $re   = '~<a href="https?(?!://' . $host . '/)://([^:/\s">]+)~m';

        $text = preg_replace_callback($re, function($matches) {
            $inject = 'target="_blank" rel="noopener';
            if (self::nofollowHost($matches[1])) $inject .= ' nofollow';

            return '<a ' . $inject . '"' . substr($matches[0], 2);
        }, $text);

        // Header modification for <h1> and <h2>
        // $h1 changes the start numbering of the <h1>. If set to 0, disable header modification.
        // <h2> is $h1 + 1.
        // $f1 ads class="$f1" to <h1>. If set to 0, it is $h1
        // $f2 ads class="$f2" to <h2>. If set to 0, it is $h1 + 1
        if ($f1 == 0) $f1 = $h1;
        if ($f2 == 0) $f2 = $f1 + 1;
        if ($f1 == 7) $f1 = 'p';
        if ($f2 == 7) $f2 = 'p';
        if ($f1 == 8) $f1 = 's';
        if ($f2 == 8) $f2 = 's';
        $text = str_replace('<h2>', '<h' . ($h1 + 1) . ' class="t t2 f' . $f2 . '">', $text);
        $text = str_replace('</h2>', '</h' . ($h1 + 1) . '>', $text);

        $text = str_replace('<h1>', '<h' . $h1 . ' class="t t1 f' . $f1 . '">', $text);
        $text = str_replace('</h1>', '</h' . $h1 . '>', $text);

        return $text;
    }

    static function stg(array $arr, string $key = null, int $h1 = 0, int $f1 = 0, int $f2 = 0, string $lang = '') {
        if (is_null($key)) {
            $key = 'temp';
            $arr = [$key => $arr];
        }

        if (!isset($arr[$key])) return null;
        $text = '';

        switch (gettype($arr[$key])) {
            case 'integer':
            case 'double':
                $text = (string)$arr[$key];
                break;

            case 'string':
                $text = self::st($arr[$key], $h1, $f1, $f2);
                break;

            case 'array':
                $langs = G::langs();
                if ($lang && in_array($lang, $langs)) {
                    $langKey = array_search($lang, $langs);
                    if ($langKey > 0) {
                        unset($langs[$langKey]);
                        array_unshift($langs, $lang);
                    }
                }
                foreach ($langs as $lang) {
                    if (!isset($arr[$key][$lang])) continue;
                    if (!is_string($arr[$key][$lang])) continue;
                    if (empty($arr[$key][$lang])) continue;
                    $text = self::st($arr[$key][$lang], $h1, $f1, $f2);
                    break;
                }
                break;
        }

        if ($text !== '0' && empty($text)) return null;

        return $text;
    }

    static function trix(string $text, array $transforms = []): ?string {
        // if (G::$dev) $text = TEST_HTML;
        $text = trim(strip_tags($text, self::ALLOWED_TAGS));
        if (empty($text)) return null;
        if (empty($transforms)) return $text;

        // add target="_blank" rel="noopener" to outgoing links
        $host = explode('.', G::$req->host);
        $host = implode('\.', $host);
        $re   = '~<a href="https?(?!://' . $host . '/)://([^:/\s">]+)~m';

        $text = preg_replace_callback($re, function($matches) {
            $inject = 'target="_blank" rel="noopener';
            if (self::nofollowHost($matches[1])) $inject .= ' nofollow';

            return '<a ' . $inject . '"' . substr($matches[0], 2);
        }, $text);

        foreach ($transforms as $tagOld => $transform) {
            $tagNew = 'galaxiaTemp' . $transform[0];
            $class  = '';
            if (isset($transform[1])) $class = ' class="' . $transform[1] . '"';
            $id = '';
            if (isset($transform[2])) $id = ' class="' . $transform[2] . '"';
            $text = str_replace("<$tagOld>", "<$tagNew$class$id>", $text);
            $text = str_replace("</$tagOld>", "</$tagNew>", $text);
        }

        foreach ($transforms as $transform) {
            $tagOld = 'galaxiaTemp' . $transform[0];
            $tagNew = $transform[0];
            $text   = str_replace($tagOld, $tagNew, $text);
        }

        return $text;
    }

    static function trixg(array $arr, string $key = null, array $transforms = [], string $lang = '') {
        if (is_null($key)) {
            $key = 'temp';
            $arr = [$key => $arr];
        }

        if (!isset($arr[$key])) return null;
        $text = '';

        switch (gettype($arr[$key])) {
            case 'integer':
            case 'double':
                $text = (string)$arr[$key];
                break;

            case 'string':
                $text = self::trix($arr[$key], $transforms);
                break;

            case 'array':
                $langs = G::langs();
                if ($lang && in_array($lang, $langs)) {
                    $langKey = array_search($lang, $langs);
                    if ($langKey > 0) {
                        unset($langs[$langKey]);
                        array_unshift($langs, $lang);
                    }
                }
                foreach ($langs as $lang) {
                    if (!isset($arr[$key][$lang])) continue;
                    if (!is_string($arr[$key][$lang])) continue;
                    if (empty($arr[$key][$lang])) continue;
                    $text = self::trix($arr[$key][$lang], $transforms);
                    break;
                }
                break;
        }

        if ($text !== '0' && empty($text)) return null;

        return $text;
    }

    static function stp(string $text, int $f1 = 0, int $f2 = 0, int $fp = 0) {
        // if (G::$dev) $text = TEST_HTML;
        if (empty($text)) return '';
        $text = trim(strip_tags($text, self::ALLOWED_TAGS));
        if (empty($text)) return '';

        // add target="_blank" rel="noopener" to outgoing links
        $host = explode('.', G::$req->host);
        $host = implode('\.', $host);
        $re   = '~<a href="https?(?!://' . $host . '/)://([^:/\s">]+)~m';

        $text = preg_replace_callback($re, function($matches) {
            $inject = 'target="_blank" rel="noopener';
            if (self::nofollowHost($matches[1])) $inject .= ' nofollow';

            return '<a ' . $inject . '"' . substr($matches[0], 2);
        }, $text);

        // Header modification for <h1> and <h2>
        // $h1 changes the start numbering of the <h1>. If set to 0, disable header modification.
        // <h2> is $h1 + 1.
        // $f1 ads class="$f1" to <h1>. If set to 0, it is $h1
        // $f2 ads class="$f2" to <h2>. If set to 0, it is $h1 + 1
        $class1 = '';
        if ($f1 > 0 && $f1 <= 6) {
            $class1 = ' class="t t1 f' . $f1 . '"';
        } else if ($f1 == 8) {
            $class1 = ' class="fs"';
        }
        $class2 = '';
        if ($f2 > 0 && $f2 <= 6) {
            $class2 = ' class="t t2 f' . $f2 . '"';
        } else if ($f2 == 8) {
            $class2 = ' class="fs"';
        }
        if ($fp > 0 && $fp <= 6) {
            $text = str_replace('<p>', '<p class="f' . $fp . '">', $text);
        } else if ($fp == 8) {
            $text = str_replace('<p>', '<p class="fs">', $text);
        }

        $text = str_replace('<h2>', '<p' . $class2 . '>', $text);
        $text = str_replace('</h2>', '</p>', $text);

        $text = str_replace('<h1>', '<p' . $class1 . '>', $text);
        $text = str_replace('</h1>', '</p>', $text);

        return $text;
    }

    static function stpg(array $arr, string $key = null, int $f1 = 0, int $f2 = 0, int $fp = 0, string $lang = '') {
        if (is_null($key)) {
            $key = 'temp';
            $arr = [$key => $arr];
        }

        if (!isset($arr[$key])) return null;
        $text = '';

        switch (gettype($arr[$key])) {
            case 'integer':
            case 'double':
                $text = (string)$arr[$key];
                break;

            case 'string':
                $text = self::stp($arr[$key], $f1, $f2, $fp);
                break;

            case 'array':
                $langs = G::langs();
                if ($lang && in_array($lang, $langs)) {
                    $langKey = array_search($lang, $langs);
                    if ($langKey > 0) {
                        unset($langs[$langKey]);
                        array_unshift($langs, $lang);
                    }
                }
                foreach ($langs as $lang) {
                    if (!isset($arr[$key][$lang])) continue;
                    if (!is_string($arr[$key][$lang])) continue;
                    if (empty($arr[$key][$lang])) continue;
                    $text = self::stp($arr[$key][$lang], $f1, $f2, $fp);
                    break;
                }
                break;
        }

        if ($text !== '0' && empty($text)) return null;

        return $text;
    }




    static function desc(string $html, int $length = null, string $separator = ' / ') {
        if (empty($html)) return '';
        if (is_null($length)) $length = 255;

        $html = preg_replace('~<br ?/?>~m', PHP_EOL, $html);
        $html = str_replace('&nbsp;', '', $html);

        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $pTags = $dom->getElementsByTagName('p');

        $text  = '';
        $chars = 0;
        $i     = 0;
        foreach ($pTags as $pTag) {
            if ($i > 0) {
                switch (substr($text, -1)) {
                    case '.':
                    case ':':
                        $text .= ' ';
                        break;
                    default:
                        $text .= PHP_EOL;
                        break;
                }
            }
            $line = $pTag->nodeValue;
            $line = trim($line, " \t\n\r\0\x0B\xC2\xA0");

            $line = preg_replace('~(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))~m', '', $line); // remove urls

            $text  .= $line;
            $chars += mb_strlen($line);
            if ($length > 0 && $chars > $length) {
                break;
            }
            $i++;
        }
        if (!$text) {
            $text = $html;
            $text = trim($text, " \t\n\r\0\x0B\xC2\xA0");
            $text = strip_tags($text);
            $text = preg_replace('~\s+~u', ' ', $text ?? '');
        }
        $text = preg_replace('~\s*\.\s*\n+~m', '. ', $text ?? '');
        $text = preg_replace('~\s*:\s*\n+~m', ': ', $text ?? '');
        $text = preg_replace('~\s*\n+\s*~m', $separator, $text ?? '');
        $text = preg_replace('~\s+~u', ' ', $text ?? '');
        $text = preg_replace('~\s?,\s?~', ', ', $text ?? '');

        if ($length > 0 && mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length) . '[…]';
        }

        return htmlspecialchars($text, self::HTMLSPECIALCHARS_FLAGS);
    }

    static function descg(array $arr, string $key = null, int $length = null, string $separator = ' / ', string $lang = '') {
        if (is_null($key)) {
            $key = 'temp';
            $arr = [$key => $arr];
        }

        if (!isset($arr[$key])) return null;
        if (is_null($length)) $length = 255;
        $text = '';

        switch (gettype($arr[$key])) {
            case 'integer':
            case 'double':
                $text = (string)$arr[$key];
                break;

            case 'string':
                $text = self::desc($arr[$key], $length, $separator);
                break;

            case 'array':
                $langs = G::langs();
                if ($lang && in_array($lang, $langs)) {
                    $langKey = array_search($lang, $langs);
                    if ($langKey > 0) {
                        unset($langs[$langKey]);
                        array_unshift($langs, $lang);
                    }
                }
                foreach ($langs as $lang) {
                    if (!isset($arr[$key][$lang])) continue;
                    if (!is_string($arr[$key][$lang])) continue;
                    if (empty($arr[$key][$lang])) continue;
                    $text = self::desc($arr[$key][$lang], $length, $separator);
                    break;
                }
        }

        if ($text !== '0' && empty($text)) return null;

        return $text;
    }




    static function unsafet(string $text, string $lang = null) {
        if ($lang == null) $lang = G::lang();

        if (isset(self::$translation[$text][$lang]) &&
            self::$translation[$text][$lang]
        ) {
            return self::$translation[$text][$lang];
        }

        if (isset(self::$translationAlias[$text])) {
            if (isset(self::$translation[self::$translationAlias[$text]][$lang]) &&
                self::$translation[self::$translationAlias[$text]][$lang]
            ) {
                return self::$translation[self::$translationAlias[$text]][$lang];
            }

            return self::$translationAlias[$text];
        }

        // if (G::$debug) $text = '@' . $text;
        return $text;
    }

    static function t(string $text, string $lang = null) {
        return htmlspecialchars(self::unsafet($text, $lang), self::HTMLSPECIALCHARS_FLAGS);
    }




    /**
     * put SQL query quotes around table and field names
     * @param string $text
     * @return string
     */
    static function q(string $text) {
        return '`' . str_replace('`', '``', self::h($text)) . '`';
    }




    static function tagExtractFirst(string &$text, string $tag): string {
        $extract = '';
        if (preg_match("~<$tag>.+?</$tag>~", $text, $m)) {
            $extract = $m[0];
            $text    = preg_replace("~<$tag>.+?</$tag>~", '', $text, 1);
        }

        return $extract;
    }




    static function firstLine(string $text) {
        if (empty($text)) return '';
        $text = str_replace('<', ' <', $text);
        $text = strip_tags($text);
        $text = trim($text);
        $len  = mb_strlen($text);
        $text = strtok($text, PHP_EOL);
        $text = mb_substr((string)$text, 0, 100);
        $text = trim($text);
        if (mb_strlen($text) < $len) $text .= ' […]';

        return $text;
    }




    static function renderLinkEmail($email, string $subject = '', string $class = '', string $prepend = '', string $append = '') {
        if (!is_string($email)) return '';
        if (!$email = filter_var($email, FILTER_VALIDATE_EMAIL)) return null;
        $email = self::h($email);
        if ($subject) $subject = '?subject=' . self::h($subject);
        if (!empty($class)) $class = ' class="' . self::h($class) . '"';

        return '<a aria-label="' . self::t('Email') . '" href="mailto:' . $email . $subject . '"' . $class . '>' . $prepend . $email . $append . '</a>';
    }

    static function renderLinkTel($prefix, $tel = '', string $class = '', string $prepend = '', string $append = '') {
        if (!is_string($prefix)) return '';
        if (!is_string($tel)) return '';
        if (!$tel) return null;
        $prefix       = self::h(preg_replace('/[\D]/', '', $prefix));
        $prefixSmall  = '';
        $telStripped  = preg_replace('/^\+' . $prefix . '/', '', self::h($tel));
        $telStripped  = preg_replace('/^00' . $prefix . '/', '', $telStripped);
        $telFormatted = $telStripped;
        $telStripped  = preg_replace('/[\D]/', '', $telStripped);
        if ($prefix == '351') $telFormatted = number_format((int)$telStripped, 0, ' ', ' ');
        if (!empty($prefix)) {
            $prefix      = '+' . $prefix;
            $prefixSmall = '<small>' . $prefix . ' </small>';
            $telStripped = ltrim($telStripped, '0');
        }
        if (!empty($class)) $class = ' class="' . self::h($class) . '"';

        return '<a aria-label="' . self::t('Phone') . '" href="tel:' . $prefix . $telStripped . '"' . $class . '>' . $prepend . $prefixSmall . $telFormatted . $append . '</a>';
    }



    // todo: empty hosts
    static function nofollowHost(string $host, array $hosts = ['facebook', 'google', 'instagram', 'twitter', 'linkedin', 'youtube']) {
        foreach ($hosts as $nofollowHost)
            if (str_contains($host, $nofollowHost)) return true;

        return false;
    }




    static function formatSlug(string $text, array $existing = []) {

        $text = str_replace('<', ' <', $text);
        $text = strip_tags($text);

        $text = str_replace(['&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;'], '-', $text);

        $tr   = self::$transliteratorLower ?? self::getTransliteratorLower();
        $text = $tr->transliterate($text);

        $text = preg_replace('~[\x{1F600}-\x{1F64F}]~u', '', $text); // Match Emoticons
        $text = preg_replace('~[\x{1F300}-\x{1F5FF}]~u', '', $text); // Match Miscellaneous Symbols and Pictographs
        $text = preg_replace('~[\x{1F680}-\x{1F6FF}]~u', '', $text); // Match Transport And Map Symbols
        $text = preg_replace('~[\x{2600}-\x{26FF}]~u', '', $text); // Match Miscellaneous Symbols
        $text = preg_replace('~[\x{2700}-\x{27BF}]~u', '', $text); // Match Dingbats
        $text = preg_replace('~[^a-z0-9-]+~u', '-', $text);
        $text = preg_replace('~-+~', '-', $text);
        $text = trim($text, '-');

        if (empty($existing)) return $text;

        // if slug is in existing, append a number
        while (isset($existing[$text])) {
            if (preg_match('~-(\d+$)~', $text, $matches, PREG_OFFSET_CAPTURE)) {
                $text = substr($text, 0, 5) . ++$matches[1][0];
            } else {
                $text .= '-2';
            }
        }

        return $text;
    }




    static function formatSearch(string $text) {
        $text = self::translit($text);

        // replace non letter or digits by a space
        $text = preg_replace('~[^\pL\d]+~u', ' ', $text);

        $text = preg_replace('~\s~', ' ', $text);
        $text = str_replace('<', ' <', $text);
        $text = strip_tags($text);
        $text = preg_replace('~\s+~m', ' ', $text);

        return self::h(trim($text));
    }




    /**
     * cached IntlDateFormatter for localized date and time
     * pattern reference: http://userguide.icu-project.org/formatparse/datetime
     * pattern characters inside single quotes are escaped: 'example'
     *
     * @param mixed $value a timestamp, a DateTime or a string that creates a DateTime
     */
    static function formatDate($value, string $pattern = '', string $lang = ''): string {
        if (is_string($value) && !ctype_digit($value)) {
            $value = date_create($value);
            if (!$value instanceof DateTimeInterface) return '';
        }

        if (empty($lang) || !isset(G::langs()[$lang]))
            $lang = G::lang();

        $df = self::$intlDateFormatters[$pattern][$lang] ?? self::getIntlDateFormatter($pattern, $lang);

        return $df->format($value);
    }




    static function normalize(string $text, string $delimiter = '-', string $keep = '') {
        $text = str_replace('<', ' <', $text);
        $text = strip_tags($text);
        $text = Normalizer::normalize($text);

        // replace non letter or digits by delimiter, keeping $keep characters
        $text = preg_replace('~[^\pL\d' . preg_quote($keep) . ']+~u', $delimiter, $text);

        return $text;
    }

    static function translit(string $text, bool $lower = true) {
        if ($lower)
            $tr = self::$transliteratorLower ?? self::getTransliteratorLower();
        else
            $tr = self::$transliterator ?? self::getTransliterator();

        return $tr->transliterate($text);
    }




    static function bytesIntToAbbr(int $bytes, int $decimals = 2, $byteAlign = ''): string {
        $negative = ($bytes < 0) ? '-' : '';
        $bytes    = abs($bytes);
        if ($bytes < 1024) return $bytes . ' ' . $byteAlign . 'B';
        $size   = [' ' . $byteAlign . 'B', ' kB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
        $factor = (int)floor((strlen($bytes) - 1) / 3);

        return $negative . sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ($size[$factor] ?? '');
    }

    /**
     * converts for example 1M => 1048576 or 1k => 1024
     * @param string $size
     * @return int
     */
    public static function bytesAbbrToInt(string $size): int {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);

        $size = preg_replace('/[^0-9\\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }



    static function commentHeader(string $text) {
        $r = '';
        $r .= '/********' . str_repeat('*', strlen($text)) . '********/' . PHP_EOL;
        $r .= '/******  ' . self::h($text) . '  ******/' . PHP_EOL;
        $r .= '/********' . str_repeat('*', strlen($text)) . '********/' . PHP_EOL;

        return $r;
    }




    static function br2nl(string $text) {
        return preg_replace('~<br(\s*)?/?>~i', PHP_EOL, $text) ?? '';
    }




    private static function getTransliteratorLower() {
        if (self::$transliteratorLower == null) {
            self::$transliteratorLower = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;');
        }

        return self::$transliteratorLower;
    }


    private static function getTransliterator() {
        if (self::$transliterator == null) {
            self::$transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;');
        }

        return self::$transliterator;
    }




    private static function getIntlDateFormatter(string $pattern, string $lang) {
        if (!isset(self::$intlDateFormatters[$pattern][$lang])) {
            self::$intlDateFormatters[$pattern][$lang] = new IntlDateFormatter(
                $lang,                        // locale
                IntlDateFormatter::FULL,      // datetype
                IntlDateFormatter::NONE,      // timetype
                null,                         // timezone
                IntlDateFormatter::GREGORIAN, // calendar
                $pattern                      // pattern
            );
        }

        return self::$intlDateFormatters[$pattern][$lang];
    }

}
