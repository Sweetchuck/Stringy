<?php

declare(strict_types = 1);

namespace Stringy;

use ReturnTypeWillChange;

class Stringy implements \ArrayAccess, \Countable, \IteratorAggregate, \Stringable
{
    /**
     * An instance's string.
     */
    protected string $str;

    /**
     * The string's encoding, which should be one of the mbstring module's
     * supported encodings.
     */
    protected string $encoding;

    protected string $globalRegexEncoding = '';

    /**
     * Creates a Stringy object and assigns both str and encoding properties
     * the supplied values. $str is cast to a string prior to assignment, and if
     * $encoding is not specified, it defaults to mb_internal_encoding(). It
     * then returns the initialized object.
     *
     * @param string|\Stringable $str
     *   Value to modify, after being cast to string.
     * @param null|string $encoding
     *   The character encoding.
     *
     * @return static A Stringy object.
     *
     * @throws \InvalidArgumentException
     *   if an array or object without a __toString() method is passed as the
     *   first argument.
     */
    public static function create(string|\Stringable $str = '', ?string $encoding = null): static
    {
        return new static($str, $encoding);
    }

    /**
     * Initializes a Stringy object and assigns both str and encoding properties
     * the supplied values. $str is cast to a string prior to assignment, and if
     * $encoding is not specified, it defaults to mb_internal_encoding().
     *
     * @param string|\Stringable $str
     *   Value to modify, after being cast to string.
     * @param null|string $encoding
     *   The character encoding.
     */
    public function __construct(string|\Stringable $str = '', ?string $encoding = null)
    {
        $this->str = (string) $str;
        $this->encoding = $encoding ?: \mb_internal_encoding();
    }

    # region \ArrayAccess
    /**
     * Returns whether or not a character exists at an index. Offsets may be
     * negative to count from the last character in the string. Implements
     * part of the ArrayAccess interface.
     *
     * @param int $offset
     *   The index to check.
     *
     * @return bool
     *   Whether or not the index exists.
     */
    public function offsetExists($offset): bool
    {
        $length = $this->length();
        settype($offset, 'int');
        if ($offset >= 0) {
            return ($length > $offset);
        }

        return ($length >= abs($offset));
    }

    /**
     * Returns the character at the given index. Offsets may be negative to
     * count from the last character in the string. Implements part of the
     * ArrayAccess interface, and throws an OutOfBoundsException if the index
     * does not exist.
     *
     * @param int $offset
     *   The index from which to retrieve the char.
     *
     * @return string
     *   The character at the specified index.
     *
     * @throws \OutOfBoundsException
     *   If the positive or negative offset does not exist.
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        settype($offset, 'int');
        $length = $this->length();

        if (($offset >= 0 && $length <= $offset) || $length < abs($offset)) {
            throw new \OutOfBoundsException('No character exists at the index');
        }

        return \mb_substr($this->str, $offset, 1, $this->encoding);
    }

    /**
     * Implements part of the ArrayAccess interface, but throws an exception
     * when called. This maintains the immutability of Stringy objects.
     *
     * @param mixed $offset
     *   The index of the character.
     * @param mixed $value
     *   Value to set.
     *
     * @throws \Exception
     *   When called.
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        // Stringy is immutable, cannot directly set char
        throw new \Exception('Stringy object is immutable, cannot modify char');
    }

    /**
     * Implements part of the ArrayAccess interface, but throws an exception
     * when called. This maintains the immutability of Stringy objects.
     *
     * @param mixed $offset
     *   The index of the character.
     *
     * @throws \Exception
     *   When called.
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        // Don't allow directly modifying the string
        throw new \Exception('Stringy object is immutable, cannot unset char');
    }
    # endregion

    # region \Countable
    /**
     * Returns the length of the string, implementing the countable interface.
     *
     * @return int
     *   The number of characters in the string, given the encoding.
     */
    public function count(): int
    {
        return \mb_strlen($this->str, $this->encoding);
    }
    # endregion

    # region \IteratorAggregate
    /**
     * Returns a new ArrayIterator, thus implementing the IteratorAggregate
     * interface. The ArrayIterator's constructor is passed an array of chars
     * in the multibyte string. This enables the use of foreach with instances
     * of Stringy\Stringy.
     *
     * @return \ArrayIterator
     *   An iterator for the characters in the string.
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->chars());
    }
    # endregion

    # region \Stringable
    /**
     * Returns the value in $str.
     *
     * @return string
     *   The current value of the $str property.
     */
    public function __toString(): string
    {
        return $this->str;
    }
    # endregion

    /**
     * Returns a new string with $string appended.
     *
     * @param  string $string
     *   The string to append.
     * @return static
     *   Object with appended $string.
     */
    public function append(string|\Stringable $string): static
    {
        return static::create($this->str . $string, $this->encoding);
    }

    /**
     * Returns the character at $index, with indexes starting at 0.
     *
     * @param int $index
     *   Position of the character.
     * @return static
     *   The character at $index.
     */
    public function at(int $index): static
    {
        return $this->substr($index, 1);
    }

    /**
     * Returns the substring between $start and $end, if found, or an empty
     * string. An optional offset may be supplied from which to begin the
     * search for the start string.
     *
     * @param string $start
     *   Delimiter marking the start of the substring.
     * @param string $end
     *   Delimiter marking the end of the substring.
     * @param int $offset
     *   Index from which to begin the search.
     *
     * @return static
     *   Object whose $str is a substring between $start and $end.
     */
    public function between(
        string|\Stringable $start,
        string|\Stringable $end,
        int $offset = 0,
    ): static {
        $startIndex = $this->indexOf($start, $offset);
        if ($startIndex === false) {
            return static::create('', $this->encoding);
        }

        $substrIndex = $startIndex + \mb_strlen((string) $start, $this->encoding);
        $endIndex = $this->indexOf($end, $substrIndex);
        if ($endIndex === false) {
            return static::create('', $this->encoding);
        }

        return $this->substr($substrIndex, $endIndex - $substrIndex);
    }

    /**
     * Returns a camelCase version of the string. Trims surrounding spaces,
     * capitalizes letters following digits, spaces, dashes and underscores,
     * and removes spaces, dashes, as well as underscores.
     *
     * @return static
     *   Object with $str in camelCase.
     */
    public function camelize(): static
    {
        $encoding = $this->encoding;
        $stringy = $this->trim()->lowerCaseFirst();
        $stringy->str = preg_replace('/^[-_]+/', '', $stringy->str);

        $stringy->str = preg_replace_callback(
            '/[-_\s]+(.)?/u',
            function ($match) use ($encoding) {
                if (isset($match[1])) {
                    return \mb_strtoupper($match[1], $encoding);
                }

                return '';
            },
            $stringy->str
        );

        $stringy->str = preg_replace_callback(
            '/[\d]+(.)?/u',
            function ($match) use ($encoding) {
                return \mb_strtoupper($match[0], $encoding);
            },
            $stringy->str
        );

        return $stringy;
    }

    /**
     * Returns an array consisting of the characters in the string.
     *
     * @return array
     *   An array of string chars.
     */
    public function chars(): array
    {
        $chars = [];
        for ($i = 0, $l = $this->length(); $i < $l; $i++) {
            $chars[] = $this->at($i)->str;
        }

        return $chars;
    }

    /**
     * Trims the string and replaces consecutive whitespace characters with a
     * single space. This includes tabs and newline characters, as well as
     * multibyte whitespace such as the thin space and ideographic space.
     *
     * @return static
     *   Object with a trimmed $str and condensed whitespace.
     */
    public function collapseWhitespace(): static
    {
        return $this->regexReplace('[[:space:]]+', ' ')->trim();
    }

