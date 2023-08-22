<?php

/**
 * First test scipt made by: Klaas de Winkel
 * Graphical script made by: Theo Huitema
 * Graphical part: better lay-out (colours) and pictures made by: Rene Janssen
 * Graphical part: improved lay-out by: Huub Mons.
 * Ancestor sheet, PDF export for ancestor report and ancestor sheet, image generation for chart made by: Yossi Beck.
 * July 2011: translated all variables to english by: Huub Mons.
 */

@set_time_limit(3000);

global $humo_option, $user, $marr_date_array, $marr_place_array;
global $gedcomnumber, $language;
global $screen_mode, $dirmark1, $dirmark2, $pdf_footnotes;

$screen_mode = 'ancestor_chart';

if (isset($hourglass) and $hourglass === true) {
    //$screen_mode = 'ancestor_chart';
} else {
    $hourglass = false;
}

$pdf_source = array();  // is set in show_sources.php with sourcenr as key to be used in source appendix



// TODO create seperate controller script.
// TEMPORARY CONTROLLER HERE:
require_once  __DIR__ . "/../models/ancestor.php";
$get_ancestor = new Ancestor($dbh);
//$family_id = $get_family->getFamilyId();
$main_person = $get_ancestor->getMainPerson();
//$family_expanded =  $get_family->getFamilyExpanded();
//$source_presentation =  $get_family->getSourcePresentation();
//$picture_presentation =  $get_family->getPicturePresentation();
//$text_presentation =  $get_family->getTextPresentation();
$rom_nr = $get_ancestor->getNumberRoman();
//$number_generation = $get_family->getNumberGeneration();
$ancestor_header = $get_ancestor->getAncestorHeader('Ancestor chart', $tree_id, $main_person);
//$this->view("families", array(
//    "family" => $family,
//    "title" => __('Family')
//));



// *** Needed for hourglass ***
include_once(CMS_ROOTPATH . "menu.php");

if ($hourglass === false) {
    //TODO check if this is still needed
    $main_person = 'I1'; // *** Default value, normally not used... ***
    if (isset($_GET["id"])) {
        $main_person = $_GET["id"];
    }
    if (isset($_POST["id"])) {
        $main_person = $_POST["id"];
    }

    // *** Check if person gedcomnumber is valid ***
    $db_functions->check_person($main_person);
}

if ($hourglass === false) {
    //echo '<h1 class="standard_header fonts">' . __('Ancestor chart') . '</h1>';
    echo $ancestor_header;
}

// The following is used for ancestor chart, ancestor sheet and ancestor sheet PDF (ASPDF)
// person 01
$personDb = $db_functions->get_person($main_person);
$gedcomnumber[1] = $personDb->pers_gedcomnumber;
$pers_famc[1] = $personDb->pers_famc;
$sexe[1] = $personDb->pers_sexe;
$parent_array[2] = '';
$parent_array[3] = '';
if ($pers_famc[1]) {
    $parentDb = $db_functions->get_family($pers_famc[1]);
    $parent_array[2] = $parentDb->fam_man;
    $parent_array[3] = $parentDb->fam_woman;
    $marr_date_array[2] = $parentDb->fam_marr_date;
    $marr_place_array[2] = $parentDb->fam_marr_place;
}
// end of person 1

// Loop to find person data
$count_max = 64;
if ($hourglass === true) {
    $count_max = pow(2, $chosengenanc);
}

for ($counter = 2; $counter < $count_max; $counter++) {
    $gedcomnumber[$counter] = '';
    $pers_famc[$counter] = '';
    $sexe[$counter] = '';
    if ($parent_array[$counter]) {
        $personDb = $db_functions->get_person($parent_array[$counter]);
        $gedcomnumber[$counter] = $personDb->pers_gedcomnumber;
        $pers_famc[$counter] = $personDb->pers_famc;
        $sexe[$counter] = $personDb->pers_sexe;
    }

    $Vcounter = $counter * 2;
    $Mcounter = $Vcounter + 1;
    $parent_array[$Vcounter] = '';
    $parent_array[$Mcounter] = '';
    $marr_date_array[$Vcounter] = '';
    $marr_place_array[$Vcounter] = '';
    if ($pers_famc[$counter]) {
        $parentDb = $db_functions->get_family($pers_famc[$counter]);
        $parent_array[$Vcounter] = $parentDb->fam_man;
        $parent_array[$Mcounter] = $parentDb->fam_woman;
        $marr_date_array[$Vcounter] = $parentDb->fam_marr_date;
        $marr_place_array[$Vcounter] = $parentDb->fam_marr_place;
    }
}

