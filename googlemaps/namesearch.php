<?php
session_start();

include_once(__DIR__ . "/../include/db_login.php"); //Inloggen database.
include_once(__DIR__ . "/../include/safe.php");
include_once(__DIR__ . "/../include/settings_global.php"); //Variables
include_once(__DIR__ . "/../include/settings_user.php"); // USER variables
include_once(__DIR__ . "/../include/person_cls.php"); // for privacy
include_once(__DIR__ . "/../include/language_date.php");
include_once(__DIR__ . "/../include/date_place.php");
include_once(__DIR__ . '/../include/link_cls.php');
$link_cls = new Link_cls();

$tree_id = $_SESSION['tree_id'];

include_once(__DIR__ . "/../include/db_functions_cls.php");
$db_functions = new Db_functions_cls($dbh);
$db_functions->set_tree_id($tree_id);

$language_folder = opendir('../languages/');
while (false !== ($file = readdir($language_folder))) {
    if (strlen($file) < 5 and $file != '.' and $file != '..') {
        $language_file[] = $file;

        // *** Save choice of language ***
        $language_choice = '';
        if (isset($_GET["language"])) {
            $language_choice = $_GET["language"];
        }

        if ($language_choice != '') {
            // Check if file exists (IMPORTANT DO NOT REMOVE THESE LINES)
            // ONLY save an existing language file.
            if ($language_choice == $file) {
                $_SESSION['language'] = $file;
            }
        }
    }
}
closedir($language_folder);
// *** Language processing after header("..") lines. ***
include_once(__DIR__ . "/../languages/language.php"); //Taal

// *** Process LTR and RTL variables ***
$dirmark1 = "&#x200E;";  //ltr marker
$dirmark2 = "&#x200F;";  //rtl marker
$rtlmarker = "ltr";
$alignmarker = "left";
// *** Switch direction markers if language is RTL ***
if ($language["dir"] == "rtl") {
    $dirmark1 = "&#x200F;";  //rtl marker
    $dirmark2 = "&#x200E;";  //ltr marker
    $rtlmarker = "rtl";
    $alignmarker = "right";
}


//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~`
// start of the namesearch part

if (isset($_GET['max'])) {
    $map_max = $_GET['max'];
} else { // Logically we can never get here because this file is always called with this parameter
    $map_max = date('Y'); //this year
}

if (isset($_GET['thisplace'])) {
    $thisplace = urldecode($_GET['thisplace']);     // should be done automatically but it doesn't hurt
    $thisplace = str_replace("\'", "'", $thisplace);  // in some settings the \ is passed on with the ' while in others not
    $thisplace = str_replace("'", "''", $thisplace);     // for MySQL single quote has to be written as 2 single quotes: '' in the mysql query
} else { // Logically we can never get here because this file is always called with this parameter
    $thisplace = "NONFOUND";
}

