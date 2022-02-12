<?php


namespace Galaxia;


/**
 * HTML element builder class with __callstatic magic methods
 *
 * Method name becomes the element name
 * Named arguments are the element attributes
 * No html validation
 *
 * Two special arguments are 'node' (string) and 'keep' (bool)
 * - These arguments don't render as attributes
 * - 'node' is the element content to be included between the tags
 * - If 'node' is empty, an empty string is returned unless 'keep' is true
 *
 * Boolean arguments are rendered only if true
 * Supports html5 self-closing tabs
 *
 * example:
 *
 * H::label(class: 'check', for: 'ch-1',
 *     node: H::input(type: 'checkbox', id: 'ch-1', checked: true) . 'example'
 * );
 *
 * becomes:
 *
 * <label class="check" for="ch-1"><input type="checkbox" id="ch-1" checked>example</label>
 *
 *
 * @method static string a(...$_)
 * @method static string abbr(...$_)
 * @method static string address(...$_)
 * @method static string area(...$_)
 * @method static string article(...$_)
 * @method static string aside(...$_)
 * @method static string audio(...$_)
 * @method static string b(...$_)
 * @method static string bdi(...$_)
 * @method static string bdo(...$_)
 * @method static string blockquote(...$_)
 * @method static string body(...$_)
 * @method static string br(...$_)
 * @method static string button(...$_)
 * @method static string canvas(...$_)
 * @method static string caption(...$_)
 * @method static string cite(...$_)
 * @method static string code(...$_)
 * @method static string col(...$_)
 * @method static string colgroup(...$_)
 * @method static string command(...$_)
 * @method static string datalist(...$_)
 * @method static string dd(...$_)
 * @method static string del(...$_)
 * @method static string details(...$_)
 * @method static string dfn(...$_)
 * @method static string div(...$_)
 * @method static string dl(...$_)
 * @method static string dt(...$_)
 * @method static string em(...$_)
 * @method static string embed(...$_)
 * @method static string fieldset(...$_)
 * @method static string figcaption(...$_)
 * @method static string figure(...$_)
 * @method static string footer(...$_)
 * @method static string form(...$_)
 * @method static string h1(...$_)
 * @method static string h2(...$_)
 * @method static string h3(...$_)
 * @method static string h4(...$_)
 * @method static string h5(...$_)
 * @method static string h6(...$_)
 * @method static string header(...$_)
 * @method static string hr(...$_)
 * @method static string html(...$_)
 * @method static string i(...$_)
 * @method static string iframe(...$_)
 * @method static string img(...$_)
 * @method static string input(...$_)
 * @method static string ins(...$_)
 * @method static string kbd(...$_)
 * @method static string keygen(...$_)
 * @method static string label(...$_)
 * @method static string legend(...$_)
 * @method static string li(...$_)
 * @method static string main(...$_)
 * @method static string map(...$_)
 * @method static string mark(...$_)
 * @method static string menu(...$_)
 * @method static string meter(...$_)
 * @method static string nav(...$_)
 * @method static string object(...$_)
 * @method static string ol(...$_)
 * @method static string optgroup(...$_)
 * @method static string option(...$_)
 * @method static string output(...$_)
 * @method static string p(...$_)
 * @method static string param(...$_)
 * @method static string pre(...$_)
 * @method static string progress(...$_)
 * @method static string q(...$_)
 * @method static string rp(...$_)
 * @method static string rt(...$_)
 * @method static string ruby(...$_)
 * @method static string s(...$_)
 * @method static string samp(...$_)
 * @method static string section(...$_)
 * @method static string select(...$_)
 * @method static string small(...$_)
 * @method static string source(...$_)
 * @method static string span(...$_)
 * @method static string strong(...$_)
 * @method static string sub(...$_)
 * @method static string summary(...$_)
 * @method static string sup(...$_)
 * @method static string table(...$_)
 * @method static string tbody(...$_)
 * @method static string td(...$_)
 * @method static string textarea(...$_)
 * @method static string tfoot(...$_)
 * @method static string th(...$_)
 * @method static string thead(...$_)
 * @method static string time(...$_)
 * @method static string tr(...$_)
 * @method static string track(...$_)
 * @method static string u(...$_)
 * @method static string ul(...$_)
 * @method static string var(...$_)
 * @method static string video(...$_)
 * @method static string wbr(...$_)
 */
class H {

    const selfClosing = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'menuitem',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    static string $indentation = '    ';


    static function __callStatic(string $name, array $arguments): string {
        if (count($arguments) == 1 && is_array($arguments[0])) $arguments = $arguments[0];

        $name  = strtolower(htmlspecialchars($name));
        $short = in_array($name, self::selfClosing);

        $node = (string)($arguments['node'] ?? '');
        unset($arguments['node']);

        $keep = (bool)($arguments['keep'] ?? false);
        unset($arguments['keep']);

        if (!$short && !$keep && $node == '') return '';

        $attributes = '';
        foreach ($arguments as $att => $val) {
            if (is_bool($val)) {
                if ($val) $attributes .= ' ' . strtolower(htmlspecialchars($att));
            } else {
                $attributes .= ' ' . strtolower(htmlspecialchars($att)) . '="' . htmlspecialchars($val) . '"';
            }
        }

        if ($short) return '<' . $name . $attributes . '>';

        return '<' . $name . $attributes . '>' . $node . '</' . $name . '>';
    }


    static function indent(string $text, int $indent = 0): string {
        if (!$indent) return $text;

        $indentation = str_repeat(self::$indentation, $indent);

        return $indentation . str_replace(PHP_EOL, PHP_EOL . $indentation, trim($text));
    }

}
