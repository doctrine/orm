// ** I18N

// Calendar LV language
// Author: Juris Valdovskis, <juris@dc.lv>
// Encoding: utf8
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
Calendar._DN = new Array
("Svētdiena",
 "Pirmdiena",
 "Otrdiena",
 "Trešdiena",
 "Ceturdiena",
 "Piektdiena",
 "Sestdiena",
 "Svētdiena");

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
("Sv",
 "Pr",
 "Ot",
 "Tr",
 "Ce",
 "Pk",
 "Se",
 "Sv");


// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 1;

// full month names
Calendar._MN = new Array
("Janvāris",
 "Februāris",
 "Marts",
 "Aprīlis",
 "Maijs",
 "Jūnijs",
 "Jūlijs",
 "Augusts",
 "Septembris",
 "Oktobris",
 "Novembris",
 "Decembris");

// short month names
Calendar._SMN = new Array
("Jan",
 "Feb",
 "Mar",
 "Apr",
 "Mai",
 "Jūn",
 "Jūl",
 "Aug",
 "Sep",
 "Okt",
 "Nov",
 "Dec");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "Par kalendāru";

Calendar._TT["ABOUT"] =
"DHTML Date/Time Selector\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"For latest version visit: http://www.dynarch.com/projects/calendar/\n" +
"Distributed under GNU LGPL.  See http://gnu.org/licenses/lgpl.html for details." +
"\n\n" +
"Datuma izvēle:\n" +
"- Izmanto \xab, \xbb pogas, lai izvēlētos gadu\n" +
"- Izmanto " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + "pogas, lai izvēlētos mēnesi\n" +
"- Turi nospiestu peles pogu uz jebkuru no augstāk minētajām pogām, lai paātrinātu izvēli.";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Laika izvēle:\n" +
"- Uzklikšķini uz jebkuru no laika daļām, lai palielinātu to\n" +
"- vai Shift-klikšķis, lai samazinātu to\n" +
"- vai noklikšķini un velc uz attiecīgo virzienu lai mainītu ātrāk.";

Calendar._TT["PREV_YEAR"] = "Iepr. gads (turi izvēlnei)";
Calendar._TT["PREV_MONTH"] = "Iepr. mēnesis (turi izvēlnei)";
Calendar._TT["GO_TODAY"] = "Šodien";
Calendar._TT["NEXT_MONTH"] = "Nākošais mēnesis (turi izvēlnei)";
Calendar._TT["NEXT_YEAR"] = "Nākošais gads (turi izvēlnei)";
Calendar._TT["SEL_DATE"] = "Izvēlies datumu";
Calendar._TT["DRAG_TO_MOVE"] = "Velc, lai pārvietotu";
Calendar._TT["PART_TODAY"] = " (šodien)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Attēlot %s kā pirmo";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "1,7";

Calendar._TT["CLOSE"] = "Aizvērt";
Calendar._TT["TODAY"] = "Šodien";
Calendar._TT["TIME_PART"] = "(Shift-)Klikšķis vai pārvieto, lai mainītu";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%d-%m-%Y";
Calendar._TT["TT_DATE_FORMAT"] = "%a, %e %b";

Calendar._TT["WK"] = "wk";
Calendar._TT["TIME"] = "Laiks:";
