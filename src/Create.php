<?php

namespace Stringy;

if (!function_exists(__NAMESPACE__ . '\create')) {
    /**
     * Creates a Stringy object and returns it on success.
     *
     * @param string|\Stringable $str
     *   Value to modify, after being cast to string.
     * @param string $encoding
     *   The character encoding.
     *
     * @return \Stringy\Stringy
     *   A Stringy instance.
     */
    function create(string|\Stringable $str, string $encoding = null)
    {
        return new Stringy($str, $encoding);
    }
}