// *** Function to show data ***
// box_appearance (large, medium, small, and some other boxes...)
function ancestor_chart_person($id, $box_appearance)
{
    global $dbh, $db_functions, $tree_prefix_quoted, $humo_option, $user;
    global $marr_date_array, $marr_place_array;
    global $gedcomnumber, $language, $screen_mode, $dirmark1, $dirmark2;

    $hour_value = ''; // if called from hourglass.php size of chart is given in box_appearance as "hour45" etc.
    if (strpos($box_appearance, "hour") !== false) {
        $hour_value = substr($box_appearance, 4);
    }

    $text = '';
    $popup = '';

    if ($gedcomnumber[$id]) {
        @$personDb = $db_functions->get_person($gedcomnumber[$id]);
        $person_cls = new person_cls($personDb);
        $pers_privacy = $person_cls->privacy;
        $name = $person_cls->person_name($personDb);
        $name2 = $name["name"];
        $name2 = $dirmark2 . $name2 . $name["colour_mark"] . $dirmark2;

        // *** Replace pop-up icon by a text box ***
        $replacement_text = '';
        //$replacement_text.='<b>'.$id.'</b>';  // *** Ancestor number: id bold, name not ***
        $replacement_text .= '<span class="anc_box_name">' . $name2 . '</span>';

        // >>>>> link to show rest of ancestor chart
        //if ($box_appearance=='small' AND isset($personDb->pers_gedcomnumber) AND $screen_mode!="ancestor_sheet"){
        if ($box_appearance == 'small' and isset($personDb->pers_gedcomnumber) and $personDb->pers_famc and $screen_mode != "ancestor_sheet") {
            $replacement_text .= ' &gt;&gt;&gt;' . $dirmark1;
        }

        if ($pers_privacy) {
            if ($box_appearance != 'ancestor_sheet_marr') {
                $replacement_text .= '<br>' . __(' PRIVACY FILTER');  //Tekst privacy weergeven
            } else {
                $replacement_text = __(' PRIVACY FILTER');
            }
        } else {
            if ($box_appearance != 'small') {
                //if ($personDb->pers_birth_date OR $personDb->pers_birth_place){
                if ($personDb->pers_birth_date) {
                    //$replacement_text.='<br>'.__('*').$dirmark1.' '.date_place($personDb->pers_birth_date,$personDb->pers_birth_place); }
                    $replacement_text .= '<br>' . __('*') . $dirmark1 . ' ' . date_place($personDb->pers_birth_date, '');
                }
                //elseif ($personDb->pers_bapt_date OR $personDb->pers_bapt_place){
                elseif ($personDb->pers_bapt_date) {
                    //$replacement_text.='<br>'.__('~').$dirmark1.' '.date_place($personDb->pers_bapt_date,$personDb->pers_bapt_place); }
                    $replacement_text .= '<br>' . __('~') . $dirmark1 . ' ' . date_place($personDb->pers_bapt_date, '');
                }

                //if ($personDb->pers_death_date OR $personDb->pers_death_place){
                if ($personDb->pers_death_date) {
                    //$replacement_text.='<br>'.__('&#134;').$dirmark1.' '.date_place($personDb->pers_death_date,$personDb->pers_death_place); }
                    $replacement_text .= '<br>' . __('&#134;') . $dirmark1 . ' ' . date_place($personDb->pers_death_date, '');
                }
                //elseif ($personDb->pers_buried_date OR $personDb->pers_buried_place){
                elseif ($personDb->pers_buried_date) {
                    //$replacement_text.='<br>'.__('[]').$dirmark1.' '.date_place($personDb->pers_buried_date,$personDb->pers_buried_place); }
                    $replacement_text .= '<br>' . __('[]') . $dirmark1 . ' ' . date_place($personDb->pers_buried_date, '');
                }

                if ($box_appearance != 'medium') {
                    $marr_date = '';
                    if (isset($marr_date_array[$id]) and ($marr_date_array[$id] != '')) {
                        $marr_date = $marr_date_array[$id];
                    }
                    $marr_place = '';
                    if (isset($marr_place_array[$id]) and ($marr_place_array[$id] != '')) {
                        $marr_place = $marr_place_array[$id];
                    }
                    //if ($marr_date OR $marr_place){
                    if ($marr_date) {
                        //$replacement_text.='<br>'.__('X').$dirmark1.' '.date_place($marr_date,$marr_place); }
                        $replacement_text .= '<br>' . __('X') . $dirmark1 . ' ' . date_place($marr_date, '');
                    }
                }
                if ($box_appearance == 'ancestor_sheet_marr') {
                    $replacement_text = '';
                    $marr_date = '';
                    if (isset($marr_date_array[$id]) and ($marr_date_array[$id] != '')) {
                        $marr_date = $marr_date_array[$id];
                    }
                    $marr_place = '';
                    if (isset($marr_place_array[$id]) and ($marr_place_array[$id] != '')) {
                        $marr_place = $marr_place_array[$id];
                    }
                    //if ($marr_date OR $marr_place){
                    if ($marr_date) {
                        //$replacement_text=__('X').$dirmark1.' '.date_place($marr_date,$marr_place); }
                        $replacement_text = __('X') . $dirmark1 . ' ' . date_place($marr_date, '');
                    } else $replacement_text = __('X'); // if no details in the row we don't want the row to collapse
                }
                if ($box_appearance == 'ancestor_header') {
                    $replacement_text = '';
                    $replacement_text .= strip_tags($name2);
                    $replacement_text .= $dirmark2;
                }
            }
        }

        if ($hour_value != '') { // called from hourglass
            if ($hour_value == '45') {
                $replacement_text = $name['name'];
            } elseif ($hour_value == '40') {
                $replacement_text = '<span class="wordwrap" style="font-size:75%">' . $name['short_firstname'] . '</span>';
            } elseif ($hour_value > 20 and $hour_value < 40) {
                $replacement_text = $name['initials'];
            } elseif ($hour_value < 25) {
                $replacement_text = "&nbsp;";
            }
            // if full scale (50) then the default of this function will be used: name with details
        }

        $extra_popup_text = '';
        $marr_date = '';
        if (isset($marr_date_array[$id]) and ($marr_date_array[$id] != '')) {
            $marr_date = $marr_date_array[$id];
        }
        $marr_place = '';
        if (isset($marr_place_array[$id]) and ($marr_place_array[$id] != '')) {
            $marr_place = $marr_place_array[$id];
        }
        if ($marr_date or $marr_place) {
            $extra_popup_text .= '<br>' . __('X') . $dirmark1 . ' ' . date_place($marr_date, $marr_place);
        }

        // *** Show picture by person ***
        if ($box_appearance != 'small' and $box_appearance != 'medium' and (strpos($box_appearance, "hour") === false or $box_appearance == "hour50")) {
            // *** Show picture ***
            if (!$pers_privacy and $user['group_pictures'] == 'j') {
                //  *** Path can be changed per family tree ***
                global $dataDb;
                $tree_pict_path = $dataDb->tree_pict_path;
                if (substr($tree_pict_path, 0, 1) == '|') $tree_pict_path = 'media/';
                $picture_qry = $db_functions->get_events_connect('person', $personDb->pers_gedcomnumber, 'picture');
                // *** Only show 1st picture ***
                if (isset($picture_qry[0])) {
                    $pictureDb = $picture_qry[0];
                    $picture = show_picture($tree_pict_path, $pictureDb->event_event, 80, 70);
                    //$text.='<img src="'.$tree_pict_path.$picture['thumb'].$picture['picture'].'" style="float:left; margin:5px;" alt="'.$pictureDb->event_text.'" width="'.$picture['width'].'">';
                    $text .= '<img src="' . $picture['path'] . $picture['thumb'] . $picture['picture'] . '" style="float:left; margin:5px;" alt="' . $pictureDb->event_text . '" width="' . $picture['width'] . '">';
                }
            }
        }

        if ($box_appearance == 'ancestor_sheet_marr' or $box_appearance == 'ancestor_header') { // cause in that case there is no link
            $text .= $replacement_text;
        } else {
            $text .= $person_cls->person_popup_menu($personDb, true, $replacement_text, $extra_popup_text);
        }
    }

    return $text . "\n";
}
// *** End of function ancestor_chart_person ***

