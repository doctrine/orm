// ** I18N

// Calendar EN language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Encoding: UTF-8
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// Translator: André Liechti, <developer@sysco.ch> (2006-01-04) from scratch for version 1.x

// full day names
Calendar._DN = new Array
("Dimanche",
 "Lundi",
 "Mardi",
 "Mercredi",
 "Jeudi",
 "Vendredi",
 "Samedi",
 "Dimanche");

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
("Dim",
 "Lun",
 "Mar",
 "Mer",
 "Jeu",
 "Ven",
 "Sam",
 "Dim");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 1;

// full month names
Calendar._MN = new Array
("Janvier",
 "Février",
 "Mars",
 "Avril",
 "Mai",
 "Juin",
 "Juillet",
 "Août",
 "Septembre",
 "Octobre",
 "Novembre",
 "Décembre");

// short month names
Calendar._SMN = new Array
("Jan",
 "Fév",
 "Mar",
 "Avr",
 "Mai",
 "Juin",
 "Juil",
 "Aou",
 "Sep",
 "Oct",
 "Nov",
 "Déc");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "À propos du calendrier";

Calendar._TT["ABOUT"] =
"Sélecteur DHTML de date/heure\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"Pour la version actuelle, visitez: http://www.dynarch.com/projects/calendar/\n" +
"Distribué sous licence GNU LGPL.  Voir http://gnu.org/licenses/lgpl.html pour les détails." + "\n(licence traduite en français: http://www.rodage.org/lgpl.fr.html)" +
"\n\n" +
"Sélection de la date:\n" +
"- Utiliser les boutons \xab, \xbb pour sélectionner l'année\n" +
"- Utiliser les boutons " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " pour sélectionner le mois\n" +
"- En conservant pressé le bouton de la souris sur l'un de ces boutons, la sélection devient plus rapide.";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Sélection de l\'heure:\n" +
"- Cliquer sur l'une des parties du temps pour l'augmenter\n" +
"- ou Maj-clic pour le diminuer\n" +
"- ou faire un cliquer-déplacer horizontal pour une modification plus rapide.";

Calendar._TT["PREV_YEAR"] = "Année préc. (maintenir pour afficher menu)";
Calendar._TT["PREV_MONTH"] = "Mois préc. (maintenir pour afficher menu)";
Calendar._TT["GO_TODAY"] = "Atteindre la date du jour";
Calendar._TT["NEXT_MONTH"] = "Mois suiv. (maintenir pour afficher menu)";
Calendar._TT["NEXT_YEAR"] = "Année suiv. (maintenir pour afficher menu)";
Calendar._TT["SEL_DATE"] = "Sélectionner une date";
Calendar._TT["DRAG_TO_MOVE"] = "Glisser pour déplacer";
Calendar._TT["PART_TODAY"] = " (aujourd'hui)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Afficher %s en premier";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Fermer";
Calendar._TT["TODAY"] = "Aujourd'hui";
Calendar._TT["TIME_PART"] = "(Maj-)Clic ou glisser pour changer la valeur";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%d.%m.%Y";
Calendar._TT["TT_DATE_FORMAT"] = "%A, %e %B";

Calendar._TT["WK"] = "sem.";
Calendar._TT["TIME"] = "Heure:";