    /**
     * Returns true if the string contains $needle, false otherwise. By default
     * the comparison is case-sensitive, but can be made insensitive by setting
     * $caseSensitive to false.
     *
     * @param string|\Stringable $needle
     *   Substring to look for.
     * @param bool $caseSensitive
     *   Whether or not to enforce case-sensitivity.
     *
     * @return bool
     *   Whether or not $str contains $needle.
     */
    public function contains(
        string|\Stringable $needle,
        bool $caseSensitive = true
    ): bool {
        $encoding = $this->encoding;

        if ($caseSensitive) {
            return \mb_strpos($this->str, (string) $needle, 0, $encoding) !== false;
        }

        return \mb_stripos($this->str, (string) $needle, 0, $encoding) !== false;
    }

    /**
     * Returns true if the string contains all $needles, false otherwise. By
     * default the comparison is case-sensitive, but can be made insensitive by
     * setting $caseSensitive to false.
     *
     * @param string[] $needles
     *   Substrings to look for.
     * @param bool $caseSensitive
     *   Whether or not to enforce case-sensitivity.
     *
     * @return bool
     *   Whether or not $str contains $needle.
     */
    public function containsAll(iterable $needles, bool $caseSensitive = true): bool
    {
        $hasNeedles = false;
        foreach ($needles as $needle) {
            $hasNeedles = true;

            if (!$this->contains($needle, $caseSensitive)) {
                return false;
            }
        }

        return $hasNeedles;
    }

