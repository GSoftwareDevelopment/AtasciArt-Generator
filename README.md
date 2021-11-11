# Configuration file

The configuration file is a JSON formatted file.

    It is important to note that the attribute names and their values are case sensitive!

Its first level, defines template properties such as:

- `width`, `height` - total width and height in characters. **These attributes are required!**
- `colors` - an array representing color settings (values for registers 708 to 712)
- `encodeAs` - a way to encode the score lines
- `scoreList` - an array of objects describing the appearance of individual score lines. **This attribute is required.**
- \* `screenFile` - a binary file with the contents of the screen
- \* `screenData` - an array of text strings describing the screen content (hex data).

_\* One of the two attributes MUST be specified in the file._

It is important to consider the `screenFile` and `screenData` attributes. The `screenData` attribute is considered first, and if it is not specified then data is taken from the file described in `screenFile`. If both attributes are defined, the `screenData` attribute will take priority!

## Description of the result list. An array of `scoreList` objects

Each score line is described by a separate object. To understand the essence of objecthood, it is best to use a graphic:

![figure](./AtasciArt-objects.png)

The following attributes are required in an object:

- `x` and `y` - specifying the initial position of the result line.
- The `width` - the width of the result line.

Options are used to describe the content of the score line:

- `place` - generates a place
- `nick` - generates a player name (his nickname)
- `score` - generates a score.
- `date` - generates the date of the score registration.

A special option is the `inversLine` attribute, which if set to `true` generates text in inverted colors (XOR operation on the 7th bit of each line character)

The above options are objects and each MUST have attributes included:

- `shift` - offset relative to the beginning of the line (in characters).
- `width` - the width of the generated value (in characters).

Optionally, each object can have the attributes:

- `align` - justification of the content relative to the specified width of the object (`width` attribute) Possible values are: `left`, `center`, `right`. The `right` value is the default.
- `fillChar` - the character that will be used to fill the object across its width. The default value is #32 (space)
- `uppercase` - (set to `true`) converts alphabetic characters to uppercase. Default value is `false`.
- `lowercase` - (set to `true`) converts alphabetic characters to lowercase. Defaults to `false`.
- `limitChars` - contains the character set that is accepted for display. Its description is a value of string type, containing all wanted characters. Paired with this attribute is the `replaceOutsideChars` attribute. By default, all characters are accepted.
- `replaceOutsideChars` - this attribute specifies the character that will be inserted when an object character is not in the range specified in the `limitChars` attribute. The default value is #32 (space)
- `invert` - works the same as the `inversLine` attribute in the `scoreList` section except that it is applied only to the generated object.

## Additional option attributes

The `score` and `date` options have additional attributes that extend the interpretation of the values.

### Attributes for the `score` option.

The score option is interpreted, by default, as a 32-bit integer type value (representing the point score achieved by the player). It can also be represented as a time.

The time is stored as an integer containing a fractional part, the precision of which is specified by the `precision` attribute in the range 2 to 100. The `precision` value should be understood as a fraction of a second 1/n. This is best understood by presenting it in a table:

| `score` | `precision` | result |
| ------- | ----------- | ------ |
| 1       | 5 (1/5s)    | 00s.20 |
| 5       |             | 01s.00 |
| 51      |             | 10s.20 |
| 1       | 50 (1/50s)  | 00s.02 |
| 5       |             | 00s.10 |
| 55      |             | 01s.10 |

To convert the score to time format, define the following attributes in the `score` option:

- `showScoreAs` - specify the value of this attribute as `time`.
- `precision` - specify the precision with which the result value will be interpreted (1/n parts of a second)
- `formatTime` - describe the format that will be applied to the score.

The `formatTime` is a string that describes what parts of time will be displayed. The meaning of the characters in this string is as follows:

- `h` - number of hours (without leading zero)
- `Hn` - number of hours, where `n` specifies the number of leading zeros (one digit)
- `m` - number of minutes (with a leading zero)
- `s` - number of seconds (with leading zeroes)
- `f` -fraction of a second (two digits)
- `Fn` - as above, only n specifies the number of decimal places.

Unrecognized characters in the format string will be shown as they are.

### Attributes for the `date` option.

An attribute that extends the `date` options is `formatDate`. This is a string describing how the result date is to be interpreted. By default, the format `Y.m.d` is used.

The function that formats the time is the PHP language function `date()`. Its description can be found [here](https://www.php.net/manual/en/function.date.php), and possible formatting options [here](https://www.php.net/manual/en/datetime.format.php).