function mapbirthplace($place)
{
    global $dbh, $tree_id, $language, $map_max, $link_cls;

    if (isset($_GET['namestring'])) {
        $temparray = explode("@", $_GET['namestring']);
        $namestring = " (";
        foreach ($temparray as $value) { //echo $value.'<br>';
            //$namestring .=  "pers_lastname = '".$value."' OR ";
            $namestring .= "CONCAT(pers_lastname,'_',LOWER(SUBSTRING_INDEX(pers_prefix,'_',1))) = '" . $value . "' OR ";
        }
        $namestring = substr($namestring, 0, -3) . ") AND "; //echo $namestring;
    } else {
        $namestring = '';
    }

    $desc_arr = '';
    $idstring = '';
    if (isset($_SESSION['desc_array'])) {
        $desc_arr = $_SESSION['desc_array'];
        $idstring = ' (';
        foreach ($desc_arr as $value) {
            $idstring .= " pers_gedcomnumber = '" . $value . "' OR ";
        }
        $idstring = substr($idstring, 0, -3) . ') AND ';
    }

    $anc_arr = '';
    $anc_idstring = '';
    if (isset($_SESSION['anc_array'])) {
        $anc_arr = $_SESSION['anc_array'];
        $anc_idstring = ' (';
        foreach ($anc_arr as $value) {
            $anc_idstring .= " pers_gedcomnumber = '" . $value . "' OR ";
        }
        $anc_idstring = substr($anc_idstring, 0, -3) . ') AND ';
    }

    if ($anc_idstring != '') {
        $idstring = $anc_idstring;
    }

    $min = 1;
    if ($place != "NONFOUND") {
        if ($_SESSION['type_birth'] == 1) {
            if (isset($_GET['all'])) { // the 'All birth locations' button
                echo '<b><u>' . __('All persons born here: ') . '</u></b><br>';
                $sql = "SELECT * , CONCAT(pers_lastname,pers_firstname) AS wholename
                    FROM humo_persons WHERE pers_tree_id='" . $tree_id . "'
                    AND " . $idstring . $namestring . " (pers_birth_place = '" . $place . "' OR (pers_birth_place = '' AND pers_bapt_place = '" . $place . "')) ORDER BY wholename";
                $maplist = $dbh->query($sql);
            } else { // *** Slider is used ***
                echo '<b><u>' . __('Persons born here until ') . $map_max . ':</u></b><br>';
                $sql = "SELECT * , CONCAT(pers_lastname,pers_firstname) AS wholename FROM humo_persons
                    WHERE pers_tree_id='" . $tree_id . "'
                    AND " . $idstring . $namestring . " (pers_birth_place = '" . $place . "' OR (pers_birth_place = '' AND pers_bapt_place = '" . $place . "'))
                    AND ((SUBSTR(pers_birth_date,-LEAST(4,CHAR_LENGTH(pers_birth_date))) < " . $map_max . "
                    AND SUBSTR(pers_birth_date,-LEAST(4,CHAR_LENGTH(pers_birth_date))) > " . $min . ")
                    OR (pers_birth_date='' AND SUBSTR(pers_bapt_date,-LEAST(4,CHAR_LENGTH(pers_bapt_date))) < " . $map_max . "
                        AND SUBSTR(pers_bapt_date,-LEAST(4,CHAR_LENGTH(pers_bapt_date))) > " . $min . "))
                    ORDER BY wholename";
                $maplist = $dbh->query($sql);
            }
        } elseif ($_SESSION['type_death'] == 1) {
            if (isset($_GET['all'])) { // the 'All birth locations' button
                echo '<b><u>' . __('All persons that died here: ') . '</u></b><br>';
                $sql = "SELECT * , CONCAT(pers_lastname,pers_firstname) AS wholename
                    FROM humo_persons
                    WHERE pers_tree_id='" . $tree_id . "'
                    AND " . $idstring . $namestring . "
                    (pers_death_place = '" . $place . "' OR (pers_death_place = '' AND pers_buried_place = '" . $place . "'))
                    ORDER BY wholename";
                $maplist = $dbh->query($sql);
            } else { // *** Slider is used ***
                echo '<b><u>' . __('Persons that died here until ') . $map_max . ':</u></b><br>';
                $sql = "SELECT * , CONCAT(pers_lastname,pers_firstname) AS wholename FROM humo_persons
                    WHERE pers_tree_id='" . $tree_id . "' AND " . $idstring . $namestring . "
                    (pers_death_place = '" . $place . "' OR (pers_death_place = '' AND pers_buried_place = '" . $place . "')) AND
                    ((SUBSTR(pers_death_date,-LEAST(4,CHAR_LENGTH(pers_death_date))) < " . $map_max . " AND SUBSTR(pers_death_date,-LEAST(4,CHAR_LENGTH(pers_death_date))) > " . $min . ") OR
                    (pers_death_date='' AND SUBSTR(pers_buried_date,-LEAST(4,CHAR_LENGTH(pers_buried_date))) < " . $map_max . " AND SUBSTR(pers_buried_date,-LEAST(4,CHAR_LENGTH(pers_buried_date))) > " . $min . "))
                    ORDER BY wholename";
                $maplist = $dbh->query($sql);
            }
        }
        //echo 'TEST: '.$sql;
?>

        <div style="direction:ltr">
            <?php
            while (@$maplistDb = $maplist->fetch(PDO::FETCH_OBJ)) {
                $man_cls = new Person_cls($maplistDb);
                $privacy_man = $man_cls->privacy;
                $name = $man_cls->person_name($maplistDb);
                if ($name["show_name"] == true) {
                    $pers_family = '';
                    if ($maplistDb->pers_famc) {
                        $pers_family = $maplistDb->pers_famc;
                    }
                    if ($maplistDb->pers_fams) {
                        $pers_fams = explode(';', $maplistDb->pers_fams);
                        $pers_family = $pers_fams[0];
                    }
                    $vars['pers_family'] = $pers_family;
                    $link = $link_cls->get_link('', 'family', $maplistDb->pers_tree_id, true, $vars);
                    $link .= "main_person=" . $maplistDb->pers_gedcomnumber;
                    echo '<a href=' . $link . ' target="blank">';
                }
                if ($_SESSION['type_birth'] == 1) {
                    echo $name["index_name"];
                    $date = $maplistDb->pers_birth_date;
                    $sign = __('born') . ' ';
                    if (!$maplistDb->pers_birth_date and $maplistDb->pers_bapt_date) {
                        $date = $maplistDb->pers_bapt_date;
                        $sign = __('baptised') . ' ';
                    }
                }
                if ($_SESSION['type_death'] == 1) {
                    echo $name["index_name"];
                    $date = $maplistDb->pers_death_date;
                    $sign = __('died') . ' ';
                    if (!$maplistDb->pers_death_date and $maplistDb->pers_buried_date) {
                        $date = $maplistDb->pers_buried_date;
                        $sign = __('buried') . ' ';
                    }
                }
                if (!$privacy_man and $date and $name["show_name"] == true) {
                    echo ' (' . $sign . date_place($date, '') . ')';
                }
                if ($name["show_name"] == true) {
                    echo '</a>';
                }
            ?>
                <br>
            <?php } ?>
        </div>
<?php
    } else { // Logically we can never get here
        echo 'No persons found';
    }
}

mapbirthplace($thisplace);