    /**
     * Returns true if the string contains any $needles, false otherwise. By
     * default the comparison is case-sensitive, but can be made insensitive by
     * setting $caseSensitive to false.
     *
     * @param string[] $needles
     *   Substrings to look for,
     * @param bool $caseSensitive
     *   Whether or not to enforce case-sensitivity.
     *
     * @return bool
     *   Whether or not $str contains $needle.
     */
    public function containsAny(iterable $needles, bool $caseSensitive = true): bool
    {
        foreach ($needles as $needle) {
            if ($this->contains($needle, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the number of occurrences of $substring in the given string.
     * By default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param string $substring
     *   The substring to search for.
     * @param bool $caseSensitive
     *   Whether or not to enforce case-sensitivity.
     *
     * @return int
     *   The number of $substring occurrences.
     */
    public function countSubstr(
        string|\Stringable $substring,
        bool $caseSensitive = true,
    ): int {
        $needle = (string) $substring;
        if ($caseSensitive) {
            return \mb_substr_count($this->str, $needle, $this->encoding);
        }

        return \mb_substr_count(
            \mb_strtoupper($this->str, $this->encoding),
            \mb_strtoupper($needle, $this->encoding),
            $this->encoding,
        );
    }

    /**
     * Returns a lowercase and trimmed string separated by dashes. Dashes are
     * inserted before uppercase characters (with the exception of the first
     * character of the string), and in place of spaces as well as underscores.
     *
     * @return static
     *   Object with a dasherized $str.
     */
    public function dasherize(): static
    {
        return $this->delimit('-');
    }

    /**
     * Returns a lowercase and trimmed string separated by the given delimiter.
     * Delimiters are inserted before uppercase characters (with the exception
     * of the first character of the string), and in place of spaces, dashes,
     * and underscores. Alpha delimiters are not converted to lowercase.
     *
     * @param string $delimiter
     *   Sequence used to separate parts of the string.
     *
     * @return static
     *   Object with a delimited $str.
     */
    public function delimit(string $delimiter): static
    {
        $this->globalRegexEncodingSave();

        $str = $this->eregReplace('\B([A-Z])', '-\1', (string) $this->trim());
        $str = \mb_strtolower($str, $this->encoding);
        $str = $this->eregReplace('[-_\s]+', $delimiter, $str);

        $this->globalRegexEncodingRestore();

        return static::create($str, $this->encoding);
    }

    /**
     * Returns true if the string ends with $substring, false otherwise. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param string $suffix
     *   The substring to look for.
     * @param bool $caseSensitive
     *   Whether or not to enforce case-sensitivity.
     *
     * @return bool
     *   Whether or not $str ends with $substring
     */
    public function endsWith(string|\Stringable $suffix, bool $caseSensitive = true): bool
    {
        $expected = (string) $suffix;
        $expectedLength = \mb_strlen($expected, $this->encoding);
        $strLength = $this->length();

        $actual = \mb_substr(
            $this->str,
            $strLength - $expectedLength,
            $expectedLength,
            $this->encoding,
        );

        if (!$caseSensitive) {
            $expected = \mb_strtolower($expected, $this->encoding);
            $actual = \mb_strtolower($actual, $this->encoding);
        }

        return $expected === $actual;
    }

    /**
     * Returns true if the string ends with any of $substrings, false otherwise.
     * By default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param string[] $substrings
     *   Substrings to look for
     * @param bool $caseSensitive
     *   Whether or not to enforce case-sensitivity.
     *
     * @return bool
     *   Whether or not $str ends with $substring.
     */
    public function endsWithAny(iterable $substrings, bool $caseSensitive = true): bool
    {
        foreach ($substrings as $substring) {
            if ($this->endsWith($substring, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensures that the string begins with $substring. If it doesn't, it's
     * prepended.
     *
     * @param string $substring
     *   The substring to add if not present
     *
     * @return static
     *   Object with its $str prefixed by the $substring.
     */
    public function ensureLeft(string|\Stringable $substring): static
    {
        $stringy = static::create($this->str, $this->encoding);

        if (!$stringy->startsWith($substring)) {
            $stringy->str = $substring . $stringy->str;
        }

        return $stringy;
    }

    /**
     * Ensures that the string ends with $substring. If it doesn't, it's
     * appended.
     *
     * @param string $substring
     *   The substring to add if not present.
     *
     * @return static
     *   Object with its $str suffixed by the $substring.
     */
    public function ensureRight(string|\Stringable $substring): static
    {
        $stringy = static::create($this->str, $this->encoding);

        if (!$stringy->endsWith($substring)) {
            $stringy->str .= $substring;
        }

        return $stringy;
    }

    /**
     * Returns the first $n characters of the string.
     *
     * @param int $n
     *   Number of characters to retrieve from the start.
     *
     * @return static
     *   Object with its $str being the first $n chars.
     */
    public function first(int $n): static
    {
        $stringy = static::create($this->str, $this->encoding);

        if ($n < 0) {
            $stringy->str = '';

            return $stringy;
        }

        return $stringy->substr(0, $n);
    }

    /**
     * Returns the encoding used by the Stringy object.
     *
     * @return string
     *   The current value of the $encoding property.
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Returns true if the string contains a lower case char, false
     * otherwise.
     *
     * @return bool
     *   Whether or not the string contains a lower case character.
     */
    public function hasLowerCase(): bool
    {
        return $this->matchesPattern('.*[[:lower:]]');
    }

    /**
     * Returns true if the string contains an upper case char, false
     * otherwise.
     *
     * @return bool
     *   Whether or not the string contains an upper case character.
     */
    public function hasUpperCase(): bool
    {
        return $this->matchesPattern('.*[[:upper:]]');
    }

    /**
     * Convert all HTML entities to their applicable characters. An alias of
     * html_entity_decode. For a list of flags, refer to
     * http://php.net/manual/en/function.html-entity-decode.php
     *
     * @param int $flags
     *   Optional flags.
     *
     * @return static
     *   Object with the resulting $str after being html decoded.
     *
     * @see \html_entity_decode()
     */
    public function htmlDecode(int $flags = \ENT_COMPAT): static
    {
        $str = html_entity_decode($this->str, $flags, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Convert all applicable characters to HTML entities. An alias of
     * htmlentities. Refer to http://php.net/manual/en/function.htmlentities.php
     * for a list of flags.
     *
     * @param int $flags
     *   Optional flags.
     *
     * @return static
     *   Object with the resulting $str after being html encoded.
     *
     * @see \htmlentities()
     */
    public function htmlEncode(int $flags = \ENT_COMPAT): static
    {
        $str = htmlentities($this->str, $flags, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Capitalizes the first word of the string, replaces underscores with
     * spaces, and strips '_id'.
     *
     * @return static
     *   Object with a humanized $str.
     */
    public function humanize(): static
    {
        $str = str_replace(['_id', '_'], ['', ' '], $this->str);

        return static::create($str, $this->encoding)->trim()->upperCaseFirst();
    }

    /**
     * Returns the index of the first occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search.
     *
     * @param string|\Stringable $needle
     *   Substring to look for.
     * @param int $offset
     *   Offset from which to search.
     *
     * @return int|false
     *   The occurrence's index if found, otherwise false.
     */
    public function indexOf(
        string|\Stringable $needle,
        int $offset = 0
    ): bool|int {
        return \mb_strpos(
            $this->str,
            (string) $needle,
            $offset,
            $this->encoding,
        );
    }

    /**
     * Returns the index of the last occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search. Offsets may be negative to count from the last character
     * in the string.
     *
     * @param string $needle
     *   Substring to look for.
     * @param int $offset
     *   Offset from which to search.
     *
     * @return int|false
     *   The last occurrence's index if found, otherwise false.
     */
    public function indexOfLast(string $needle, int $offset = 0): bool|int
    {
        return \mb_strrpos(
            $this->str,
            $needle,
            $offset,
            $this->encoding
        );
    }

    /**
     * Inserts $substring into the string at the $index provided.
     *
     * @param string $substring
     *   String to be inserted.
     * @param int $index
     *   The index at which to insert the substring.
     *
     * @return static
     *   Object with the resulting $str after the insertion.
     *
     * @todo Support for negative $index.
     */
    public function insert(
        string|\Stringable $substring,
        int $index,
    ): static {
        $stringy = static::create($this->str, $this->encoding);
        if ($index > $stringy->length()) {
            return $stringy;
        }

        $start = \mb_substr($stringy->str, 0, $index, $stringy->encoding);
        $end = \mb_substr($stringy->str, $index, $stringy->length(), $stringy->encoding);

        $stringy->str = $start . $substring . $end;

        return $stringy;
    }

    /**
     * Returns true if the string contains only alphabetic chars, false
     * otherwise.
     *
     * @return bool
     *   Whether or not $str contains only alphabetic chars.
     */
    public function isAlpha(): bool
    {
        return $this->matchesPattern('^[[:alpha:]]*$');
    }

    /**
     * Returns true if the string contains only alphabetic and numeric chars,
     * false otherwise.
     *
     * @return bool
     *   Whether or not $str contains only alphanumeric chars.
     */
    public function isAlphanumeric(): bool
    {
        return $this->matchesPattern('^[[:alnum:]]*$');
    }

    /**
     * Returns true if the string contains only whitespace chars, false
     * otherwise.
     *
     * @return bool
     *   Whether or not $str contains only whitespace characters.
     */
    public function isBlank(): bool
    {
        return $this->matchesPattern('^[[:space:]]*$');
    }

    /**
     * Returns true if the string contains only hexadecimal chars, false
     * otherwise.
     *
     * @return bool
     *   Whether or not $str contains only hexadecimal chars.
     */
    public function isHexadecimal(): bool
    {
        return $this->matchesPattern('^[[:xdigit:]]*$');
    }

    /**
     * Returns true if the string is JSON, false otherwise. Unlike json_decode
     * in PHP 5.x, this method is consistent with PHP 7 and other JSON parsers,
     * in that an empty string is not considered valid JSON.
     *
     * @return bool
     *   Whether or not $str is JSON.
     */
    public function isJson(): bool
    {
        // @todo This is not needed any more.
        if (!$this->length()) {
            return false;
        }

        // @todo Very likely this will throw user notice or user warning.
        json_decode($this->str);

        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Returns true if the string contains only lower case chars, false
     * otherwise.
     *
     * @return bool
     *   Whether or not $str contains only lower case characters.
     */
    public function isLowerCase(): bool
    {
        return $this->matchesPattern('^[[:lower:]]*$');
    }

    /**
     * Returns true if the string is serialized, false otherwise.
     *
     * @return bool
     *   Whether or not $str is serialized.
     */
    public function isSerialized(): bool
    {
        return $this->str === 'b:0;' || @unserialize($this->str) !== false;
    }


    /**
     * Returns true if the string is base64 encoded, false otherwise.
     *
     * @return bool
     *   Whether or not $str is base64 encoded.
     */
    public function isBase64(): bool
    {
        return (base64_encode(base64_decode($this->str, true)) === $this->str);
    }

    /**
     * Returns true if the string contains only lower case chars, false
     * otherwise.
     *
     * @return bool
     *   Whether or not $str contains only lower case characters.
     */
    public function isUpperCase(): bool
    {
        return $this->matchesPattern('^[[:upper:]]*$');
    }

    /**
     * Returns the last $n characters of the string.
     *
     * @param int $n
     *   Number of characters to retrieve from the end.
     *
     * @return static
     *   Object with its $str being the last $n chars.
     */
    public function last(int $n): static
    {
        $stringy = static::create($this->str, $this->encoding);

        if ($n <= 0) {
            $stringy->str = '';

            return $stringy;
        }

        return $stringy->substr(-$n);
    }

    /**
     * Returns the length of the string. An alias for PHP's mb_strlen() function.
     *
     * @return int
     *   The number of characters in $str given the encoding.
     */
    public function length(): int
    {
        return $this->count();
    }

    /**
     * Splits on newlines and carriage returns, returning an array of Stringy
     * objects corresponding to the lines in the string.
     *
     * @return static[]
     *   An array of Stringy objects.
     */
    public function lines(): array
    {
        if ($this->str === '') {
            return [];
        }

        $lines = $this->split('[\r\n]{1,2}');
        for ($i = 0; $i < count($lines); $i++) {
            $lines[$i] = static::create($lines[$i], $this->encoding);
        }

        return $lines;
    }

    /**
     * Returns the longest common prefix between the string and $otherStr.
     *
     * @param string $otherStr
     *   Second string for comparison.
     *
     * @return static
     *   Object with its $str being the longest common prefix.
     */
    public function longestCommonPrefix(string|\Stringable $otherStr): static
    {
        $other = (string) $otherStr;
        $encoding = $this->encoding;
        $maxLength = min($this->length(), \mb_strlen($other, $encoding));

        $longestCommonPrefix = '';
        for ($i = 0; $i < $maxLength; $i++) {
            $char = \mb_substr($this->str, $i, 1, $encoding);

            if ($char === \mb_substr($other, $i, 1, $encoding)) {
                $longestCommonPrefix .= $char;
            } else {
                break;
            }
        }

        return static::create($longestCommonPrefix, $encoding);
    }

    /**
     * Returns the longest common suffix between the string and $otherStr.
     *
     * @param string $otherStr
     *   Second string for comparison.
     *
     * @return static
     *   Object with its $str being the longest common suffix.
     */
    public function longestCommonSuffix(string|\Stringable $otherStr): static
    {
        $other = (string) $otherStr;
        $encoding = $this->encoding;
        $maxLength = min($this->length(), \mb_strlen((string) $other, $encoding));

        $longestCommonSuffix = '';
        for ($i = 1; $i <= $maxLength; $i++) {
            $char = \mb_substr($this->str, -$i, 1, $encoding);

            if ($char === \mb_substr($other, -$i, 1, $encoding)) {
                $longestCommonSuffix = $char . $longestCommonSuffix;
            } else {
                break;
            }
        }

        return static::create($longestCommonSuffix, $encoding);
    }

    /**
     * Returns the longest common substring between the string and $otherStr.
     * In the case of ties, it returns that which occurs first.
     *
     * @param string $otherStr
     *   Second string for comparison.
     *
     * @return static
     *   Object with its $str being the longest common substring.
     */
    public function longestCommonSubstring(string|\Stringable $otherStr): static
    {
        $other = (string) $otherStr;
        // Uses dynamic programming to solve
        // http://en.wikipedia.org/wiki/Longest_common_substring_problem
        $encoding = $this->encoding;
        $stringy = static::create($this->str, $encoding);
        $strLength = $stringy->length();
        $otherLength = \mb_strlen($other, $encoding);

        // Return if either string is empty
        if ($strLength === 0 || $otherLength === 0) {
            $stringy->str = '';

            return $stringy;
        }

        $len = 0;
        $end = 0;
        $table = array_fill(
            0,
            $strLength + 1,
            array_fill(0, $otherLength + 1, 0),
        );

        for ($i = 1; $i <= $strLength; $i++) {
            for ($j = 1; $j <= $otherLength; $j++) {
                $strChar = \mb_substr($stringy->str, $i - 1, 1, $encoding);
                $otherChar = \mb_substr($other, $j - 1, 1, $encoding);

                if ($strChar === $otherChar) {
                    $table[$i][$j] = $table[$i - 1][$j - 1] + 1;
                    if ($table[$i][$j] > $len) {
                        $len = $table[$i][$j];
                        $end = $i;
                    }
                } else {
                    $table[$i][$j] = 0;
                }
            }
        }

        $stringy->str = \mb_substr($stringy->str, $end - $len, $len, $encoding);

        return $stringy;
    }

    /**
     * Converts the first character of the string to lower case.
     *
     * @return static
     *   Object with the first character of $str being lower case.
     */
    public function lowerCaseFirst(): static
    {
        $first = \mb_substr($this->str, 0, 1, $this->encoding);
        $rest = \mb_substr($this->str, 1, $this->length() - 1, $this->encoding);
        $str = \mb_strtolower($first, $this->encoding) . $rest;

        return static::create($str, $this->encoding);
    }

    /**
     * Pads the string to a given length with $padStr. If length is less than
     * or equal to the length of the string, no padding takes places. The
     * default string used for padding is a space, and the default type (one of
     * 'left', 'right', 'both') is 'right'. Throws an InvalidArgumentException
     * if $padType isn't one of those 3 values.
     *
     * @param int $length
     *   Desired string length after padding.
     * @param string $padStr
     *   String used to pad, defaults to space.
     * @param string $padType
     *   One of 'left', 'right', 'both'.
     *
     * @return static
     *   Object with a padded $str.
     *
     * @throws \InvalidArgumentException
     *   If $padType isn't one of 'right', 'left' or 'both'.
     *
     * @see \STR_PAD_LEFT
     * @see \STR_PAD_RIGHT
     * @see \STR_PAD_BOTH
     */
    public function pad(int $length, string $padStr = ' ', int|string $padType = 'right'): static
    {
        $validPadTypes = [
            'left',
            'right',
            'both',
            \STR_PAD_LEFT,
            \STR_PAD_RIGHT,
            \STR_PAD_BOTH,
        ];
        if (!in_array($padType, $validPadTypes)) {
            throw new \InvalidArgumentException(
                "Pad expects \$padType to be one of: " . implode(', ', $validPadTypes),
            );
        }

        return match ($padType) {
            \STR_PAD_LEFT, 'left' => $this->padLeft($length, $padStr),
            \STR_PAD_RIGHT, 'right' => $this->padRight($length, $padStr),
            default => $this->padBoth($length, $padStr),
        };
    }

    /**
     * Returns a new string of a given length such that both sides of the
     * string are padded. Alias for pad() with a $padType of 'both'.
     *
     * @param int $length
     *   Desired string length after padding.
     * @param string $padStr
     *   String used to pad, defaults to space.
     *
     * @return static
     *   String with padding applied.
     */
    public function padBoth(int $length, string $padStr = ' '): static
    {
        $padding = $length - $this->length();

        return $this->applyPadding(
            (int) floor($padding / 2),
            (int) ceil($padding / 2),
            $padStr,
        );
    }

    /**
     * Returns a new string of a given length such that the beginning of the
     * string is padded. Alias for pad() with a $padType of 'left'.
     *
     * @param int $length
     *   Desired string length after padding.
     * @param string $padStr
     *   String used to pad, defaults to space.
     *
     * @return static
     *   String with left padding.
     */
    public function padLeft(int $length, string $padStr = ' '): static
    {
        return $this->applyPadding($length - $this->length(), 0, $padStr);
    }

    /**
     * Returns a new string of a given length such that the end of the string
     * is padded. Alias for pad() with a $padType of 'right'.
     *
     * @param int $length
     *   Desired string length after padding.
     * @param string $padStr
     *   String used to pad, defaults to space.
     *
     * @return static
     *   String with right padding.
     */
    public function padRight(int $length, string $padStr = ' '): static
    {
        return $this->applyPadding(0, $length - $this->length(), $padStr);
    }

    /**
     * Returns a new string starting with $string.
     *
     * @param string $string
     *   The string to append.
     *
     * @return static
     *   Object with appended $string.
     */
    public function prepend(string $string): static
    {
        return static::create($string . $this->str, $this->encoding);
    }

    /**
     * Replaces all occurrences of $pattern in $str by $replacement. An alias
     * for mb_ereg_replace(). Note that the 'i' option with multibyte patterns
     * in mb_ereg_replace() requires PHP 5.6+ for correct results. This is due
     * to a lack of support in the bundled version of Oniguruma in PHP < 5.6,
     * and current versions of HHVM (3.8 and below).
     *
     * @param string $pattern
     *   The regular expression pattern.
     * @param string $replacement
     *   The string to replace with.
     * @param string $options
     *   Matching conditions to be used.
     *
     * @return static
     *   Object with the resulting $str after the replacements.
     */
    public function regexReplace(
        string|\Stringable $pattern,
        string|\Stringable $replacement,
        string $options = 'msr'
    ): static {
        $this->globalRegexEncodingSave();
        $str = $this->eregReplace($pattern, $replacement, $this->str, $options);
        $this->globalRegexEncodingRestore();

        return static::create($str, $this->encoding);
    }

    /**
     * Returns a new string with the prefix $substring removed, if present.
     *
     * @param string $substring
     *   The prefix to remove.
     *
     * @return static
     *   Object having a $str without the prefix $substring.
     */
    public function removeLeft(string|\Stringable $substring): static
    {
        $stringy = static::create($this->str, $this->encoding);

        if ($stringy->startsWith($substring)) {
            $substringLength = \mb_strlen((string) $substring, $stringy->encoding);

            return $stringy->substr($substringLength);
        }

        return $stringy;
    }

    /**
     * Returns a new string with the suffix $substring removed, if present.
     *
     * @param string $substring
     *   The suffix to remove.
     *
     * @return static
     *   Object having a $str without the suffix $substring.
     */
    public function removeRight(string|\Stringable $substring): static
    {
        $stringy = static::create($this->str, $this->encoding);

        if ($stringy->endsWith($substring)) {
            $substringLength = \mb_strlen((string) $substring, $stringy->encoding);

            return $stringy->substr(0, $stringy->length() - $substringLength);
        }

        return $stringy;
    }

    /**
     * Returns a repeated string given a multiplier. An alias for str_repeat.
     *
     * @param int $multiplier
     *   The number of times to repeat the string.
     *
     * @return static
     *   Object with a repeated str.
     */
    public function repeat(int $multiplier): static
    {
        $repeated = str_repeat($this->str, $multiplier);

        return static::create($repeated, $this->encoding);
    }

    /**
     * Replaces all occurrences of $search in $str by $replacement.
     *
     * @param string $search
     *   The needle to search for.
     * @param string $replacement
     *   The string to replace with.
     *
     * @return static
     *   Object with the resulting $str after the replacements.
     */
    public function replace(
        string|\Stringable $search,
        string|\Stringable $replacement,
    ): static {
        return $this->regexReplace(
            preg_quote((string) $search),
            (string) $replacement,
        );
    }

    /**
     * Returns a reversed string. A multibyte version of strrev().
     *
     * @return static
     *   Object with a reversed $str.
     */
    public function reverse(): static
    {
        $strLength = $this->length();
        $reversed = '';

        // Loop from last index of string to first
        for ($i = $strLength - 1; $i >= 0; $i--) {
            $reversed .= \mb_substr($this->str, $i, 1, $this->encoding);
        }

        return static::create($reversed, $this->encoding);
    }

    /**
     * Truncates the string to a given length, while ensuring that it does not
     * split words. If $substring is provided, and truncating occurs, the
     * string is further truncated so that the substring may be appended without
     * exceeding the desired length.
     *
     * @param int $length
     *   Desired length of the truncated string.
     * @param string $substring
     *   The substring to append if it can fit.
     *
     * @return static
     *   Object with the resulting $str after truncating.
     */
    public function safeTruncate(
        int $length,
        string|\Stringable $substring = '',
    ): static {
        $stringy = static::create($this->str, $this->encoding);
        if ($length >= $stringy->length()) {
            return $stringy;
        }

        // Need to further trim the string so we can append the substring
        $encoding = $stringy->encoding;
        $substringLength = \mb_strlen((string) $substring, $encoding);
        $length = $length - $substringLength;

        $truncated = \mb_substr($stringy->str, 0, $length, $encoding);

        // If the last word was truncated
        if (mb_strpos($stringy->str, ' ', $length - 1, $encoding) != $length) {
            // Find pos of the last occurrence of a space, get up to that
            $lastPos = \mb_strrpos($truncated, ' ', 0, $encoding);
            if ($lastPos !== false) {
                $truncated = \mb_substr($truncated, 0, $lastPos, $encoding);
            }
        }

        $stringy->str = $truncated . $substring;

        return $stringy;
    }

    /**
     * A multibyte str_shuffle() function. It returns a string with its
     * characters in random order.
     *
     * @return static
     *   Object with a shuffled $str.
     */
    public function shuffle(): static
    {
        $indexes = range(0, $this->length() - 1);
        shuffle($indexes);

        $shuffledStr = '';
        foreach ($indexes as $i) {
            $shuffledStr .= \mb_substr($this->str, $i, 1, $this->encoding);
        }

        return static::create($shuffledStr, $this->encoding);
    }

    /**
     * Converts the string into an URL slug. This includes replacing non-ASCII
     * characters with their closest ASCII equivalents, removing remaining
     * non-ASCII and non-alphanumeric characters, and replacing whitespace with
     * $replacement. The replacement defaults to a single dash, and the string
     * is also converted to lowercase. The language of the source string can
     * also be supplied for language-specific transliteration.
     *
     * @param string $replacement
     *   The string used to replace whitespace.
     * @param string $language
     *   Language of the source string.
     *
     * @return static
     *   Object whose $str has been converted to an URL slug.
     */
    public function slugify(string $replacement = '-', string $language = 'en'): static
    {
        $stringy = $this->toAscii($language);

        $stringy->str = str_replace('@', $replacement, (string) $stringy);
        $quotedReplacement = preg_quote($replacement);
        // @todo Default $replacement (-) is already hardcoded into the $pattern.
        $pattern = "/[^a-zA-Z\d\s\-_$quotedReplacement]/u";
        $stringy->str = preg_replace($pattern, '', (string) $stringy);

        return $stringy
            ->toLowerCase()
            ->delimit($replacement)
            ->removeLeft($replacement)
            ->removeRight($replacement);
    }

    /**
     * Returns true if the string begins with $substring, false otherwise. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param string $prefix
     *   The prefix to look for.
     * @param bool $caseSensitive
     *   Whether or not to enforce case-sensitivity.
     *
     * @return bool
     *   Whether or not $str starts with $substring.
     */
    public function startsWith(string|\Stringable $prefix, bool $caseSensitive = true): bool
    {
        $expected = (string) $prefix;
        $expectedLength = \mb_strlen($expected, $this->encoding);
        $actual = \mb_substr(
            $this->str,
            0,
            $expectedLength,
            $this->encoding,
        );

        if (!$caseSensitive) {
            $expected = \mb_strtolower($expected, $this->encoding);
            $actual = \mb_strtolower($actual, $this->encoding);
        }

        return $expected === $actual;
    }

    /**
     * Returns true if the string begins with any of $substrings, false
     * otherwise. By default the comparison is case-sensitive, but can be made
     * insensitive by setting $caseSensitive to false.
     *
     * @param string[] $substrings
     *   Substrings to look for.
     * @param bool $caseSensitive
     *   Whether or not to enforce case-sensitivity.

     * @return bool
     *   Whether or not $str starts with $substring..
     */
    public function startsWithAny(iterable $substrings, bool $caseSensitive = true): bool
    {
        foreach ($substrings as $substring) {
            if ($this->startsWith($substring, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the substring beginning at $start, and up to, but not including
     * the index specified by $end. If $end is omitted, the function extracts
     * the remaining string. If $end is negative, it is computed from the end
     * of the string.
     *
     * @param int $start
     *   Initial index from which to begin extraction.
     * @param int|null $end
     *   Optional index at which to end extraction.
     *
     * @return static
     *   Object with its $str being the extracted substring.
     */
    public function slice(int $start, ?int $end = null): static
    {
        if ($end === null) {
            $length = $this->length();
        } elseif ($end >= 0 && $end <= $start) {
            return static::create('', $this->encoding);
        } elseif ($end < 0) {
            $length = $this->length() + $end - $start;
        } else {
            $length = $end - $start;
        }

        return $this->substr($start, $length);
    }

    /**
     * Splits the string with the provided regular expression, returning an
     * array of Stringy objects. An optional integer $limit will truncate the
     * results.
     *
     * @param string $pattern
     *   The regex with which to split the string.
     * @param int|null $limit
     *   Optional maximum number of results to return.
     *
     * @return static[]
     *   An array of Stringy objects.
     */
    public function split(string|\Stringable $pattern, ?int $limit = null): array
    {
        if ($limit === 0) {
            return [];
        }

        $pattern = (string) $pattern;

        // mb_split errors when supplied an empty pattern in < PHP 5.4.13
        // and HHVM < 3.8
        if ($pattern === '') {
            return [
                static::create($this->str, $this->encoding),
            ];
        }

        $this->globalRegexEncodingSave();
        // mb_split returns the remaining unsplit string in the last index when
        // supplying a limit
        // @todo Make this compatible with \explode().
        $limit = ($limit > 0) ? $limit + 1 : -1;

        $array = \mb_split($pattern, $this->str, $limit);
        $this->globalRegexEncodingRestore();

        if ($limit > 0 && count($array) === $limit) {
            array_pop($array);
        }

        for ($i = 0; $i < count($array); $i++) {
            $array[$i] = static::create($array[$i], $this->encoding);
        }

        return $array;
    }

    /**
     * Strip all whitespace characters. This includes tabs and newline
     * characters, as well as multibyte whitespace such as the thin space
     * and ideographic space.
     *
     * @return static
     *   Object with whitespace stripped.
     */
    public function stripWhitespace(): static
    {
        return $this->regexReplace('[[:space:]]+', '');
    }

    /**
     * Returns the substring beginning at $start with the specified $length.
     * It differs from the mb_substr() function in that providing a $length of
     * null will return the rest of the string, rather than an empty string.
     *
     * @param int $start
     *   Position of the first character to use.
     * @param null|int $length
     *   Maximum number of characters used.
     *
     * @return static
     *   Object with its $str being the substring.
     */
    public function substr(int $start, ?int $length = null): static
    {
        $length = $length === null ? $this->length() : $length;
        $str = \mb_substr($this->str, $start, $length, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Surrounds $str with the given substring.
     *
     * @param string $substring
     *   The substring to add to both sides.
     *
     * @return static
     *   Object whose $str had the substring both prepended and appended.
     */
    public function surround(string|\Stringable $substring): static
    {
        // @todo Why implode()?
        $str = implode('', [(string) $substring, $this->str, (string) $substring]);

        return static::create($str, $this->encoding);
    }

    /**
     * Returns a case swapped version of the string.
     *
     * @return static
     *   Object whose $str has each character's case swapped.
     */
    public function swapCase(): static
    {
        $stringy = static::create($this->str, $this->encoding);
        $encoding = $stringy->encoding;

        $stringy->str = preg_replace_callback(
            '/[\S]/u',
            function ($match) use ($encoding) {
                if ($match[0] === \mb_strtoupper($match[0], $encoding)) {
                    return \mb_strtolower($match[0], $encoding);
                }

                return \mb_strtoupper($match[0], $encoding);
            },
            $stringy->str
        );

        return $stringy;
    }

    /**
     * Returns a string with smart quotes, ellipsis characters, and dashes from
     * Windows-1252 (commonly used in Word documents) replaced by their ASCII
     * equivalents.
     *
     * @return static
     *   Object whose $str has those characters removed.
     */
    public function tidy(): static
    {
        $str = preg_replace([
            '/\x{2026}/u',
            '/[\x{201C}\x{201D}]/u',
            '/[\x{2018}\x{2019}]/u',
            '/[\x{2013}\x{2014}]/u',
        ], [
            '...',
            '"',
            "'",
            '-',
        ], $this->str);

        return static::create($str, $this->encoding);
    }

    /**
     * Returns a trimmed string with the first letter of each word capitalized.
     * Also accepts an array, $ignore, allowing you to list words not to be
     * capitalized.
     *
     * @param array|null $ignore
     *   An array of words not to capitalize.
     *
     * @return static
     *   Object with a titleized $str.
     */
    public function titleize(?array $ignore = null): static
    {
        $stringy = static::create($this->trim(), $this->encoding);
        $encoding = $this->encoding;

        $stringy->str = preg_replace_callback(
            '/([\S]+)/u',
            function ($match) use ($encoding, $ignore) {
                if ($ignore && in_array($match[0], $ignore)) {
                    return $match[0];
                }

                $stringy = new Stringy($match[0], $encoding);

                return (string) $stringy->toLowerCase()->upperCaseFirst();
            },
            $stringy->str
        );

        return $stringy;
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * by default. The language or locale of the source string can be supplied
     * for language-specific transliteration in any of the following formats:
     * en, en_GB, or en-GB. For example, passing "de" results in "äöü" mapping
     * to "aeoeue" rather than "aou" as in other languages.
     *
     * @param string $language
     *   Language of the source string.
     * @param bool $removeUnsupported
     *   Whether or not to remove the unsupported characters.
     *
     * @return static
     *   Object whose $str contains only ASCII characters.
     */
    public function toAscii(string $language = 'en', bool $removeUnsupported = true): static
    {
        $str = $this->str;

        $langSpecific = $this->langSpecificCharsArray($language);
        if (!empty($langSpecific)) {
            $str = str_replace($langSpecific[0], $langSpecific[1], $str);
        }

        foreach ($this->charsArray() as $key => $value) {
            $str = str_replace($value, (string) $key, $str);
        }

        if ($removeUnsupported) {
            $str = preg_replace('/[^\x20-\x7E]/u', '', $str);
        }

        return static::create($str, $this->encoding);
    }

    /**
     * Returns a boolean representation of the given logical string value.
     * For example, 'true', '1', 'on' and 'yes' will return true. 'false', '0',
     * 'off', and 'no' will return false. In all instances, case is ignored.
     * For other numeric strings, their sign will determine the return value.
     * In addition, blank strings consisting of only whitespace will return
     * false. For all other strings, the return value is a result of a
     * boolean cast.
     *
     * @return bool
     *   A boolean value for the string.
     */
    public function toBoolean(): bool
    {
        $key = $this->toLowerCase()->str;
        // @todo This mapping should be exposed/configurable.
        // @todo Enabled/disabled.
        // @todo Visible/hidden.
        $map = [
            'true'  => true,
            '1'     => true,
            'on'    => true,
            'yes'   => true,
            'false' => false,
            '0'     => false,
            'off'   => false,
            'no'    => false
        ];

        if (array_key_exists($key, $map)) {
            return $map[$key];
        }

        if (is_numeric($this->str)) {
            // @todo This is not compatible with PHP's native type conversion.
            return intval($this->str) > 0;
        }

        // @todo Use ::stripWhitespace().
        return (bool) $this->regexReplace('[[:space:]]', '')->str;
    }

    /**
     * Converts all characters in the string to lowercase. An alias for PHP's
     * mb_strtolower().
     *
     * @return static
     *   Object with all characters of $str being lowercase.
     */
    public function toLowerCase(): static
    {
        $str = \mb_strtolower($this->str, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Converts each tab in the string to some number of spaces, as defined by
     * $tabLength. By default, each tab is converted to 4 consecutive spaces.
     *
     * @param int $tabLength
     *   Number of spaces to replace each tab with.
     *
     * @return static
     *   Object whose $str has had tabs switched to spaces.
     */
    public function toSpaces(int $tabLength = 4): static
    {
        $spaces = str_repeat(' ', $tabLength);
        $str = str_replace("\t", $spaces, $this->str);

        return static::create($str, $this->encoding);
    }

    /**
     * Converts each occurrence of some consecutive number of spaces, as
     * defined by $tabLength, to a tab. By default, each 4 consecutive spaces
     * are converted to a tab.
     *
     * @param int $tabLength
     *   Number of spaces to replace with a tab.
     *
     * @return static
     *   Object whose $str has had spaces switched to tabs.
     */
    public function toTabs(int $tabLength = 4): static
    {
        $spaces = str_repeat(' ', $tabLength);
        $str = str_replace($spaces, "\t", $this->str);

        return static::create($str, $this->encoding);
    }

    /**
     * Converts the first character of each word in the string to uppercase.
     *
     * @return static
     *   Object with all characters of $str being title-cased.
     */
    public function toTitleCase(): static
    {
        $str = \mb_convert_case($this->str, \MB_CASE_TITLE, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Converts all characters in the string to uppercase. An alias for PHP's
     * mb_strtoupper().
     *
     * @return static
     *   Object with all characters of $str being uppercase.
     */
    public function toUpperCase(): static
    {
        $str = \mb_strtoupper($this->str, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Returns a string with whitespace removed from the start and end of the
     * string. Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param null|string $chars
     *   Optional string of characters to strip.
     *
     * @return static
     *   Object with a trimmed $str.
     *
     * @todo Default value for $chars argument should be ''.
     */
    public function trim(null|string|\Stringable $chars = null): static
    {
        $charsSafe = $chars === null ? '[:space:]' : preg_quote((string) $chars);
        if ($charsSafe === '') {
            // Nothing to trim.
            return static::create($this->str, $this->encoding);
        }

        return $this->regexReplace("^[$charsSafe]+|[$charsSafe]+\$", '');
    }

    /**
     * Returns a string with whitespace removed from the start of the string.
     * Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param null|string $chars
     *   Optional string of characters to strip.
     *
     * @return static
     *   Object with a trimmed $str.
     */
    public function trimLeft(null|string|\Stringable $chars = null): static
    {
        $charsSafe = $chars === null ? '[:space:]' : preg_quote((string) $chars);
        if ($charsSafe === '') {
            // Nothing to trim.
            return static::create($this->str, $this->encoding);
        }

        return $this->regexReplace("^[$charsSafe]+", '');
    }

    /**
     * Returns a string with whitespace removed from the end of the string.
     * Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param string $chars
     *   Optional string of characters to strip.
     *
     * @return static
     *   Object with a trimmed $str.
     */
    public function trimRight(null|string|\Stringable $chars = null): static
    {
        $charsSafe = $chars === null ? '[:space:]' : preg_quote((string) $chars);
        if ($charsSafe === '') {
            // Nothing to trim.
            return static::create($this->str, $this->encoding);
        }

        return $this->regexReplace("[$charsSafe]+\$", '');
    }

    /**
     * Truncates the string to a given length. If $substring is provided, and
     * truncating occurs, the string is further truncated so that the substring
     * may be appended without exceeding the desired length.
     *
     * @param int $length
     *   Desired length of the truncated string.
     * @param string $substring
     *   The substring to append if it can fit.
     *
     * @return static
     *   Object with the resulting $str after truncating..
     */
    public function truncate(
        int $length,
        string|\Stringable $substring = '',
    ): static {
        $stringy = static::create($this->str, $this->encoding);
        if ($length >= $stringy->length()) {
            return $stringy;
        }

        // Need to further trim the string so we can append the substring
        $substringLength = \mb_strlen((string) $substring, $stringy->encoding);
        $length = $length - $substringLength;

        $truncated = \mb_substr($stringy->str, 0, $length, $stringy->encoding);
        $stringy->str = $truncated . $substring;

        return $stringy;
    }

    /**
     * Returns a lowercase and trimmed string separated by underscores.
     * Underscores are inserted before uppercase characters (with the exception
     * of the first character of the string), and in place of spaces as well as
     * dashes.
     *
     * @return static
     *   Object with an underscored $str.
     */
    public function underscored(): static
    {
        return $this->delimit('_');
    }

    /**
     * Returns an UpperCamelCase version of the supplied string. It trims
     * surrounding spaces, capitalizes letters following digits, spaces, dashes
     * and underscores, and removes spaces, dashes, underscores.
     *
     * @return static
     *   Object with $str in UpperCamelCase.
     */
    public function upperCamelize(): static
    {
        return $this->camelize()->upperCaseFirst();
    }

    /**
     * Converts the first character of the supplied string to upper case.
     *
     * @return static
     *   Object with the first character of $str being upper case.
     */
    public function upperCaseFirst(): static
    {
        $first = \mb_substr($this->str, 0, 1, $this->encoding);
        $rest = \mb_substr($this->str, 1, $this->length() - 1, $this->encoding);

        $str = \mb_strtoupper($first, $this->encoding) . $rest;

        return static::create($str, $this->encoding);
    }

    /**
     * Returns the replacements for the toAscii() method.
     *
     * @return array
     *   An array of replacements.
     */
    protected function charsArray(): array
    {
        // @todo Review why not a ::$foo property.
        static $charsArray;
        if (isset($charsArray)) {
            return $charsArray;
        }

        return $charsArray = [
            '0'     => ['°', '₀', '۰', '０'],
            '1'     => ['¹', '₁', '۱', '１'],
            '2'     => ['²', '₂', '۲', '２'],
            '3'     => ['³', '₃', '۳', '３'],
            '4'     => ['⁴', '₄', '۴', '٤', '４'],
            '5'     => ['⁵', '₅', '۵', '٥', '５'],
            '6'     => ['⁶', '₆', '۶', '٦', '６'],
            '7'     => ['⁷', '₇', '۷', '７'],
            '8'     => ['⁸', '₈', '۸', '８'],
            '9'     => ['⁹', '₉', '۹', '９'],
            'a'     => ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ',
                        'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å',
                        'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ',
                        'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά',
                        'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ', 'အ',
                        'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ا', 'ａ', 'ä'],
            'b'     => ['б', 'β', 'ب', 'ဗ', 'ბ', 'ｂ'],
            'c'     => ['ç', 'ć', 'č', 'ĉ', 'ċ', 'ｃ'],
            'd'     => ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ',
                        'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ', 'ｄ'],
            'e'     => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ',
                        'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ',
                        'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э',
                        'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'إ', 'ئ', 'ｅ'],
            'f'     => ['ф', 'φ', 'ف', 'ƒ', 'ფ', 'ｆ'],
            'g'     => ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ',
                        'ｇ'],
            'h'     => ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ', 'ｈ'],
            'i'     => ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į',
                        'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ',
                        'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ',
                        'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი',
                        'इ', 'ی', 'ｉ'],
            'j'     => ['ĵ', 'ј', 'Ј', 'ჯ', 'ج', 'ｊ'],
            'k'     => ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ',
                        'ک', 'ｋ'],
            'l'     => ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ',
                        'ｌ'],
            'm'     => ['м', 'μ', 'م', 'မ', 'მ', 'ｍ'],
            'n'     => ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န',
                        'ნ', 'ｎ'],
            'o'     => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ',
                        'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő',
                        'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό',
                        'о', 'و', 'θ', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ', 'ｏ',
                        'ö'],
            'p'     => ['п', 'π', 'ပ', 'პ', 'پ', 'ｐ'],
            'q'     => ['ყ', 'ｑ'],
            'r'     => ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ', 'ｒ'],
            's'     => ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ',
                        'ſ', 'ს', 'ｓ'],
            't'     => ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ',
                        'თ', 'ტ', 'ｔ'],
            'u'     => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ',
                        'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ',
                        'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ', 'ｕ',
                        'ў', 'ü'],
            'v'     => ['в', 'ვ', 'ϐ', 'ｖ'],
            'w'     => ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ', 'ｗ'],
            'x'     => ['χ', 'ξ', 'ｘ'],
            'y'     => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ',
                        'ϋ', 'ύ', 'ΰ', 'ي', 'ယ', 'ｙ'],
            'z'     => ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ', 'ｚ'],
            'aa'    => ['ع', 'आ', 'آ'],
            'ae'    => ['æ', 'ǽ'],
            'ai'    => ['ऐ'],
            'ch'    => ['ч', 'ჩ', 'ჭ', 'چ'],
            'dj'    => ['ђ', 'đ'],
            'dz'    => ['џ', 'ძ'],
            'ei'    => ['ऍ'],
            'gh'    => ['غ', 'ღ'],
            'ii'    => ['ई'],
            'ij'    => ['ĳ'],
            'kh'    => ['х', 'خ', 'ხ'],
            'lj'    => ['љ'],
            'nj'    => ['њ'],
            'oe'    => ['œ', 'ؤ'],
            'oi'    => ['ऑ'],
            'oii'   => ['ऒ'],
            'ps'    => ['ψ'],
            'sh'    => ['ш', 'შ', 'ش'],
            'shch'  => ['щ'],
            'ss'    => ['ß'],
            'sx'    => ['ŝ'],
            'th'    => ['þ', 'ϑ', 'ث', 'ذ', 'ظ'],
            'ts'    => ['ц', 'ც', 'წ'],
            'uu'    => ['ऊ'],
            'ya'    => ['я'],
            'yu'    => ['ю'],
            'zh'    => ['ж', 'ჟ', 'ژ'],
            '(c)'   => ['©'],
            'A'     => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ',
                        'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą',
                        'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ',
                        'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ',
                        'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ', 'Ａ', 'Ä'],
            'B'     => ['Б', 'Β', 'ब', 'Ｂ'],
            'C'     => ['Ç','Ć', 'Č', 'Ĉ', 'Ċ', 'Ｃ'],
            'D'     => ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ',
                        'Ｄ'],
            'E'     => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ',
                        'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ',
                        'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э',
                        'Є', 'Ə', 'Ｅ'],
            'F'     => ['Ф', 'Φ', 'Ｆ'],
            'G'     => ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ', 'Ｇ'],
            'H'     => ['Η', 'Ή', 'Ħ', 'Ｈ'],
            'I'     => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į',
                        'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ',
                        'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ',
                        'Ｉ'],
            'J'     => ['Ｊ'],
            'K'     => ['К', 'Κ', 'Ｋ'],
            'L'     => ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल', 'Ｌ'],
            'M'     => ['М', 'Μ', 'Ｍ'],
            'N'     => ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν', 'Ｎ'],
            'O'     => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ',
                        'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő',
                        'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ',
                        'Ό', 'О', 'Θ', 'Ө', 'Ǒ', 'Ǿ', 'Ｏ', 'Ö'],
            'P'     => ['П', 'Π', 'Ｐ'],
            'Q'     => ['Ｑ'],
            'R'     => ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ', 'Ｒ'],
            'S'     => ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ', 'Ｓ'],
            'T'     => ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ', 'Ｔ'],
            'U'     => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ',
                        'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ',
                        'Ǘ', 'Ǚ', 'Ǜ', 'Ｕ', 'Ў', 'Ü'],
            'V'     => ['В', 'Ｖ'],
            'W'     => ['Ω', 'Ώ', 'Ŵ', 'Ｗ'],
            'X'     => ['Χ', 'Ξ', 'Ｘ'],
            'Y'     => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ',
                        'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ', 'Ｙ'],
            'Z'     => ['Ź', 'Ž', 'Ż', 'З', 'Ζ', 'Ｚ'],
            'AE'    => ['Æ', 'Ǽ'],
            'Ch'    => ['Ч'],
            'Dj'    => ['Ђ'],
            'Dz'    => ['Џ'],
            'Gx'    => ['Ĝ'],
            'Hx'    => ['Ĥ'],
            'Ij'    => ['Ĳ'],
            'Jx'    => ['Ĵ'],
            'Kh'    => ['Х'],
            'Lj'    => ['Љ'],
            'Nj'    => ['Њ'],
            'Oe'    => ['Œ'],
            'Ps'    => ['Ψ'],
            'Sh'    => ['Ш'],
            'Shch'  => ['Щ'],
            'Ss'    => ['ẞ'],
            'Th'    => ['Þ'],
            'Ts'    => ['Ц'],
            'Ya'    => ['Я'],
            'Yu'    => ['Ю'],
            'Zh'    => ['Ж'],
            ' '     => ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81",
                        "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84",
                        "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87",
                        "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A",
                        "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80",
                        "\xEF\xBE\xA0"],
        ];
    }

    /**
     * Returns language-specific replacements for the toAscii() method.
     * For example, German will map 'ä' to 'ae', while other languages
     * will simply return 'a'.
     *
     * @param string $language
     *   Language of the source string
     *
     * @return array
     *   An array of replacements.
     */
    protected static function langSpecificCharsArray(string $language = 'en'): array
    {
        $split = preg_split('/[-_]/', $language);
        $language = strtolower($split[0]);

        static $charsArray = [];
        if (isset($charsArray[$language])) {
            return $charsArray[$language];
        }

        $languageSpecific = [
            'de' => [
                ['ä',  'ö',  'ü',  'Ä',  'Ö',  'Ü' ],
                ['ae', 'oe', 'ue', 'AE', 'OE', 'UE'],
            ],
            'bg' => [
                ['х', 'Х', 'щ', 'Щ', 'ъ', 'Ъ', 'ь', 'Ь'],
                ['h', 'H', 'sht', 'SHT', 'a', 'А', 'y', 'Y']
            ]
        ];

        // @todo Use ??.
        if (isset($languageSpecific[$language])) {
            $charsArray[$language] = $languageSpecific[$language];
        } else {
            $charsArray[$language] = [];
        }

        return $charsArray[$language];
    }

    /**
     * Adds the specified amount of left and right padding to the given string.
     * The default character used is a space.
     *
     * @param int $left
     *   Length of left padding.
     * @param int $right
     *   Length of right padding.
     * @param string $padStr
     *   String used to pad.
     *
     * @return static
     *   String with padding applied.
     */
    protected function applyPadding(int $left = 0, int $right = 0, string $padStr = ' '): static
    {
        $stringy = static::create($this->str, $this->encoding);
        $length = \mb_strlen($padStr, $stringy->encoding);

        $strLength = $stringy->length();
        $paddedLength = $strLength + $left + $right;

        if (!$length || $paddedLength <= $strLength) {
            return $stringy;
        }

        $leftPadding = \mb_substr(
            str_repeat($padStr, (int) ceil($left / $length)),
            0,
            $left,
            $stringy->encoding,
        );
        $rightPadding = \mb_substr(
            str_repeat($padStr, (int) ceil($right / $length)),
            0,
            $right,
            $stringy->encoding,
        );

        $stringy->str = $leftPadding . $stringy->str . $rightPadding;

        return $stringy;
    }

    /**
     * Returns true if $str matches the supplied pattern, false otherwise.
     *
     * @param string $pattern
     *   Regex pattern to match against.
     *
     * @return bool
     *   Whether or not $str matches the pattern.
     */
    protected function matchesPattern(string $pattern): bool
    {
        $this->globalRegexEncodingSave();
        $match = \mb_ereg_match($pattern, $this->str);
        $this->globalRegexEncodingRestore();

        return $match;
    }

    /**
     * Alias for mb_ereg_replace with a fallback to preg_replace if the
     * mbstring module is not installed.
     */
    protected function eregReplace(
        string|\Stringable $pattern,
        string|\Stringable $replacement,
        string|\Stringable $string,
        string $option = 'msr',
    ) {
        return \mb_ereg_replace(
            (string) $pattern,
            (string) $replacement,
            (string) $string,
            $option,
        );
    }

    protected function globalRegexEncodingSave()
    {
        $this->globalRegexEncoding = mb_regex_encoding();
        mb_regex_encoding($this->encoding);
    }

    protected function globalRegexEncodingRestore()
    {
        mb_regex_encoding($this->globalRegexEncoding);
    }
}
