# Loco

Loco is a parsing library for PHP.

Loco uses single-valued parsers called `MonoParser`s. A conventional, "enthusiastic" parser returns a set of possible results, which is empty if parsing is not possible. A "lazy" parser returns one possible result on the first call, and then returns further results with each subsequent call until no more are possible. In contrast, `MonoParser`s simply return a single result or failure. This in turn **makes backtracking impossible**, which has two effects:

* it prevents parsing time from becoming exponential, and
* it reduces expressive power to only certain **unambiguous** context-free grammars.

Loco directly parses strings, requiring no intermediate lexing step.

Loco detects infinite loops (e.g. `(|a)*`) and [left recursion](http://en.wikipedia.org/wiki/Left_recursion) (e.g. `S -> Sa`) at grammar creation time.

## API

Loco exports the following parser classes, all in the `Ferno\Loco` namespace.

### Ferno\Loco\MonoParser

Abstract base class from which all parsers inherit. "Mono" means the parser returns one result, or fails.

`Ferno\Loco\MonoParser` has one important method, `match($string, $i = 0)`, which either returns the successful match in the form of an `array("j" => 9, "value" => "something")`, or throws a `Ferno\Loco\ParseFailureException`.

There is also the more useful method `parse($string)`, which either returns the parsed value `"something"` or throws a `Ferno\Loco\ParseFailureException` if the match fails or doesn't occupy the entire length of the supplied string.

### Ferno\Loco\EmptyParser

Finds the empty string (and always succeeds). Callback is passed no arguments. Default callback returns `null`.

```php
new Ferno\Loco\EmptyParser();
// returns null

new Ferno\Loco\EmptyParser(
    function() { return array(); }
);
// return an empty array instead
```

### Ferno\Loco\StringParser

Finds a static string. Callback is passed one argument, the string which was matched. Yes, that's effectively the same function call each time. Default callback returns the first argument i.e. the string.

```php
new Ferno\Loco\StringParser("name");
// returns "name"

new Ferno\Loco\StringParser(
    "name",
    function($string) { return strrev($string); }
);
// returns "eman"
```

### Ferno\Loco\RegexParser

Matches a regular expression. The regular expression must be anchored at the beginning of the substring supplied to match, using `^`. Otherwise, there's no way to stop PHP from matching elsewhere entirely in the expression, which is very bad. Caution: formations like `/^a|b/` only anchor the `"a"` at the start of the string; a `"b"` might be matched anywhere! You should use `/^(a|b)/` or `/^a|^b/`.

Callback is passed one argument for each sub-match. For example, if the regex is `/^ab(cd(ef)gh)ij/` then the first argument is the whole match, `"abcdefghij"`, the second argument is `"cdefgh"` and the third argument is `"ef"`. The default callback returns only the first argument, the whole match.

```php
new Ferno\Loco\RegexParser("/^'([a-zA-Z_][a-zA-Z_0-9]*)'/");
// returns the full match including the single quotes

new Ferno\Loco\RegexParser(
    "/^'([a-zA-Z_][a-zA-Z_0-9]*)'/",
    function($match0, $match1) { return $match1; }
);
// discard the single quotes and returns only the inner string
```

### Ferno\Loco\Utf8Parser

Matches a single UTF-8 character. You can optionally supply an exclusion list of characters which will *not* be matched.

```php
new Ferno\Loco\Utf8Parser(array("<", ">", "&"));
// any UTF-8 character except the three listed
```

Callback is passed one argument, the string that was matched. The default callback returns the first argument *i.e.* the string.

For best results, alternate (see `Ferno\Loco\LazyAltParser` below) with `Ferno\Loco\StringParsers` for e.g. `"&lt;"`, `"&gt;"`, `"&amp;"` and other HTML character entities.

### Ferno\Loco\LazyAltParser

This encapsulates the "alternation" parser combinator by alternating between several internal parsers. The key word here is "lazy". As soon as one of them matches, that result is returned, and that's the end of the story. There is no capability to merge the results from several of the internal parsers, and there is no capability for returning (backtracking) to this parser and trying to retrieve other results if the first one turns out to be bogus.

Callback is passed one argument, the sole successful internal match. The default callback returns the first argument directly.

```php
new Ferno\Loco\LazyAltParser(
    array(
        new Ferno\Loco\StringParser("foo"),
        new Ferno\Loco\StringParser("bar")
    )
);
// returns either "foo" or "bar"
```

### Ferno\Loco\ConcParser

This encapsulates the "concatenation" parser combinator by concatenating a finite sequence of internal parsers. If the sequence is empty, this is equivalent to `Ferno\Loco\EmptyParser`, above.

Callback is passed one argument for every internal parser, each argument containing the result from that parser. For example, `new Ferno\Loco\ConcParser(array($a, $b, $c), $callback)` will pass three arguments to its callback. The first contains the result from parser `$a`, the second the result from parser `$b` and the third the result from parser `$c`. The default callback returns the arguments in the form of an array: `return func_get_args();`.

```php
new Ferno\Loco\ConcParser(
    array(
        new Ferno\Loco\RegexParser("/^<([a-zA-Z_][a-zA-Z_0-9]*)>/", function($match0, $match1) { return $match1; }),
        new Ferno\Loco\StringParser(", "),
        new Ferno\Loco\RegexParser("/^<(\d\d\d\d-\d\d-\d\d)>/",     function($match0, $match1) { return $match1; }),
        new Ferno\Loco\StringParser(", "),
        new Ferno\Loco\RegexParser("/^<([A-Z]{2}[0-9]{7})>/",       function($match0, $match1) { return $match1; }),
    ),
    function($name, $comma1, $opendate, $comma2, $ref) { return new Account($accountname, $opendate, $ref); }
);
// match something like "<Williams>, <2011-06-30>, <GH7784939>"
// return new Account("Williams", "2011-06-30", "GH7784939")
```

### Ferno\Loco\GreedyMultiParser

This encapsulates the "Kleene star closure" parser combinator to match single internal parser multiple (finitely or infinitely many) times. With a finite upper bound, this is more or less equivalent to `Ferno\Loco\ConcParser`, above. With an infinite upper bound, this gets more interesting. `Ferno\Loco\GreedyMultiParser`, as the name hints, will match as many times as it can before returning. There is no option for returning multiple matches simultaneously; only the largest match is returned. And there is no option for backtracking and trying to consume more or fewer instances.

Callback is passed one argument for every match. For example, `new Ferno\Loco\GreedyMultiParser($a, 2, 4, $callback)` could pass 2, 3 or 4 arguments to its callback. `new GreedyMultiParser($a, 0, null, $callback)` has an unlimited upper bound and could pass an unlimited number of arguments to its callback. (PHP seems to have no problem with this.) The default callback returns all of the arguments in the form of an array: `return func_get_args();`.

Remember that a PHP function can be defined as `function(){...}` and still accept an arbitrary number of arguments.

```php
new Ferno\Loco\GreedyMultiParser(
    new Ferno\Loco\LazyAltParser(
        array(
            new Ferno\Loco\Utf8Parser(array("<", ">", "&")),                         // match any UTF-8 character except <, > or &
            new Ferno\Loco\StringParser("&lt;",  function($string) { return "<"; }), // ...or an escaped < (unescape it)
            new Ferno\Loco\StringParser("&gt;",  function($string) { return ">"; }), // ...or an escaped > (unescape it)
            new Ferno\Loco\StringParser("&amp;", function($string) { return "&"; })  // ...or an escaped & (unescape it)
        )
    ),
    0,                                                  // at least 0 times
    null,                                               // at most infinitely many times
    function() { return implode("", func_get_args()); } // concatenate all of the matched characters together
);
// matches a continuous string of valid, UTF-8 encoded HTML text
// returns the unescaped string
```

### Ferno\Loco\Grammar

All of the above is well and good, but it doesn't complete the picture. Firstly, it makes our parsers quite large and confusing to read when they nest too much. Secondly, it makes recursion very difficult; a parser cannot easily be placed inside itself, for example. Without recursion, all we can parse is regular languages, not context-free languages.

The `Ferno\Loco\Grammar` class makes this very easy. At its heart, `Ferno\Loco\Grammar` is just another `Ferno\Loco\MonoParser`. But `Ferno\Loco\Grammar` accepts an associative array of parsers as input -- meaning each one comes attached to a name. The parsers inside it, meanwhile, can refer to other parsers by name instead of containing them directly. `Ferno\Loco\Grammar` resolves these references at instantiation time, as well as detecting anomalies like left recursion, names which refer to parsers which don't exist, dangerous formations such as new `Ferno\Loco\GreedyMultiParser(new Ferno\Loco\EmptyParser(), 0, null)`, and so on.

Here's a simple `Ferno\Loco\Grammar` which can recognise (some) valid HTML paragraphs and return the text content of those paragraphs:

```php
$p = new Ferno\Loco\Grammar(
    "paragraph",
    array(
        "paragraph" => new Ferno\Loco\ConcParser(
            array(
                "OPEN_P",
                "CONTENT",
                "CLOSE_P"
            ),
            function($open_p, $content, $close_p) {
                return $content;
            }
        ),

        "OPEN_P" => new Ferno\Loco\StringParser("<p>"),

        "CONTENT" => new Ferno\Loco\GreedyMultiParser(
            "UTF-8 CHAR",
            0,
            null,
            function() { return implode("", func_get_args()); }
        ),

        "CLOSE_P" => new Ferno\Loco\StringParser("</p>"),

        "UTF-8 CHAR" => new Ferno\Loco\LazyAltParser(
            array(
                new Ferno\Loco\Utf8Parser(array("<", ">", "&")),                         // match any UTF-8 character except <, > or &
                new Ferno\Loco\StringParser("&lt;",  function($string) { return "<"; }), // ...or an escaped < (unescape it)
                new Ferno\Loco\StringParser("&gt;",  function($string) { return ">"; }), // ...or an escaped > (unescape it)
                new Ferno\Loco\StringParser("&amp;", function($string) { return "&"; })  // ...or an escaped & (unescape it)
            )
        ),
    )
);

$p->parse("<p>Your text here &amp; here &amp; &lt;here&gt;</p>");
// returns "Your text here & here & <here>"
```

## Examples

Loco also comes with [a collection of public domain examples](examples).

## Development

Assuming you have PHP 7.4 or greater installed, and Composer, run:

```sh
composer install
```

to install all dependencies, then

```sh
composer run-script test
```

to run the test scripts, including linting.

## About Loco

I created Loco because I wanted to let people use XHTML comments on my website, and I wanted to be able validate that XHTML in a flexible way, starting with a narrow subset of XHTML and adding support for more tags over time. I believed that writing a parsing library would be more effective and educational than hand-writing (and then constantly hand-rewriting) a parser.

Loco, together with the Loco grammar [examples/simpleComment.php](https://github.com/ferno/loco/blob/master/examples/simpleComment.php), fulfilled the first objective. These were kept in successful use for several years. Later, I developed [examples/locoNotation.php](https://github.com/ferno/loco/blob/master/examples/locoNotation.php) which things even simpler for me. However, there were some drawbacks:

* Grammars had to be instantiated every time the comment-submission PHP script ran, which was laborious and inelegant. PHP doesn't make it possible to retrieve the text content of a callback, so the process of turning Loco from a parser *library* into a true parser *generator* stalled.
* Lack of backtracking meant I had to be inordinately careful in describing my CFG so that it would be unambiguous and work correctly. This need for extra effort kind of defeated the point.
* As I now realise, one of the most important things to consider when parsing user input is generating meaningful error messages when parsing failed. Loco is sort of bad at this, and users found it difficult to create correct HTML to keep it pleased.
* I severely dislike working with PHP.

Before beginning the project, I also observed that PHP had no parser combinator library, and I decided to fill this niche. Again, I ran into some problems:

* I didn't actually know what the term "parser combinator" meant at the time. It is not "a parser made up from a combination of other parsers". It is "a function or operator which accepts one or more parsers as input and returns a new parser as output". You can see the term being misused several times above. There is still no parser combinator library for PHP, to my knowledge.
* I knew, and still know, barely anything about parsing in general.
* I severely dislike working with PHP.

Overall I would say that this project fulfilled my needs at the time, and if it fulfills yours now that is probably just a coincidence. I would exercise caution when using Loco, or inspecting its code. At the time of writing, comments on my website allow strict plain text only, with no HTML or any other kind of formatting.
