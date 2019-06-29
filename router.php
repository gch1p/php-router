<?php

class router {

    protected $routes = [
        'children' => [],
        're_children' => []
    ];

    public function add($template, $value) {
        if ($template == '') {
            return;
        }

        // expand {enum,erat,ions}
        $templates = [[$template, $value]];
        if (preg_match_all('/\{([\w\d_\-,]+)\}/', $template, $matches)) {
            foreach ($matches[1] as $match_index => $variants) {
                $variants = explode(',', $variants);
                $variants = array_map('trim', $variants);
                $variants = array_filter($variants, function($s) { return $s != ''; });

                for ($i = 0; $i < count($templates); ) {
                    list($template, $value) = $templates[$i];
                    $new_templates = [];
                    foreach ($variants as $variant_index => $variant) {
                        $new_templates[] = [
                            str_replace_once($matches[0][$match_index], $variant, $template),
                            str_replace('${'.($match_index+1).'}', $variant, $value)
                        ];
                    }
                    array_splice($templates, $i, 1, $new_templates);
                    $i += count($new_templates);
                }
            }
        }

        // process all generated routes
        foreach ($templates as $template) {
            list($template, $value) = $template;

            $start_pos = 0;
            $parent = &$this->routes;
            $template_len = strlen($template);

            while ($start_pos < $template_len) {
                $slash_pos = strpos($template, '/', $start_pos);
                if ($slash_pos !== false) {
                    $part = substr($template, $start_pos, $slash_pos-$start_pos+1);
                    $start_pos = $slash_pos+1;
                } else {
                    $part = substr($template, $start_pos);
                    $start_pos = $template_len;
                }

                $parent = &$this->_addRoute($parent, $part,
                    $start_pos < $template_len ? null : $value);
            }
        }
    }

    protected function &_addRoute(&$parent, $part, $value = null) {
        $par_pos = strpos($part, '(');
        $is_regex = $par_pos !== false && ($par_pos == 0 || $part[$par_pos-1] != '\\');

        $children_key = !$is_regex ? 'children' : 're_children';

        if (isset($parent[$children_key][$part])) {
            if (is_null($value)) {
                $parent = &$parent[$children_key][$part];
            } else {
                if (!isset($parent[$children_key][$part]['value'])) {
                    $parent[$children_key][$part]['value'] = $value;
                } else {
                    trigger_error(__METHOD__.': route is already defined');
                }
            }
            return $parent;
        }

        $child = [
            'children' => [],
            're_children' => []
        ];
        if (!is_null($value)) {
            $child['value'] = $value;
        }

        $parent[$children_key][$part] = $child;
        return $parent[$children_key][$part];
    }

    public function find($uri) {
        if ($uri != '/' && $uri[0] == '/') {
            $uri = substr($uri, 1);
        }
        $start_pos = 0;
        $parent = &$this->routes;
        $uri_len = strlen($uri);
        $matches = [];

        while ($start_pos < $uri_len) {
            $slash_pos = strpos($uri, '/', $start_pos);
            if ($slash_pos !== false) {
                $part = substr($uri, $start_pos, $slash_pos-$start_pos+1);
                $start_pos = $slash_pos+1;
            } else {
                $part = substr($uri, $start_pos);
                $start_pos = $uri_len;
            }

            $found = false;
            if (isset($parent['children'][$part])) {
                $parent = &$parent['children'][$part];
                $found = true;
            } else if (!empty($parent['re_children'])) {
                foreach ($parent['re_children'] as $re => &$child) {
                    if (preg_match('#^'.$re.'$#', $part, $match)) {
                        if (count($match) > 1) {
                            $matches = array_merge($matches, array_slice($match, 1));
                        }
                        $parent = &$child;
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                return false;
            }
        }

        if (!isset($parent['value'])) {
            return false;
        }

        $value = $parent['value'];
        if (!empty($matches)) {
            foreach ($matches as $i => $match) {
                $needle = '$('.($i+1).')';
                $pos = strpos($value, $needle);
                if ($pos !== false) {
                    $value = substr_replace($value, $match, $pos, strlen($needle));
                }
            }
        }

        return $value;
    }

    public function load($routes) {
        $this->routes = $routes;
    }

    public function dump() {
        return $this->routes;
    }

}

if (!function_exists('str_replace_once')) {
    function str_replace_once($needle, $replace, $haystack) {
        $pos = strpos($haystack, $needle);
        if ($pos !== false) {
            $haystack = substr_replace($haystack, $replace, $pos, strlen($needle));
        }
        return $haystack;
    }
}
