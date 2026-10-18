<?php

namespace App\Support;

/**
 * Translates the Urdu text of rendered pages into English using
 * resources/lang/en.json (Urdu text => English text).
 *
 * Form data is never translated: value="" attributes, data-* attributes that
 * feed forms, <textarea> contents and JSON <script> blocks are left as-is, so
 * saving a form in English mode cannot change stored Urdu values.
 */
class UiTranslator
{
    public const LOCALES = ['ur', 'en'];

    private const URDU = '\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}';

    /** data-* attributes that hold interface text rather than form data. */
    private const TEXT_DATA_ATTRIBUTES = 'confirm|label|title|message|max-message|placeholder|empty|tooltip|text|help|hint|caption|confirm-title|confirm-button';

    /** Longest phrase (in words) tried when a whole run is not in the dictionary. */
    private const MAX_PHRASE_WORDS = 8;

    private ?array $dictionary = null;

    public function translateHtml(string $html): string
    {
        $protected = [];
        $protect = function (string $text) use (&$protected): string {
            $protected[] = $text;

            return "\u{E000}".(count($protected) - 1)."\u{E001}";
        };

        // Keep form data, machine-readable blocks and translate="no" elements untouched.
        $html = preg_replace_callback(
            '#<(\w+)\b[^>]*\btranslate="no"[^>]*>.*?</\1>#is',
            fn ($m) => $protect($m[0]),
            $html
        );
        $html = preg_replace_callback(
            '#(<textarea\b[^>]*>)(.*?)(</textarea>)#is',
            fn ($m) => $m[1].$protect($m[2]).$m[3],
            $html
        );
        $html = preg_replace_callback(
            '#<script\b[^>]*type=["\']application/(?:ld\+)?json["\'][^>]*>.*?</script>#is',
            fn ($m) => $protect($m[0]),
            $html
        );
        $html = preg_replace_callback(
            '#\s(?:value|data-(?!(?:'.self::TEXT_DATA_ATTRIBUTES.')\s*=)[\w-]+)\s*=\s*(?:"[^"]*"|\'[^\']*\')#i',
            fn ($m) => $protect($m[0]),
            $html
        );

        $html = $this->translateText($html);

        return preg_replace_callback(
            "/\u{E000}(\d+)\u{E001}/u",
            fn ($m) => $protected[(int) $m[1]],
            $html
        );
    }

    /**
     * Mirrors right-to-left layout hard-coded by pages (dir="rtl", direction: rtl,
     * text-align and text-right/left classes) so English pages read left to right.
     */
    public function mirrorToLtr(string $html): string
    {
        $swapAlign = fn (string $css) => preg_replace_callback(
            '/\b(direction\s*:\s*)rtl\b|\b(text-align\s*:\s*)(right|left)\b/i',
            fn ($m) => ($m[1] ?? '') !== ''
                ? $m[1].'ltr'
                : $m[2].(strtolower($m[3]) === 'right' ? 'left' : 'right'),
            $css
        );

        // Page and component styles (blocks marked data-ltr-ready already follow the locale).
        $html = preg_replace_callback(
            '#(<style\b[^>]*>)(.*?)(</style>)#is',
            fn ($m) => str_contains($m[1], 'data-ltr-ready') ? $m[0] : $m[1].$swapAlign($m[2]).$m[3],
            $html
        );
        // Inline style attributes.
        $html = preg_replace_callback(
            '#(\sstyle\s*=\s*")([^"]*)(")#i',
            fn ($m) => $m[1].$swapAlign($m[2]).$m[3],
            $html
        );
        // dir attributes and Bootstrap alignment classes.
        $html = preg_replace('#(\sdir\s*=\s*["\'])rtl(["\'])#i', '$1ltr$2', $html);

        return preg_replace_callback(
            '#(\sclass\s*=\s*")([^"]*)(")#i',
            fn ($m) => $m[1].preg_replace_callback(
                '/(?<![\w-])text-(right|left)(?![\w-])/',
                fn ($c) => 'text-'.($c[1] === 'right' ? 'left' : 'right'),
                $m[2]
            ).$m[3],
            $html
        );
    }

