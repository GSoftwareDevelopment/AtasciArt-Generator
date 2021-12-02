<?
// config file sections and attributes
// required section
const SECTION_LAYOUT="layout";
const SECTION_LINES="lines";
// optional section
const SECTION_ELEMENTSCHAMES="lineSchemas";

// default elements tags
const ELEMENT_DATE="date";
const ELEMENT_GENTIME="genTime";
const ELEMENT_NICK="nick";
const ELEMENT_PLACE="place";
const ELEMENT_SCORE="score";
const ELEMENT_TEXT="text";
// separator sign for element labeling
const LABEL_SEPARATOR=".";

// attributes in layout section
const ATTR_SCREENDATA="screenData";
const ATTR_ENCODEAS="encodeAs";
const ATTR_SCREENFILL="screenFill";

// required attributes, used in layers and elements section
const ATTR_HEIGHT="height";
const ATTR_WIDTH="width";
const ATTR_X="x";
const ATTR_XOFFSET="offsetX";
const ATTR_Y="y";
const ATTR_YOFFSET="offsetY";

// attributes used in elements section
const ATTR_ALIGN="align";
const ATTR_CONTENT="content";
const ATTR_FILLCHAR="fillChar";
const ATTR_FORMAT="format";
const ATTR_INVERS="invert";
const ATTR_ISENTRY="isEntry";
const ATTR_LETTERCASE="letterCase";
const ATTR_LIMITCHAR="limitChar";
const ATTR_PRECISION="precision";
const ATTR_REPLACEOUTSIDECHAR="replaceOutsideChar";
const ATTR_SHOWSCOREAS="showScoreAs";
const ATTR_USEPALETTE="usePalette";
const ATTR_USESCHEMA="useSchema";
const ATTR_USEATASCIFONT="useAtasciiFont";

// default values for attributes
const DEFAULT_BCDTIMEFORMAT="H2:m:s";
const DEFAULT_INTTIMEFORMAT="m:s";
const DEFAULT_DATEFORMAT="Y.m.d";
const DEFAULT_GENTIME_FORMAT="Y.m.d H:i:s";

//
const DEFAULT_PALETTE_PATH="./palette/";
const DEFAULT_PALETTE_FILE="altirra";
const DEFAULT_FONT_FILE="./atari_16.png";
const DEFAULT_CHAR_WIDTH=16;
const DEFAULT_CHAR_HEIGHT=16;
?>