# Loco examples

## [json.php](json.php)

Parse [JSON](http://json.org/) expressions and returns PHP arrays.

## [regEx.php](regEx.php)

Parse simple regular expressions and return PHP objects representing them.

## [simpleComment.php](simpleComment.php)

Recognise simple valid HTML text using `<h5>`, `<p>`, `<em>` and `<strong>`, with balanced tags and escaped entities.

## [bnf.php](bnf.php)

Defines `$bnfGrammar`, which parses a grammar presented in [Backus-Naur Form](http://en.wikipedia.org/wiki/Backus%E2%80%93Naur_Form) and returns `Ferno\Loco\Grammar` object capable of recognising that grammar.

BNF is generally pretty low-tech and lacks a lot of features.

### Sample grammar in Backus-Naur Form

This appears on Wikipedia. This is a pretty clunky example because it doesn't handle whitespace and doesn't define a whole lot of variables which I had to do myself. However, it gets the point across.

```
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
```

### String in the sample grammar

```
Steve MacLaurin \n173 Acacia Avenue 7A\nStevenage, KY 33445\n
```

## [wirth.php](wirth.php)

Defines `$wirthGrammar`, which parses a grammar presented in [Wirth syntax notation](http://en.wikipedia.org/wiki/Wirth_syntax_notation) and returns a `Ferno\Loco\Grammar` object capable of recognising that grammar.

Wirth syntax notation is okay, but I don't like the use of `.` (which in my mind usually means "any character" (when used in a regex), or the string concatenation operator) as a line ending (which I usually think of as a semicolon or an actual `\n`). I also dislike the use of square brackets for optional terms, and braces for Kleene star closure. Neither of these are unambiguous enough in their meaning.

### Sample grammar in Wirth syntax notation

```
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
```

This example grammar happens to be the grammar which describes Wirth syntax notation itself - if it had all whitespace removed from it. Observe:

### String in the sample grammar

```
SYNTAX={PRODUCTION}.
```

## [ebnf.php](ebnf.php)
Defines `$ebnfGrammar`, which parses a grammar presented in [Extended Backus-Naur Form](http://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_Form) and returns a `Grammar` object capable of recognising that grammar.

This is a big improvement on vanilla BNF (comments are a must!) but the need for commas between tokens is irritating and again, braces and square brackets aren't ideal in my mind.

`$ebnfGrammar` can't handle "specials" (strings contained between two question marks), since these have no clear definition. It also can't handle "exceptions" (when a `-` is used to discard certain possibilities), because these are not permissible in context-free grammars or possible with naive `Ferno\Loco\MonoParser`s, and so would require special modification to Loco to handle.

### Sample grammar in Extended Backus-Naur Form

```
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
```

### String in the sample grammar

```
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
```

## [locoNotation.php](locoNotation.php)

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

### Sample grammar in Loco notation

Remember [simpleComment.php](simpleComment.php)? Here is that grammar in Loco notation.

```
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
```

See how I've put `/[ \n\r\t]*/` to match an unlimited sequence of whitespace. This could be achieved using more rules and StringParsers, but RegexParsers are more powerful and more elegant.

Also see how I've put `[^<>&]` to match "any UTF-8 character except a `<`, a `>` or a `&`".

### String in the sample grammar

```
<h5>  Title<br /><em\n><strong\n></strong>&amp;</em></h5>
   \r\n\t 
<p  >&lt;</p  >
```
