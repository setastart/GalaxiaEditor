<?php
/* Copyright 2017-2020 Ino Detelić

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

use Galaxia\Director;


const ALLOWED_TAGS           = '<a><h1><h2><strong><p><br><em><del><blockquote><pre><ul><ol><li>';
const HTMLSPECIALCHARS_FLAGS = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5;

const TEST_HTML = <<<HTML
<h1>Example <strong>Test Heading 1</strong> that <del>is</del> 49 <em>characters</em> long</h1><p>First paragraph, a bit <strong>long so it wraps around</strong>, to see how it <em>looks if it wraps</em>. This first paragraph <del>doesn't have</del> any newlines and is 155 characters long.</p><p>Second short paragraph with newine here.<br>This line is a soft break from the previous.<br>Another line with a <a href="https://setastart.com">hyperlink</a>.</p><h2>Example <strong>Test Heading 2</strong> that <del>is</del> 49 <em>characters</em> long</h2><p>Third short paragraph.</p><ul><li>Unordered list item one.<br>Soft break into a long line to see how it wraps. Lorem ipsum dolor sit amet everything bla bla bla andalongfakewordwhynot.</li><li>Unordered list item two.<ul><li>Subitem one.<ul><li>Subsubitem one. I'm making this one longer so it wraps to see where it ends up. This item is 113 characters long.</li><li>Subsubitem two.</li></ul></li></ul></li></ul><ol><li>Unordered list item one.<br>Soft break into a long line to see how it wraps. Lorem ipsum dolor sit amet everything bla bla bla andalongfakewordwhynot.</li><li>Unordered list item two.<ol><li>Subitem one.<ol><li>Subsubitem one. I'm making this one longer so it wraps to see where it ends up. This item is 113 characters long.</li><li>Subsubitem two.</li></ol></li></ol></li></ol><pre>This is a code fragment or something. First line, a bit long so it wraps around, to see how it looks if it wraps. This first line ends in a newline after the dot.
Second Line.</pre><blockquote>This is a quotation or something. First line, a bit long so it wraps around, to see how it looks if it wraps. This first line ends in a newline after the dot.<br>Second Line.</blockquote>
HTML;

function h($text, bool $condition = true) {
    if ($condition) return htmlspecialchars((string)$text, HTMLSPECIALCHARS_FLAGS);

    return null;
}

function unsafe($text, bool $condition = true) {
    if ($condition) return $text;

    return null;
}

function hg(array $arr, string $key, string $lang = '') {
    if (empty($arr)) return null;
    if (!isset($arr[$key])) return null;
    $text = null;

    switch (gettype($arr[$key])) {
        case 'integer':
        case 'double':
            $text = (string)$arr[$key];
            break;

        case 'string':
            $text = htmlspecialchars($arr[$key], HTMLSPECIALCHARS_FLAGS) ?: null;
            break;

        case 'array':
            $langs = Director::getApp()->langs;
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
                $text = htmlspecialchars($arr[$key][$lang], HTMLSPECIALCHARS_FLAGS);
                break;
            }
            break;
    }

    if ($text !== '0' && empty($text)) return null;

    return $text;
}




function unsafeg(array $arr, string $key, string $lang = '') {
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
            $langs = Director::getApp()->langs;
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




function st(string $text, int $h1 = 0, int $f1 = 0, int $f2 = 0) {
    // if (Director::$dev) $text = TEST_HTML;
    if (empty($text)) return '';
    $text = trim(strip_tags($text, ALLOWED_TAGS));
    if (empty($text)) return '';
    if ($h1 == 0) return $text;

    // add target="_blank" rel="noopener" to outgoing links
    $host = explode('.', $_SERVER['HTTP_HOST']);
    $host = implode('\.', $host);
    $re   = '~<a href="https?(?!://' . $host . '/)://([^:/\s">]+)~m';

    $text = preg_replace_callback($re, function ($matches) {
        $inject = 'target="_blank" rel="noopener';
        if (nofollowHost($matches[1])) $inject .= ' nofollow';

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

function stg(array $arr, string $key, int $h1 = 0, int $f1 = 0, int $f2 = 0, string $lang = '') {
    if (!isset($arr[$key])) return null;
    $text = '';

    switch (gettype($arr[$key])) {
        case 'integer':
        case 'double':
            $text = (string)$arr[$key];
            break;

        case 'string':
            $text = st($arr[$key], $h1, $f1, $f2);
            break;

        case 'array':
            $langs = Director::getApp()->langs;
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
                $text = st($arr[$key][$lang], $h1, $f1, $f2);
                break;
            }
            break;
    }

    if ($text !== '0' && empty($text)) return null;

    return $text;
}




function stp(string $text, int $f1 = 0, int $f2 = 0, int $fp = 0) {
    // if (Director::$dev) $text = TEST_HTML;
    if (empty($text)) return '';
    $text = trim(strip_tags($text, ALLOWED_TAGS));
    if (empty($text)) return '';

    // add target="_blank" rel="noopener" to outgoing links
    $host = explode('.', $_SERVER['HTTP_HOST']);
    $host = implode('\.', $host);
    $re   = '~<a href="https?(?!://' . $host . '/)://([^:/\s">]+)~m';

    $text = preg_replace_callback($re, function ($matches) {
        $inject = 'target="_blank" rel="noopener';
        if (nofollowHost($matches[1])) $inject .= ' nofollow';

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

function stpg(array $arr, string $key, int $f1 = 0, int $f2 = 0, int $fp = 0, string $lang = '') {
    if (!isset($arr[$key])) return null;
    $text = '';

    switch (gettype($arr[$key])) {
        case 'integer':
        case 'double':
            $text = (string)$arr[$key];
            break;

        case 'string':
            $text = stp($arr[$key], $f1, $f2, $fp);
            break;

        case 'array':
            $langs = Director::getApp()->langs;
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
                $text = stp($arr[$key][$lang], $f1, $f2, $fp);
                break;
            }
            break;
    }

    if ($text !== '0' && empty($text)) return null;

    return $text;
}




function desc(string $html, int $length = null, string $separator = ' / ') {
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
        $text = preg_replace('~\s+~u', ' ', $text);
    }
    $text = preg_replace('~\s*\.\s*\n+~m', '. ', $text);
    $text = preg_replace('~\s*:\s*\n+~m', ': ', $text);
    $text = preg_replace('~\s*\n+\s*~m', $separator, $text);
    $text = preg_replace('~\s+~u', ' ', $text);
    $text = preg_replace('~\s?,\s?~', ', ', $text);

    if ($length > 0 && mb_strlen($text) > $length) {
        $text = mb_substr($text, 0, $length) . '[…]';
    }

    return htmlspecialchars($text, HTMLSPECIALCHARS_FLAGS);
}

function descg(array $arr, string $key, int $length = null, string $separator = ' / ', string $lang = '') {
    if (!isset($arr[$key])) return null;
    if (is_null($length)) $length = 255;
    $text = '';

    switch (gettype($arr[$key])) {
        case 'integer':
        case 'double':
            $text = (string)$arr[$key];
            break;

        case 'string':
            $text = desc($arr[$key], $length, $separator);
            break;

        case 'array':
            $langs = Director::getApp()->langs;
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
                $text = desc($arr[$key][$lang], $length, $separator);
                break;
            }
    }

    if ($text !== '0' && empty($text)) return null;

    return $text;
}



function t($text, $lang = null) {
    $app = Director::getApp();
    if ($lang == null) $lang = $app->lang;
    if (isset(Director::$translations[$text][$lang])) {
        return htmlspecialchars(Director::$translations[$text][$lang], HTMLSPECIALCHARS_FLAGS);
    }

    // if (Director::$debug) $text = '@' . $text;
    return htmlspecialchars($text, HTMLSPECIALCHARS_FLAGS);
}



function unsafet($text, $lang = null) {
    $app = Director::getApp();
    if ($lang == null) $lang = $app->lang;
    if (isset(Director::$translations[$text][$lang])) {
        return Director::$translations[$text][$lang];
    }

    // if (Director::$debug) $text = '@' . $text;
    return $text;
}




// put SQL query quotes around table and field names
function q($text) {
    return '`' . str_replace('`', '``', h($text)) . '`';
}


function firstLine(string $text) {
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


// debug
function d() {
    foreach (func_get_args() as $arg) {
        ob_start();
        var_dump($arg);
        $dump = ob_get_clean();
        $dump = preg_replace('/=>\n\s+/m', ' => ', (string)$dump);
        $dump = str_replace('<?php ', '', (string)$dump);
        echo $dump;
    }
}

// debug with <pre>
function dp() {
    $args = func_get_args();
    echo '<pre>';
    call_user_func_array('d', $args);
    echo '</pre>';
}

// debug and die
function dd() {
    $args = func_get_args();
    call_user_func_array('d', $args);
    exit();
}

// debug and die with <pre>
function ddp() {
    $args = func_get_args();
    echo '<pre>';
    call_user_func_array('d', $args);
    echo '</pre>';
    exit();
}

// debug backtrace
function db() {
    $args      = func_get_args();
    $backtrace = array_reverse(debug_backtrace());
    $error     = '';
    foreach ($backtrace as $trace) {
        if (empty($trace)) continue;
        $error .= '<span class="select-on-click">' . $trace['file'] . ':' . $trace['line'] . '</span><br>';
    }
    echo $error;
    echo '<pre>';
    call_user_func_array('d', $args);
    echo '</pre>';
}




// Render links

function renderLinkEmail($email, string $subject = '', string $class = '', string $prepend = '', string $append = '') {
    if (!is_string($email)) return '';
    if (!$email = filter_var($email, FILTER_VALIDATE_EMAIL)) return null;
    $email = h($email);
    if ($subject) $subject = '?subject=' . h($subject);
    if (!empty($class)) $class = ' class="' . h($class) . '"';

    return '<a aria-label="' . t('Email') . '" href="mailto:' . $email . $subject . '"' . $class . '>' . $prepend . $email . $append . '</a>';
}

function renderLinkTel($prefix, $tel = '', string $class = '', string $prepend = '', string $append = '') {
    if (!is_string($prefix)) return '';
    if (!is_string($tel)) return '';
    if (!$tel) return null;
    $prefix       = h(preg_replace('/[\D]/', '', $prefix));
    $prefixSmall  = '';
    $telStripped  = preg_replace('/^\+' . $prefix . '/', '', h($tel));
    $telStripped  = preg_replace('/^00' . $prefix . '/', '', $telStripped);
    $telFormatted = $telStripped;
    $telStripped  = preg_replace('/[\D]/', '', $telStripped);
    if ($prefix == '351') $telFormatted = number_format((int)$telStripped, 0, ' ', ' ');
    if (!empty($prefix)) {
        $prefix      = '+' . $prefix;
        $prefixSmall = '<small>' . $prefix . ' </small>';
        $telStripped = ltrim($telStripped, '0');
    }
    if (!empty($class)) $class = ' class="' . h($class) . '"';

    return '<a aria-label="' . t('Phone') . '" href="tel:' . $prefix . $telStripped . '"' . $class . '>' . $prepend . $prefixSmall . $telFormatted . $append . '</a>';
}




// nofollow

function nofollowHost(string $host) {
    foreach (Director::$nofollowHosts as $nofollowHost)
        if (strpos($host, $nofollowHost))
            return $nofollowHost;

    return false;
}




function gFormatSlug(string $text, array $existing = []) {

    $text = str_replace('<', ' <', $text);
    $text = strip_tags($text);

    $text = str_replace(['&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;'], '-', $text);

    $tr   = Director::$transliteratorLower ?? Director::getTransliteratorLower();
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



function gNormalize($text, $delimiter = '-', $keep = '') {
    $text = str_replace('<', ' <', $text);
    $text = strip_tags($text);
    $text = Normalizer::normalize($text);

    // replace non letter or digits by delimiter, keeping $keep characters
    $text = preg_replace('~[^\pL\d' . preg_quote($keep) . ']+~u', $delimiter, $text);

    return $text;
}




function gTranslit($text, $lower = true) {
    if ($lower)
        $tr = Director::$transliteratorLower ?? Director::getTransliteratorLower();
    else
        $tr = Director::$transliterator ?? Director::getTransliterator();

    return $tr->transliterate($text);
}




function gPrepareTextForSearch(string $text) {
    $text = gTranslit($text);

    // replace non letter or digits by a space
    $text = preg_replace('~[^\pL\d]+~u', ' ', $text);

    $text = preg_replace('~\s~', ' ', $text);
    $text = str_replace('<', ' <', $text);
    $text = strip_tags($text);
    $text = preg_replace('~\s+~m', ' ', $text);

    return h(trim($text));
}




// cached IntlDateFormatter for localized date and time
// pattern reference: http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details
// escape with single quotes ''

function gFormatDate($value, string $pattern = '', string $lang = '') {
    $app = Director::getApp();

    if (empty($lang) || !isset($app->langs[$lang]))
        $lang = $app->lang;

    $df = Director::$intlDateFormatters[$pattern][$lang] ?? Director::getIntlDateFormatter($pattern, $lang);

    return $df->format($value);
}




function gFilesize(int $bytes, int $decimals = 2): string {
    if ($bytes < 1024) return $bytes . ' B';
    $size   = [' B', ' kB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
    $factor = (int)floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ($size[$factor] ?? '');
}




function commentHeader(string $text) {
    $r = '';
    $r .= '/********' . str_repeat('*', strlen($text)) . '********/' . PHP_EOL;
    $r .= '/******  ' . h($text) . '  ******/' . PHP_EOL;
    $r .= '/********' . str_repeat('*', strlen($text)) . '********/' . PHP_EOL;
    return $r;
}