    public function translateText(string $text): string
    {
        $run = '/['.self::URDU.'](?:['.self::URDU.'\s\x{200C}\x{200F}]*['.self::URDU.'])?/u';

        return preg_replace_callback($run, fn ($m) => $this->translateRun($m[0]), $text) ?? $text;
    }

    /**
     * Translates JSON response data: messages, validation errors and rendered HTML fragments.
     */
    public function translateJsonData(mixed $data, ?string $key = null): mixed
    {
        if (is_array($data)) {
            foreach ($data as $childKey => $value) {
                $textKey = is_string($childKey) && preg_match('/message|error|html|label|title|text|hint/i', $childKey);
                $data[$childKey] = $this->translateJsonData($value, $textKey ? $childKey : $key);
            }

            return $data;
        }

        if (! is_string($data) || $key === null) {
            return $data;
        }

        return str_contains($key, 'html')
            ? $this->mirrorToLtr($this->translateHtml($data))
            : $this->translateText($data);
    }

    /**
     * The part of the dictionary needed by the static JS files, which the server
     * never renders; the browser translates the text those scripts insert.
     */
    public function scriptDictionary(): array
    {
        $files = glob(public_path('assets/js/{custom,confirm-modal,form-accessibility,offline-workspace}.js'), GLOB_BRACE) ?: [];
        $version = md5(implode('|', array_map(fn ($file) => $file.filemtime($file), $files)).filemtime(lang_path('en.json')));

        return cache()->rememberForever('ui-script-dictionary-'.$version, function () use ($files) {
            $dictionary = $this->dictionary();
            $run = '/['.self::URDU.'](?:['.self::URDU.'\s\x{200C}\x{200F}]*['.self::URDU.'])?/u';
            $subset = [];
            foreach ($files as $file) {
                preg_match_all($run, (string) file_get_contents($file), $matches);
                foreach ($matches[0] as $phrase) {
                    $words = explode(' ', preg_replace('/\s+/u', ' ', trim($phrase)));
                    // Every known sub-phrase, so the browser can use the same word fallback.
                    for ($i = 0, $count = count($words); $i < $count; $i++) {
                        for ($length = 1; $length <= min(self::MAX_PHRASE_WORDS, $count - $i); $length++) {
                            $key = implode(' ', array_slice($words, $i, $length));
                            if (isset($dictionary[$key])) {
                                $subset[$key] = $dictionary[$key];
                            }
                        }
                    }
                }
            }

            return $subset;
        });
    }

    private function translateRun(string $run): string
    {
        $dictionary = $this->dictionary();
        $normalized = preg_replace('/\s+/u', ' ', $run);

        if (isset($dictionary[$normalized])) {
            return $dictionary[$normalized];
        }

        // Not a known phrase: translate the longest known word sequences, left to right.
        $words = explode(' ', $normalized);
        $output = [];
        for ($i = 0, $count = count($words); $i < $count;) {
            for ($length = min(self::MAX_PHRASE_WORDS, $count - $i); $length > 0; $length--) {
                $phrase = implode(' ', array_slice($words, $i, $length));
                if (isset($dictionary[$phrase])) {
                    $output[] = $dictionary[$phrase];
                    $i += $length;
                    continue 2;
                }
            }
            $output[] = $this->punctuation($words[$i]);
            $i++;
        }

        return implode(' ', $output);
    }

    private function punctuation(string $word): string
    {
        return strtr($word, ['۔' => '.', '،' => ',', '؟' => '?', '٪' => '%']);
    }

    private function dictionary(): array
    {
        if ($this->dictionary !== null) {
            return $this->dictionary;
        }

        $path = lang_path('en.json');
        $entries = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
        $dictionary = [];
        foreach ($entries as $urdu => $english) {
            if (! is_string($english) || $english === '') {
                continue;
            }
            // Quotes would break the HTML attributes and JS strings the text sits in.
            $dictionary[preg_replace('/\s+/u', ' ', trim($urdu))] = strtr($english, ["'" => '’', '"' => '”']);
        }

        return $this->dictionary = $dictionary;
    }
}
