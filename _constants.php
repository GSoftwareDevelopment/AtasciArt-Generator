<?
// config file sections and attributes
// required section
const CONFIG_LAYOUTS="layouts";
const CONFIG_LAYOUTS_LINES="lines";
// optional section
const CONFIG_ELEMENTSCHAMES="lineSchemes";

// layout section
// attributes in layout section
const CONFIG_LAYOUTS_SCREENDATA="screenData";
const CONFIG_LAYOUTS_ENCODEELEMENTAS="encodeLinesAs";
const CONFIG_SCREENFILL="screenFill";

// default elements tags
const ELEMENT_PLACE="place";
const ELEMENT_NICK="nick";
const ELEMENT_SCORE="score";
const ELEMENT_DATE="date";
const ELEMENT_TEXT="text";
const ELEMENT_GENTIME="genTime";
// separator sign for element labeling
const LABEL_SEPARATOR=".";

// required attributes, used in layers and elements section
const ATTR_X="x";
const ATTR_Y="y";
const ATTR_XOFFSET="offsetX";
const ATTR_YOFFSET="offsetY";
const ATTR_WIDTH="width";
const ATTR_HEIGHT="height";

// attributes used in elements section
const ATTR_ISENTRY="isEntry";
const ATTR_USESCHEMA="useSchema";
const ATTR_USEATASCIFONT="useAtasciiFont";
const ATTR_ALIGN="align";
const ATTR_FILLCHAR="fillChar";
const ATTR_INVERS="invert";
const ATTR_LETTERCASE="letterCase";
const ATTR_LIMITCHAR="limitChar";
const ATTR_REPLACEOUTSIDECHAR="replaceOutsideChar";
const ATTR_SHOWSCOREAS="showScoreAs";
const ATTR_PRECISION="precision";
const ATTR_CONTENT="content";
const ATTR_FORMAT="format";

// default values for attributes
const DEFAULT_BCDTIMEFORMAT="H2:m:s";
const DEFAULT_INTTIMEFORMAT="m:s";
const DEFAULT_DATEFORMAT="Y.m.d";
const DEFAULT_GENTIME_FORMAT="Y.m.d H:i:s";

//
const DEFAULT_FONT_FILE="./atari_16.png";
const DEFAULT_CHAR_WIDTH=16;
const DEFAULT_CHAR_HEIGHT=16;
?>