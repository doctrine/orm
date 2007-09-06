//////////////////////////////////////////////////////////////////////////////////////////////
//	Turkish Translation by Nuri AKMAN
//	Location: Ankara/TURKEY
//	e-mail	: nuriakman@hotmail.com
//	Date	: April, 9 2003
//
//	Note: if Turkish Characters does not shown on you screen
//		  please include falowing line your html code:
//
//		  <meta http-equiv="Content-Type" content="text/html; charset=utf8">
//
//////////////////////////////////////////////////////////////////////////////////////////////

// ** I18N
Calendar._DN = new Array
("Pazar",
 "Pazartesi",
 "Salı",
 "Çarşamba",
 "Perşembe",
 "Cuma",
 "Cumartesi",
 "Pazar");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 1;

Calendar._MN = new Array
("Ocak",
 "Şubat",
 "Mart",
 "Nisan",
 "Mayıs",
 "Haziran",
 "Temmuz",
 "Ağustos",
 "Eylül",
 "Ekim",
 "Kasım",
 "Aralık");

// tooltips
Calendar._TT = {};
Calendar._TT["TOGGLE"] = "Haftanın ilk gününü kaydır";
Calendar._TT["PREV_YEAR"] = "Önceki Yıl (Menü için basılı tutunuz)";
Calendar._TT["PREV_MONTH"] = "Önceki Ay (Menü için basılı tutunuz)";
Calendar._TT["GO_TODAY"] = "Bugün'e git";
Calendar._TT["NEXT_MONTH"] = "Sonraki Ay (Menü için basılı tutunuz)";
Calendar._TT["NEXT_YEAR"] = "Sonraki Yıl (Menü için basılı tutunuz)";
Calendar._TT["SEL_DATE"] = "Tarih seçiniz";
Calendar._TT["DRAG_TO_MOVE"] = "Taşımak için sürükleyiniz";
Calendar._TT["PART_TODAY"] = " (bugün)";
Calendar._TT["MON_FIRST"] = "Takvim Pazartesi gününden başlasın";
Calendar._TT["SUN_FIRST"] = "Takvim Pazar gününden başlasın";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Display %s first";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Kapat";
Calendar._TT["TODAY"] = "Bugün";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "dd-mm-y";
Calendar._TT["TT_DATE_FORMAT"] = "d MM y, DD";

Calendar._TT["WK"] = "Hafta";