// Specific code for ancestor chart:
if ($screen_mode != "ancestor_sheet" and $screen_mode != "ASPDF" and $hourglass === false) {
    echo '<script src="include/html2canvas/html2canvas.min.js"></script>';

    echo '<div style="text-align:center;">';
    echo '<br><input type="button" id="imgbutton" value="' . __('Get image of chart for printing (allow popup!)') . '" onClick="showimg();">';
    echo '</div>';

    $divlen = 1000;
    // width of the chart. for 6 generations 1000px is right
    // if we ever make the anc chart have optionally more generations, the width and length will have to be generated
    // as in dreport_descendant.php

    //following div gets width and length in imaging java function showimg() (at bottom) otherwise double scrollbars won't work.
    echo '<div id="png">';

    echo '
<style type="text/css">
        #doublescroll { position:relative; width:auto; height:1100px; overflow: auto; overflow-y: hidden; }
        #doublescroll p { margin: 0; padding: 1em; white-space: nowrap; }
</style>
';

    echo '<div id="doublescroll">';

    // *** First column name ***
    $left = 10;
    $sexe_colour = '';
    $backgr_col = "#FFFFFF";
    if ($sexe[1] == 'F') {
        $sexe_colour = ' ancestor_woman';
        $backgr_col = "#FBDEC0";
    }
    if ($sexe[1] == 'M') {
        $sexe_colour = ' ancestor_man';
        $backgr_col =  "#C0F9FC";
    }
    //echo '<div class="ancestorName'.$sexe_colour.'" style="top: 520px; left: '.$left.'px; height: 80px; width:180px;';
    // *** No _ character allowed in name of CSS class because of javascript ***
    echo '<div class="ancestorName' . $sexe_colour . '" align="left" style="background-color:' . $backgr_col . '; top: 520px; left: ' . $left . 'px; height: 80px; width:200px;">';
    echo ancestor_chart_person('1', 'large');
    echo '</div>';

    $left = 50;
    $top = 320;
    // *** Second column split ***
    echo '<div class="ancestor_split" style="top: ' . $top . 'px; left: ' . $left . 'px; height: 199px"></div>';
    echo '<div class="ancestor_split" style="top: ' . ($top + 281) . 'px; left: ' . $left . 'px; height: 199px"></div>';
    // *** Second column names ***
    for ($i = 1; $i < 3; $i++) {
        $sexe_colour = '';
        $backgr_col = "#FFFFFF";
        if ($sexe[$i + 1] == 'F') {
            $sexe_colour = ' ancestor_woman';
            $backgr_col = "#FBDEC0";
        }
        if ($sexe[$i + 1] == 'M') {
            $sexe_colour = ' ancestor_man';
            $backgr_col =  "#C0F9FC";
        }
        echo '<div class="ancestorName' . $sexe_colour . '" style="background-color:' . $backgr_col . '; top: ' . (($top - 520) + ($i * 480)) . 'px; left: ' . ($left + 8) . 'px; height: 80px; width:200px;">';
        echo ancestor_chart_person($i + 1, 'large');
        echo '</div>';
    }

    $left = 80;
    $top = 199;
    // *** Third column split ***
    echo '<div class="ancestor_split" style="top: ' . $top . 'px; left: ' . ($left + 32) . 'px; height: 80px;"></div>';
    echo '<div class="ancestor_split" style="top: ' . ($top + 162) . 'px; left: ' . ($left + 32) . 'px; height: 80px;"></div>';
    echo '<div class="ancestor_split" style="top: ' . ($top + 480) . 'px; left: ' . ($left + 32) . 'px; height: 80px;"></div>';
    echo '<div class="ancestor_split" style="top: ' . ($top + 642) . 'px; left: ' . ($left + 32) . 'px; height: 80px;"></div>';
    // *** Third column names ***
    for ($i = 1; $i < 5; $i++) {
        $sexe_colour = '';
        $backgr_col = "#FFFFFF";
        //if ($sexe[$i+3] == 'F'){ $sexe_colour=' ancestor_woman'; }
        //if ($sexe[$i+3] == 'M'){ $sexe_colour=' ancestor_man'; }
        if ($sexe[$i + 3] == 'F') {
            $sexe_colour = ' ancestor_woman';
            $backgr_col = "#FBDEC0";
        }
        if ($sexe[$i + 3] == 'M') {
            $sexe_colour = ' ancestor_man';
            $backgr_col =  "#C0F9FC";
        }
        echo '<div class="ancestorName' . $sexe_colour . '" style="background-color:' . $backgr_col . '; top: ' . (($top - 279) + ($i * 240)) . 'px; left: ' . ($left + 40) . 'px; height: 80px; width:200px;">';
        echo ancestor_chart_person($i + 3, 'large');
        echo '</div>';
    }

    $left = 300;
    $top = -290;
    // *** Fourth column line ***
    for ($i = 1; $i < 3; $i++) {
        echo '<div class="ancestor_line" style="top: ' . ($top + ($i * 485)) . 'px; left: ' . ($left + 24) . 'px; height: 240px;"></div>';
    }
    // *** Fourth column split ***
    for ($i = 1; $i < 5; $i++) {
        echo '<div class="ancestor_split" style="top: ' . (($top + 185) + ($i * 240)) . 'px; left: ' . ($left + 32) . 'px; height: 120px;"></div>';
    }
    // *** Fourth column names ***
    for ($i = 1; $i < 9; $i++) {
        $sexe_colour = '';
        $backgr_col = "#FFFFFF";
        //if ($sexe[$i+7] == 'F'){ $sexe_colour=' ancestor_woman'; }
        //if ($sexe[$i+7] == 'M'){ $sexe_colour=' ancestor_man'; }
        if ($sexe[$i + 7] == 'F') {
            $sexe_colour = ' ancestor_woman';
            $backgr_col = "#FBDEC0";
        }
        if ($sexe[$i + 7] == 'M') {
            $sexe_colour = ' ancestor_man';
            $backgr_col =  "#C0F9FC";
        }
        echo '<div class="ancestorName' . $sexe_colour . '" style="background-color:' . $backgr_col . '; top: ' . (($top + 265) + ($i * 120)) . 'px; left: ' . ($left + 40) . 'px; height: 80px; width:200px;">';
        echo ancestor_chart_person($i + 7, 'large');
        echo '</div>';
    }

    $left = 520;
    $top = -110;
    // *** Fifth column line ***
    for ($i = 1; $i < 5; $i++) {
        echo '<div class="ancestor_line" style="top: ' . ($top + ($i * 240)) . 'px; left: ' . ($left + 24) . 'px; height: 120px;"></div>';
    }
    // *** Fifth column split ***
    for ($i = 1; $i < 9; $i++) {
        echo '<div class="ancestor_split" style="top: ' . (($top + 90) + ($i * 120)) . 'px; left: ' . ($left + 32) . 'px; height: 60px;"></div>';
    }
    // *** Fifth column names ***
    for ($i = 1; $i < 17; $i++) {
        $sexe_colour = '';
        $backgr_col = "#FFFFFF";
        //if ($sexe[$i+15] == 'F'){ $sexe_colour=' ancestor_woman'; }
        //if ($sexe[$i+15] == 'M'){ $sexe_colour=' ancestor_man'; }
        if ($sexe[$i + 15] == 'F') {
            $sexe_colour = ' ancestor_woman';
            $backgr_col = "#FBDEC0";
        }
        if ($sexe[$i + 15] == 'M') {
            $sexe_colour = ' ancestor_man';
            $backgr_col =  "#C0F9FC";
        }
        echo '<div class="ancestorName' . $sexe_colour . '" style="background-color:' . $backgr_col . '; top: ' . (($top + 125) + ($i * 60)) . 'px; left: ' . ($left + 40) . 'px; height: 50px; width:200px;">';
        echo ancestor_chart_person($i + 15, 'medium');
        echo '</div>';
    }

    $left = 740;
    $top = -20;
    // *** Last column line ***
    for ($i = 1; $i < 9; $i++) {
        echo '<div class="ancestor_line" style="top: ' . ($top + ($i * 120)) . 'px; left: ' . ($left + 24) . 'px; height: 60px;"></div>';
    }
    // *** Last column split ***
    for ($i = 1; $i < 17; $i++) {
        echo '<div class="ancestor_split" style="top: ' . (($top + 45) + ($i * 60)) . 'px; left: ' . ($left + 32) . 'px; height: 30px;"></div>';
    }
    // *** Last column names ***
    for ($i = 1; $i < 33; $i++) {
        $sexe_colour = '';
        $backgr_col = "#FFFFFF";
        //if ($sexe[$i+31] == 'F'){ $sexe_colour=' ancestor_woman'; }
        //if ($sexe[$i+31] == 'M'){ $sexe_colour=' ancestor_man'; }
        if ($sexe[$i + 31] == 'F') {
            $sexe_colour = ' ancestor_woman';
            $backgr_col = "#FBDEC0";
        }
        if ($sexe[$i + 31] == 'M') {
            $sexe_colour = ' ancestor_man';
            $backgr_col =  "#C0F9FC";
        }
        echo '<div class="ancestorName' . $sexe_colour . '" style="background-color:' . $backgr_col . '; top: ' . (($top + 66) + ($i * 30)) . 'px; left: ' . ($left + 40) . 'px; height:16px; width:200px;">';
        echo ancestor_chart_person($i + 31, 'small');
        echo '</div>';
    }
    echo '</div>';
    echo '<div>';

    // YB:
    // before creating the image we want to hide unnecessary items such as the help link, the menu box etc
    // we also have to set the width and height of the "png" div (this can't be set before because then the double scrollbars won't work
    // after generating the image, all those items are returned to their  previous state....
    // *** 19-08-2022: script updated by Huub ***
    echo '<script>';
    echo "
    function showimg() {
        /*   document.getElementById('helppopup').style.visibility = 'hidden';
        document.getElementById('menubox').style.visibility = 'hidden'; */
         document.getElementById('imgbutton').style.visibility = 'hidden';
        document.getElementById('png').style.width = '" . $divlen . "px';
        document.getElementById('png').style.height= 'auto';

        // *** Change ancestorName class, DO NOT USE A _ CHARACTER IN CLASS NAME ***
        const el = document.querySelectorAll('.ancestorName');
        el.forEach((elItem) => {
            //elItem.style.setProperty('border-radius', 'none', 'important');
            //elItem.style.setProperty('border-radius', '0px', 'important');
            elItem.style.setProperty('box-shadow', 'none', 'important');
        });

        //html2canvas( [ document.getElementById('png') ], {
        //	onrendered: function( canvas ) {

        html2canvas(document.querySelector('#png')).then(canvas => {
                var img = canvas.toDataURL();
                /*   document.getElementById('helppopup').style.visibility = 'visible';
                document.getElementById('menubox').style.visibility = 'visible'; */
                document.getElementById('imgbutton').style.visibility = 'visible';
                document.getElementById('png').style.width = 'auto';
                document.getElementById('png').style.height= 'auto';
                var newWin = window.open();
                newWin.document.open();
                newWin.document.write('<!DOCTYPE html><head></head><body>" . __('Right click on the image below and save it as a .png file to your computer.<br>You can then print it over multiple pages with dedicated third-party programs, such as the free: ') . "<a href=\"http://posterazor.sourceforge.net/index.php?page=download&lang=english\" target=\"_blank\">\"PosteRazor\"</a><br>" . __('If you have a plotter you can use its software to print the image on one large sheet.') . "<br><br><img src=\"' + img + '\"></body></html>');
                newWin.document.close();
                }
        //}
        );
    }
    ";
    echo '</script>';

?>
    <script>
        function DoubleScroll(element) {
            var scrollbar = document.createElement('div');
            scrollbar.appendChild(document.createElement('div'));
            scrollbar.style.overflow = 'auto';
            scrollbar.style.overflowY = 'hidden';
            scrollbar.firstChild.style.width = element.scrollWidth + 'px';
            scrollbar.firstChild.style.paddingTop = '1px';
            scrollbar.firstChild.style.height = '20px';
            scrollbar.firstChild.appendChild(document.createTextNode('\xA0'));
            scrollbar.onscroll = function() {
                element.scrollLeft = scrollbar.scrollLeft;
            };
            element.onscroll = function() {
                scrollbar.scrollLeft = element.scrollLeft;
            };
            element.parentNode.insertBefore(scrollbar, element);
        }

        DoubleScroll(document.getElementById('doublescroll'));
    </script>
<?php
}   // end of ancestor CHART code