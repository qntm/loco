# Loco

Loco is a parsing library for PHP.

Loco uses single-valued parsers called `MonoParser`s. A conventional, "enthusiastic" parser returns a set of possible results, 
which is empty if parsing is not possible. A "lazy" parser returns one possible result on the first call, and then returns further 
results with each subsequent call until no more are possible. In contrast, `MonoParser`s simply return a single result or failure. 
This in turn **makes backtracking impossible**, which has two effects:

* it reduces expressive power to only certain **unambiguous** context-free grammars
* it prevents parsing time from becoming exponential.

Loco directly to parses strings, requiring no intermediate lexing step.

Loco detects infinite loops (e.g. `(|a)*`) and [left recursion](http://en.wikipedia.org/wiki/Left_recursion) (e.g. `A -> Aa`) at 
grammar creation time.


## Parsers in Loco

### `MonoParser`

Abstract base class from which all parsers inherit. Can't be instantiated. "Mono" means the parser returns one result, or fails.

`Ferno\Loco\MonoParser` has one important method, `match($string, $i = 0)`, which either returns the successful match in the form 
of an `array("j" => 9, "value" => "something")`, or throws a `Ferno\Loco\ParseFailureException`.

There is also the more useful method `parse($string)`, which either returns the parsed value `"something"` or throws a 
`Ferno\Loco\ParseFailureException` if the match fails or doesn't occupy the entire length of the supplied string.




### `EmptyParser`

Finds the empty string (and always succeeds). Callback is passed no arguments. Default callback returns `null`.

    new Ferno\Loco\EmptyParser();
    // returns null

    new Ferno\Loco\EmptyParser(
      function() { return array(); }
    );
    // return an empty array instead


### `StringParser`

Finds a static string. Callback is passed one argument, the string that was matched. Yes, that's effectively the same function 
call each time. Default callback returns the first argument i.e. the string.

    new Ferno\Loco\StringParser("name");
    // returns "name"

    new Ferno\Loco\StringParser(
      "name",
      function($string) { return strrev($string); }
    );
    // returns "eman"





### `RegexParser`

Matches a regular expression. The regular expression must be anchored at the beginning of the substring supplied to match, using 
`^`. Otherwise, there's no way to stop PHP from matching elsewhere entirely in the expression, which is very bad. Caution: 
formations like `/^a|b/` only anchor the `"a"` at the start of the string; a `"b"` might be matched anywhere! You should use 
`/^(a|b)/` or `/^a|^b/`.

Callback is passed one argument for each sub-match. For example, if the regex is `/^ab(cd(ef)gh)ij/` then the first argument is 
the whole match, `"abcdefghij"`, the second argument is `"cdefgh"` and the third argument is `"ef"`. The default callback returns 
only the first argument, the whole match.

    new Ferno\Loco\RegexParser("/^'([a-zA-Z_][a-zA-Z_0-9]*)'/");
    // returns the full match including the single quotes
  
    new Ferno\Loco\RegexParser(
      "/^'([a-zA-Z_][a-zA-Z_0-9]*)'/",
      function($match0, $match1) { return $match1; }
    );
    // discard the single quotes and returns only the inner string



### `Utf8Parser`

Matches a single UTF-8 character. You can optionally supply a blacklist of characters which will *not* be matched.

    new Ferno\Loco\Utf8Parser(array("<", ">", "&"));
    // any UTF-8 character except the three listed

Callback is passed one argument, the string that was matched. The default callback returns the first argument i.e. the string.

For best results, alternate (see `Ferno\Loco\LazyAltParser` below) with `Ferno\Loco\StringParsers` for e.g. `"&lt;"`, `"&gt;"`, 
`"&amp;"` and other HTML character entities.



### `LazyAltParser`

This encapsulates the "alternation" parser combinator by alternating between several internal parsers. The key word here is 
"lazy". As soon as one of them matches, that result is returned, and that's the end of the story. There is no capability to merge 
the results from several of the internal parsers, and there is no capability for returning (backtracking) to this parser and 
trying to retrieve other results if the first one turns out to be bogus.

Callback is passed one argument, the sole successful internal match. The default callback returns the first argument directly.

    new Ferno\Loco\LazyAltParser(
      array(
        new Ferno\Loco\StringParser("foo"),
        new Ferno\Loco\StringParser("bar")
      )
    );
    // returns either "foo" or "bar"




### `ConcParser`

This encapsulates the "concatenation" parser combinator by concatenating a finite sequence of internal parsers. If the sequence is 
empty, this is equivalent to `Ferno\Loco\EmptyParser`, above.

Callback is passed one argument for every internal parser, each argument containing the result from that parser. For example, 
`new Ferno\Loco\ConcParser(array($a, $b, $c), $callback)` will pass three arguments to its callback. The first contains the result from 
parser `$a`, the second the result from parser `$b` and the third the result from parser `$c`. The default callback returns the 
arguments in the form of an array: `return func_get_args();`.

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


### `GreedyMultiParser`

This encapsulates the "Kleene star closure" parser combinator to match single internal parser multiple (finitely or infinitely 
many) times. With a finite upper bound, this is more or less equivalent to `Ferno\Loco\ConcParser`, above. With an infinite upper bound, this 
gets more interesting. `Ferno\Loco\GreedyMultiParser`, as the name hints, will match as many times as it can before returning. 
There is no option for returning multiple matches simultaneously; only the largest match is returned. And there is no option for 
backtracking and trying to consume more or fewer instances.

Callback is passed one argument for every match. For example, `new Ferno\Loco\GreedyMultiParser($a, 2, 4, $callback)` could pass 
2, 3 or 4 arguments to its callback. `new GreedyMultiParser($a, 0, null, $callback)` has an unlimited upper bound and could pass 
an unlimited number of arguments to its callback. (PHP seems to have no problem with this.) The default callback returns all of 
the arguments in the form of an array: `return func_get_args();`.

Remember that a PHP function can be defined as `function(){...}` and still accept an arbitrary number of arguments.

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


### `Grammar`

All of the above is well and good, but it doesn't complete the picture. Firstly, it makes our parsers quite large and confusing to 
read when they nest too much. Secondly, it makes recursion very difficult; a parser cannot easily be placed inside itself, for 
example. Without recursion, all we can parse is regular languages, not context-free languages.

The `Ferno\Loco\Grammar` class makes this very easy. At its heart, `Ferno\Loco\Grammar` is just another `Ferno\Loco\MonoParser`. 
But `Ferno\Loco\Grammar` accepts an associative array of parsers as input -- meaning each one comes attached to a name. The 
parsers inside it, meanwhile, can refer to other parsers by name instead of containing them directly. `Ferno\Loco\Grammar` 
resolves these references at instantiation time, as well as detecting anomalies like left recursion, names which refer to parsers 
which don't exist, dangerous formations such as new `Ferno\Loco\GreedyMultiParser(new Ferno\Loco\EmptyParser(), 0, null)`, and so 
on.

Here's a simple `Ferno\Loco\Grammar` which can recognise (some) valid HTML paragraphs and return the text content of those paragraphs:

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








## Examples

Loco also comes with a collection of public domain examples:

### [examples/json.php](https://github.com/ferno/loco/blob/master/examples/json.php)

Parse [JSON](http://json.org/) expressions and returns PHP arrays.

### [examples/regEx.php](https://github.com/ferno/loco/blob/master/examples/regEx.php)

Parse simple regular expressions and return PHP objects representing them.

### [examples/simpleComment.php](https://github.com/ferno/loco/blob/master/examples/simpleComment.php)

Recognise simple valid HTML text using `<h5>`, `<p>`, `<em>` and `<strong>`, with balanced tags and escaped entities.

### [examples/bnf.php](https://github.com/ferno/loco/blob/master/examples/bnf.php)

Defines `$bnfGrammar`, which parses a grammar presented in [Backus-Naur Form](http://en.wikipedia.org/wiki/Backus%E2%80%93Naur_Form) 
and returns `Ferno\Loco\Grammar` object capable of recognising that grammar.

BNF is generally pretty low-tech and lacks a lot of features.

#### Sample grammar in Backus-Naur Form

This appears on Wikipedia. This is a pretty clunky example because it doesn't handle whitespace and doesn't define a whole lot of 
variables which I had to do myself. However, it gets the point across.

    <postal-address> ::= <name-part> <street-address> <zip-part>
    <name-part>      ::= <personal-part> <name-part> | <personal-part> <last-name> <opt-jr-part> <EOL>
    <personal-part>  ::= <initial> "." | <first-name>
    <street-address> ::= <house-num> <street-name> <opt-apt-num> <EOL>
    <zip-part>       ::= <town-name> "," <state-code> <ZIP-code> <EOL>
    <opt-jr-part>    ::= "Sr." | "Jr." | <roman-numeral> | ""

    <last-name>     ::= 'MacLaurin '
    <EOL>           ::= '\n'
    <initial>       ::= 'b'
    <first-name>    ::= 'Steve '
    <house-num>     ::= '173 '
    <street-name>   ::= 'Acacia Avenue '
    <opt-apt-num>   ::= '7A'
    <town-name>     ::= 'Stevenage'
    <state-code>    ::= ' KY '
    <ZIP-code>      ::= '33445'
    <roman-numeral> ::= 'g'

#### String in the sample grammar

Steve MacLaurin \n173 Acacia Avenue 7A\nStevenage, KY 33445\n

### [examples/wirth.php](https://github.com/ferno/loco/blob/master/examples/wirth.php)

Defines `$wirthGrammar`, which parses a grammar presented in [Wirth syntax notation](http://en.wikipedia.org/wiki/Wirth_syntax_notation) 
and returns a `Ferno\Loco\Grammar` object capable of recognising that grammar.

Wirth syntax notation is okay, but I don't like the use of `.` (which in my mind usually means "any character" (when used in a 
regex), or the string concatenation operator) as a line ending (which I usually think of as a semicolon or an actual `\n`). I also 
dislike the use of square brackets for optional terms, and braces for Kleene star closure. Neither of these are unambiguous enough 
in their meaning.

#### Sample grammar in Wirth syntax notation

    SYNTAX     = { PRODUCTION } .
    PRODUCTION = IDENTIFIER "=" EXPRESSION "." .
    EXPRESSION = TERM { "|" TERM } .
    TERM       = FACTOR { FACTOR } .
    FACTOR     = IDENTIFIER
               | LITERAL
               | "[" EXPRESSION "]"
               | "(" EXPRESSION ")"
               | "{" EXPRESSION "}" .
    IDENTIFIER = letter { letter } .
    LITERAL    = """" character { character } """" .
    digit      = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" .
    upper      = "A" | "B" | "C" | "D" | "E" | "F" | "G" | "H" | "I" | "J" 
               | "K" | "L" | "M" | "N" | "O" | "P" | "Q" | "R" | "S" | "T" 
               | "U" | "V" | "W" | "X" | "Y" | "Z" .
    lower      = "a" | "b" | "c" | "d" | "e" | "f" | "g" | "h" | "i" | "j" 
               | "k" | "l" | "m" | "n" | "o" | "p" | "q" | "r" | "s" | "t" 
               | "u" | "v" | "w" | "x" | "y" | "z" .
    letter     = upper | lower .
    character  = letter | digit | "=" | "." | """""" .

This example grammar happens to be the grammar which describes Wirth syntax notation itself - if it had all whitespace removed from it. Observe:

#### String in the sample grammar

    SYNTAX={PRODUCTION}.


---

### [examples/ebnf.php](https://github.com/ferno/loco/blob/master/examples/ebnf.php)
Defines `$ebnfGrammar`, which parses a grammar presented in [Extended Backus-Naur Form](http://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_Form) and returns a `Grammar` object capable of recognising that grammar.

This is a big improvement on vanilla BNF (comments are a must!) but the need for commas between tokens is irritating and again, braces and square brackets aren't ideal in my mind.

`$ebnfGrammar` can't handle "specials" (strings contained between two question marks), since these have no clear definition. It also can't handle "exceptions" (when a `-` is used to discard certain possibilities), because these are not permissible in context-free grammars or possible with naive `Ferno\Loco\MonoParser`s, and so would require special modification to Loco to handle.

#### Sample grammar in Extended Backus-Naur Form

    (* a simple program syntax in EBNF - Wikipedia *)
    program = 'PROGRAM' , white space , identifier , white space ,
               'BEGIN' , white space ,
               { assignment , ";" , white space } ,
               'END.' ;
    identifier = alphabetic character , { alphabetic character | digit } ;
    number = [ "-" ] , digit , { digit } ;
    string = '"' , { all characters } , '"' ;
    assignment = identifier , ":=" , ( number | identifier | string ) ;
    alphabetic character = "A" | "B" | "C" | "D" | "E" | "F" | "G"
                         | "H" | "I" | "J" | "K" | "L" | "M" | "N"
                         | "O" | "P" | "Q" | "R" | "S" | "T" | "U"
                         | "V" | "W" | "X" | "Y" | "Z" ;
    digit = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;
    white space = ( " " | "\n" ) , { " " | "\n" } ;
    all characters = "H" | "e" | "l" | "o" | " " | "w" | "r" | "d" | "!" ;

#### String in the sample grammar

    PROGRAM DEMO1
    BEGIN
      A0:=3;
      B:=45;
      H:=-100023;
      C:=A;
      D123:=B34A;
      BABOON:=GIRAFFE;
      TEXT:=\"Hello world!\";
    END."

---

### [examples/locoNotation.php](https://github.com/ferno/loco/blob/master/examples/locoNotation.php)

Defines `$locoGrammar`, which parses a grammar presented in "Loco notation" and returns a `Ferno\Loco\Grammar` object capable of parsing that grammar.

"Loco notation" (for lack of a better name) is an extension of Backus-Naur Form which gives access to all the `Ferno\Loco\MonoParser`s that Loco makes available. The following parsers are already effectively available in most grammar notations:

* `Ferno\Loco\EmptyParser` - Just have an empty string or an empty right-hand side to a rule. Some notations also permit an explicit "epsilon" symbol.
* `Ferno\Loco\StringParser` - Invariably requires a simple string literal in single or double quotes.
* `Ferno\Loco\ConcParser` - Usually you put multiple tokens in a row and they will be matched consecutively. In EBNF, commas must be used as separators.
* `Ferno\Loco\LazyAltParser` - Alternation is achieved using a pipe, `|`, between possibilities.
* `Ferno\Loco\GreedyMultiParser` - Most notations provide some ability to make a match optional (typically square brackets), and/or to match an unlimited number of times (typically an asterisk or braces).

I had to invent new notation for the following:

* `Ferno\Loco\RegexParser` - Put your regex between slashes, just like in Perl.
* `Ferno\Loco\Utf8Parser` - To match any single UTF-8 character, put a full stop, `.`. To blacklist some characters, put the blacklisted characters between `[^` and `]`.

In both cases I borrowed notation from the standard regular expression syntax, because why not stay with the familiar?

In all cases where a "literal" is provided (strings, regexes, UTF-8 exceptions), you can put the corresponding closing delimiter (i.e. `"`, `'`, `/` or `]`) inside the "literal" by escaping it with a backslash. E.g.: `"\""`, `'\''`, `/\//`, `[^\]]`. You can also put a backslash itself, if you escape it with a second backslash. E.g.: `"\\"`, `'\\'`, `/\\/`, `[^\\]`.

#### Sample grammar in Loco notation

Remember [examples/simpleComment.php](https://github.com/ferno/loco/blob/master/examples/simpleComment.php)? Here is that grammar in Loco notation.

    comment    ::= whitespace block*
    block      ::= h5 whitespace | p whitespace
    p          ::= '<p'      whitespace '>' text '</p'      whitespace '>'
    h5         ::= '<h5'     whitespace '>' text '</h5'     whitespace '>'
    strong     ::= '<strong' whitespace '>' text '</strong' whitespace '>'
    em         ::= '<em'     whitespace '>' text '</em'     whitespace '>'
    br         ::= '<br'     whitespace '/>'
    text       ::= atom*
    atom       ::= [^<>&] | '&' entity ';' | strong | em | br
    entity     ::= 'gt' | 'lt' | 'amp'
    whitespace ::= /[ \n\r\t]*/

See how I've put `/[ \n\r\t]*/` to match an unlimited sequence of whitespace. This could be achieved using more rules and StringParsers, but RegexParsers are more powerful and more elegant.

Also see how I've put `[^<>&]` to match "any UTF-8 character except a `<`, a `>` or a `&`".

#### String in the sample grammar

    <h5>  Title<br /><em\n><strong\n></strong>&amp;</em></h5>
       \r\n\t 
    <p  >&lt;</p  >


## About Loco

Loco was created because I wanted to let people use XHTML comments on my website, and I wanted to be able validate that XHTML in a flexible way, starting with a narrow subset of XHTML and adding support for more tags over time. I believed that writing a parsing library would be more effective and educational than hand-writing (and then constantly hand-rewriting) a parser.

Loco, together with the Loco grammar [examples/simpleComment.php](https://github.com/ferno/loco/blob/master/examples/simpleComment.php), fulfilled the first objective. These were kept in successful use for several years. Later, I developed [examples/locoNotation.php](https://github.com/ferno/loco/blob/master/examples/locoNotation.php) which things even simpler for me. However, there were some drawbacks:

* Grammars had to be instantiated every time the comment-submission PHP script ran, which was laborious and inelegant. PHP doesn't make it possible to retrieve the text content of a callback, so the process of turning Loco from a parser *library* into a true parser *generator* stalled.
* Lack of backtracking meant I had to be inordinately careful in describing my CFG so that it would be unambiguous and work correctly. This need for extra effort kind of defeated the point.
* As I now realise, one of the most important things to consider when parsing user input is generating meaningful error messages when parsing failed. Loco is sort of bad at this, and users found it difficult to create correct HTML to keep it pleased.
* PHP is *horrible*.

Before beginning the project, I also observed that PHP had no parser combinator library, and I decided to fill this niche. Again, I ran into some problems:

* I didn't actually know what the term "parser combinator" meant at the time. It is not "a parser made up from a combination of other parsers". It is "a function or operator which accepts one or more parsers as input and returns a new parser as output". You can see the term being misused several times above. There is still no parser combinator library for PHP, to my knowledge.
* I knew, and still know, barely anything about parsing in general.
* PHP is *horrible*.

Overall I would say that this project fulfilled my needs at the time, and if it fulfills yours now that is probably just a coincidence. I would exercise caution when using Loco, or inspecting its code.
