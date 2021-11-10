The configuration file is a standard JSON file.
Its first level, defines template properties such as:

- `width`, `height` - total width and height in characters. These attributes are required!
- `colors` - an array representing color settings (values for registers 708 to 712)
- `encodeAs` - a way of encoding the result lines
- `scoreList` - an array of objects describing the appearance of particular score lines. This attribute is required.
- `screenFile`\* - binary file with screen content.
- `screenData`\* - array of text strings describing the screen content (hex data).

_\* One of the two attributes must be specified in the file._

It is important to consider the attributes 'screenFile' and 'screenData'. The 'screenData' attribute is considered first, and if it is not specified then the data from the file described in 'screenFile' is taken. If both attributes are defined, the 'screenData' attribute will take priority!

# Description of the score list. Object array `scoreList`

Each score line is described by a separate object.
The following attributes are required in the object:

- `x` and `y` - specifying the initial position of the score line.
- The `width` - the width of the score line.

Options are used to describe the content of the score line:

- `place` - generates a place.
- `nick` - generates player's name (his nickname).
- `score` - generates achieved score.
- `date` - generates a date of score registration.

A special option is the `inversLine` attribute, which if set to `true` generates inverted text (XOR operation on the 7th bit of each byte of the line)

The above options are objects and each MUST have attributes included:

- `shift` - offset relative to the beginning of the line (overriding position attributes)
- width` - the width of the generated value (in characters)

Optionally, each option may have attributes included:

- `align` - justification of the value relative to the specified object width (`width` attribute) Possible values are: `left`, `center`, `right`. `right` its default value.
- `fillChar` - the character that is used to fill the object in its entire width. The default value is #32 (space)
- `uppercase` - (set to `true`) converts alphabetic characters to uppercase. Default value is `false`.
- `lowercase` - (set to `true`) converts alphabet characters to lowercase. Defaults to `false`.
- `limitChars` - contains the character set that is accepted for display. Its description is a value of string type, containing all wanted characters. Paired with this attribute is the `replaceOutsideChars` attribute. By default, all characters are accepted.
- `replaceOutsideChars` - this attribute specifies the character that will be inserted if the object's character is not in the range specified in the `limitChars` attribute. The default value is #32 (space)

# Additional option attributes

The `score` and `date` options have additional attributes that extend the interpretation of the values.

## Attributes for the `score` option.

The score option defaults to being interpreted as a 32-bit integer-type value (representing the point score achieved by the player), but can also be interpreted as time.

Time is stored as an integer containing a fractional part, the precision of which is determined by the `precision` attribute. However, the precision cannot be greater than 1/100.

To convert the score into time format, define the following attributes in the `score` option:

- `showScoreAs` - specify the value of this attribute as `time`.
- `precision` - specify the precision with which the result value will be interpreted (1/n parts of a second)
- `formatTime` - describe the format that will be used for the result

The `formatTime` is a string that describes which parts of time will be displayed. The meaning of the characters in this string is as follows:

- `h` - number of hours (without leading zero)
- `Hn` - number of hours, where n is the number of leading zeros
- `m` - number of minutes (with leading zero)
- `s` - number of seconds (with leading zero)
- `f` - fraction of a second
- `Fn` - as above, only n specifies the number of decimal places.

## Attributes for the `date` option

The extension attribute for the `date` option is `formatDate`. This is a string describing how the score date is to be interpreted. By default, the format `Y.m.d` is used.

The function that formats the time is the PHP language function `date()`. Its description can be found [here](https://www.php.net/manual/en/function.date.php), and possible options [here](https://www.php.net/manual/en/datetime.format.php).
