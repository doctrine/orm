// ** I18N

// Calendar EU (basque) language
// Author: Xabier Bayon <admin@gaztesarea.net>
// Updater: Xabier Bayon <admin@gaztesarea.net>
// Updated: 2005-04-05
// Encoding: utf-8
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
Calendar._DN = new Array
("Igandea",
 "Astelehena",
 "Asteartea",
 "Asteazkena",
 "Osteguna",
 "Ostirala",
 "Larunbata",
 "Igandea");

// Please note that the following array of short day names (and the same goes
// for short month names, _SMN) isn't absolutely necessary.  We give it here
// for exemplification on how one can customize the short day names, but if
// they are simply the first N letters of the full name you can simply say:
//
//   Calendar._SDN_len = N; // short day name length
//   Calendar._SMN_len = N; // short month name length
//
// If N = 3 then this is not needed either since we assume a value of 3 if not
// present, to be compatible with translation files that were written before
// this feature.

// short day names
Calendar._SDN = new Array
("Ig",
 "Al",
 "Ar",
 "Az",
 "Os",
 "Ol",
 "La",
 "Ig");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 1;

// full month names
Calendar._MN = new Array
("Urtarrila",
 "Otsaila",
 "Martxoa",
 "Apirila",
 "Maiatza",
 "Ekaina",
 "Uztaila",
 "Abuztua",
 "Iraila",
 "Urria",
 "Azaroa",
 "Abendua");

// short month names
Calendar._SMN = new Array
("Urt",
 "Ots",
 "Mar",
 "Apr",
 "Mai",
 "Eka",
 "Uzt",
 "Abu",
 "Ira",
 "Urr",
 "Aza",
 "Abe");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "Egutegiari buruz";

Calendar._TT["ABOUT"] =
"Data/ordua DHTML hautatzailea\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"Bertsio berriena eskuratzeko: http://www.dynarch.com/projects/calendar/\n" +
"GNU LGPL baimenpean banatua. http://gnu.org/licenses/lgpl.html orrira joan zehaztasun gehiagotarako." +
"\n\n" +
"Data nola aukeratu:\n" +
"- \xab, \xbb botoiak erabili urtea aukeratzeko\n" +
"- " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " botoiak erabili hilabetea aukeratzeko\n" +
"- Aukera azkar burutzeko saguaren botoia sakatuta mantendu.";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Ordua nola aukeratu:\n" +
"- Orduan sakatu gehitzeko\n" +
"- Maiuskula sakatuta ordu kopurua gutxituko da\n" +
"- Saguaren botoia sakatu eta arrastatuz arinago burutuko da.";

Calendar._TT["PREV_YEAR"] = "Aurreko urtea (Menurako mantendu)";
Calendar._TT["PREV_MONTH"] = "Aurreko hilabetea (Menurako mantendu)";
Calendar._TT["GO_TODAY"] = "Gaurkora jo";
Calendar._TT["NEXT_MONTH"] = "Hurrengo hilabetea (Menurako mantendu)";
Calendar._TT["NEXT_YEAR"] = "Hurrengo urtea (Menurako mantendu)";
Calendar._TT["SEL_DATE"] = "Data aukeratu";
Calendar._TT["DRAG_TO_MOVE"] = "Mugitzeko arrastatu";
Calendar._TT["PART_TODAY"] = " (gaur)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "%s asteko lehenengo eguna bihurtu";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Itxi";
Calendar._TT["TODAY"] = "Gaur";
Calendar._TT["TIME_PART"] = "(Mayúscula-)Clic o arrastre para cambiar valor";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%Y/%m/%d";
Calendar._TT["TT_DATE_FORMAT"] = "%Yko %Bren %e, %A";

Calendar._TT["WK"] = "ast";
Calendar._TT["TIME"] = "Ordua:";
