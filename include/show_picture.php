<?php
// *** Function to show media by person or by marriage ***
// *** Updated feb 2013, aug 2015, feb 2023. ***
function show_media($event_connect_kind, $event_connect_id)
{
    global $dbh, $db_functions, $tree_id, $user, $dataDb, $uri_path;
    global $sect, $screen_mode; // *** RTF Export ***
    global $data, $page;

    $templ_person = array(); // local version
    $process_text = '';
    $media_nr = 0;

    // *** Pictures/ media ***
    //if ($user['group_pictures'] == 'j' and $data["picture_presentation"] != 'hide') {
    if ($user['group_pictures'] == 'j' && isset($data["picture_presentation"]) && $data["picture_presentation"] != 'hide') {
        $tree_pict_path = $dataDb->tree_pict_path;

        // *** Use default folder: media ***
        if (substr($tree_pict_path, 0, 1) === '|') {
            $tree_pict_path = 'media/';
        }

        //TODO check PDF code
        if ($screen_mode == 'PDF') {
            $tree_pict_path = __DIR__ . '/../' . $tree_pict_path;
        }

        // *** Standard connected media by person and family ***
        $picture_qry = $dbh->query("SELECT * FROM humo_events WHERE event_tree_id='" . $tree_id . "'
            AND event_connect_kind='" . safe_text_db($event_connect_kind) . "'
            AND event_connect_id='" . safe_text_db($event_connect_id) . "'
            AND LEFT(event_kind,7)='picture'
            ORDER BY event_kind, event_order");
        while ($pictureDb = $picture_qry->fetch(PDO::FETCH_OBJ)) {
            $media_nr++;
            $media_event_id[$media_nr] = $pictureDb->event_id;
            $media_event_event[$media_nr] = $pictureDb->event_event;
            $media_event_date[$media_nr] = $pictureDb->event_date;
            $media_event_place[$media_nr] = $pictureDb->event_place;
            $media_event_text[$media_nr] = $pictureDb->event_text;
            // *** Remove last seperator ***
            if ($media_event_text[$media_nr] && substr(rtrim($media_event_text[$media_nr]), -1) === "|") {
                $media_event_text[$media_nr] = substr($media_event_text[$media_nr], 0, -1);
            }
            //$media_event_source[$media_nr]=$pictureDb->event_source;
        }

        // *** Search for all external connected objects by a person, family or source ***
        if ($event_connect_kind == 'person') {
            $connect_sql = $db_functions->get_connections_connect_id('person', 'pers_object', $event_connect_id);
        } elseif ($event_connect_kind == 'family') {
            $connect_sql = $db_functions->get_connections_connect_id('family', 'fam_object', $event_connect_id);
        } elseif ($event_connect_kind == 'source') {
            $connect_sql = $db_functions->get_connections_connect_id('source', 'source_object', $event_connect_id);
        }

        if ($event_connect_kind == 'person' || $event_connect_kind == 'family' || $event_connect_kind == 'source') {
            foreach ($connect_sql as $connectDb) {
                $picture_qry = $dbh->query("SELECT * FROM humo_events WHERE event_tree_id='" . $tree_id . "'
                    AND event_gedcomnr='" . safe_text_db($connectDb->connect_source_id) . "' AND event_kind='object'
                    ORDER BY event_order");
                while ($pictureDb = $picture_qry->fetch(PDO::FETCH_OBJ)) {
                    $media_nr++;
                    $media_event_id[$media_nr] = $pictureDb->event_id;
                    $media_event_event[$media_nr] = $pictureDb->event_event;
                    $media_event_date[$media_nr] = $pictureDb->event_date;
                    $media_event_place[$media_nr] = $pictureDb->event_place;
                    $media_event_text[$media_nr] = $pictureDb->event_text;
                    // *** Remove last seperator ***
                    if (substr(rtrim($media_event_text[$media_nr]), -1) === "|") {
                        $media_event_text[$media_nr] = substr($media_event_text[$media_nr], 0, -1);
                    }
                    //$media_event_source[$media_nr]=$pictureDb->event_source;
                }
            }
        }

        // ******************
        // *** Show media ***
        // ******************
        if ($media_nr > 0) {
            if ($screen_mode == "RTF") {
                $process_text .= "\n";
            } else {
                $process_text .= '<br>';
            }
        }

        $picpath = $uri_path;

        for ($i = 1; $i < ($media_nr + 1); $i++) {
            $date_place = date_place($media_event_date[$i], $media_event_place[$i]);
            // *** If possible show a thumb ***

            // *** Don't use entities in a picture ***
            //$event_event = html_entity_decode($pictureDb->event_event, ENT_NOQUOTES, 'ISO-8859-15');
            $event_event = $media_event_event[$i];

            // in case subfolders are made for photobook categories and this was not already set in $picture_path, look there
            // (if the $picture_path is already set with subfolder this anyway gives false and so the $picture_path given will work)
            $temp_path = $tree_pict_path; // store original so we can reset after using for subfolder path for this picture.

            $temp = $dbh->query("SHOW TABLES LIKE 'humo_photocat'");
            if ($temp->rowCount()) {   // there is a category table 
                $catg = $dbh->query("SELECT photocat_prefix FROM humo_photocat WHERE photocat_prefix != 'none' GROUP BY photocat_prefix");
                if ($catg->rowCount()) {
                    while ($catDb = $catg->fetch(PDO::FETCH_OBJ)) {
                        if (substr($event_event, 0, 3) == $catDb->photocat_prefix && is_dir($tree_pict_path . '/' . substr($event_event, 0, 2))) {  // there is a subfolder of this prefix
                            $tree_pict_path .= substr($event_event, 0, 2) . '/';  // look in that subfolder
                        }
                    }
                }
            }

            // *** In some cases the picture name must be converted to lower case ***
            if (file_exists($tree_pict_path . strtolower($event_event))) {
                $event_event = strtolower($event_event);
            }

            // *** Check for PDF, DOC file etc. Show standard icon ***
            $thumbnail_type = thumbnail_type($event_event);
            if ($thumbnail_type[0]) {
                $picture = '<a href="' . $tree_pict_path . $event_event . '"><img src="' . $picpath . $thumbnail_type[0] . '" alt="' . $thumbnail_type[1] . '"></a>';
            } else {
                // *** Show photo using the lightbox effect ***
                $picture_array = show_picture($tree_pict_path, $event_event, '', 120);
                // *** lightbox can't handle brackets etc so encode it. ("urlencode" doesn't work since it changes spaces to +, so we use rawurlencode)
                // *** But: reverse change of / character (if sub folders are used) ***
                //$picture_array['picture'] = rawurlencode($picture_array['picture']);
                $picture_array['picture'] = str_ireplace("%2F", "/", rawurlencode($picture_array['picture']));

                $line_pos = 0;
                if ($media_event_text[$i]) {
                    $line_pos = strpos($media_event_text[$i], "|");
                }
                $title_txt = $media_event_text[$i];
                if ($line_pos > 0) {
                    $title_txt = substr($media_event_text[$i], 0, $line_pos);
                }

                // *** April 2021: using GLightbox ***
                //$picture='<a href="'.$picture_array['path'].$picture_array['picture'].'" data-glightbox="title: Title; description: '.str_replace("&", "&amp;", $title_txt).'" class="glightbox3" data-gallery="gallery'.$event_connect_id.'">';
                //$picture='<a href="'.$picture_array['path'].$picture_array['picture'].'" data-glightbox="description: '.str_replace("&", "&amp;", $title_txt).'" class="glightbox3" data-gallery="gallery'.$event_connect_id.'">';

                $picture = '<a href="' . $picture_array['path'] . $picture_array['picture'] . '" class="glightbox3" data-gallery="gallery' . $event_connect_id . '" data-glightbox="description: .custom-desc' . $media_event_id[$i] . '">';
                // *** Need a class for multiple lines and HTML code in a text ***
                $picture .= '<div class="glightbox-desc custom-desc' . $media_event_id[$i] . '">';
                if ($date_place) {
                    $picture .= $date_place . '<br>';
                }
                $picture .= $title_txt . '</div>';

                $picture .= '<img src="' . $picture_array['path'] . $picture_array['thumb'] . $picture_array['picture'] . '" height="' . $picture_array['height'] . '" alt="' . $event_event . '"></a>';
                //$picture.='<img src="'.$picture_array['path'].$picture_array['thumb'].$picture_array['picture'].'" width="'.$picture_array['width'].'" alt="'.$event_event.'"></a>';

                $templ_person["pic_path" . $i] = $picture_array['path'] . "thumb_" . $picture_array['picture']; //for the time being pdf only with thumbs
                // *** Remove spaces ***
                $templ_person["pic_path" . $i] = trim($templ_person["pic_path" . $i]);
            }

            // *** Show picture date and place ***
            $picture_text = '';
            if ($media_event_date[$i] || $media_event_place[$i]) {
                if ($screen_mode != 'RTF') {
                    $picture_text = $date_place . ' ';
                }
                $templ_person["pic_text" . $i] = $date_place;
            }

            // *** Show text by picture of little space ***
            if (isset($media_event_text[$i]) && $media_event_text[$i]) {
                if ($screen_mode != 'RTF') {
                    //$picture_text.=' '.str_replace("&", "&amp;", $media_event_text[$i]);
                    $picture_text .= ' ' . str_replace("&", "&amp;", process_text($media_event_text[$i]));
                }
                if (isset($templ_person["pic_text" . $i])) {
                    $templ_person["pic_text" . $i] .= ' ' . $media_event_text[$i];
                } else {
                    $templ_person["pic_text" . $i] = $media_event_text[$i];
                }
            }

            if ($screen_mode != 'RTF') {
                // Jan. 2024: Don't connect a source to a picture if source page is shown.
                if ($page != 'source') {
                    // *** Show source by picture ***
                    $source_array = '';
                    if ($event_connect_kind == 'person') {
                        $source_array = show_sources2("person", "pers_event_source", $media_event_id[$i]);
                    } else {
                        $source_array = show_sources2("family", "fam_event_source", $media_event_id[$i]);
                    }
                    if ($source_array) {
                        $picture_text .= $source_array['text'];
                    }
                }

                $process_text .= '<div class="photo">';
                $process_text .= $picture;
                if (isset($picture_array['picture']) && $picture_array['picture'] == 'missing-image.jpg') {
                    $picture_text .= '<br><b>' . __('Missing image') . ':<br>' . $tree_pict_path . $event_event . '</b>';
                }
                // *** Show text by picture ***
                if (isset($picture_text)) {
                    $process_text .= '<div class="phototext">' . $picture_text . '</div>';
                }
                $process_text .= '</div>' . "\n";
            }

            // reset path back to original in case was used for subfolder
            $tree_pict_path = $temp_path;
        }

        if ($media_nr > 0) {
            $process_text .= '<br clear="All">';
            $templ_person["got_pics"] = 1;
        }
    }
    //return $process_text;
    $result[0] = $process_text;
    $result[1] = $templ_person; // local version with pic data
    return $result;
}

// *** Function to show a picture in several places ***
// *** Made by Huub Mons sept. 2011/ update aug. 2014 ***
// Example:
// $picture=show_picture($tree_pict_path,$pictureDb->event_event,'',120);
// $popup.='<img src="'.$picture['path'].$picture['thumb'].$picture['picture'].'" style="margin-left:50px; margin-top:5px;" alt="'.$pictureDb->event_text.'" height="'.$picture['height'].'">';

function show_picture($picture_path, $picture_org, $pict_width = '', $pict_height = '')
{
    global $dbh, $screen_mode, $uri_path;
    // in case subfolders are made for photobook categories and this was not already set in $picture_path, look there
    // in cases where the $picture_path is already set with subfolder this anyway gives false and so the $picture_path gives will work
    $temp = $dbh->query("SHOW TABLES LIKE 'humo_photocat'");
    if ($temp->rowCount()) {  // there is a category table 
        $cat1 = $dbh->query("SELECT photocat_prefix FROM humo_photocat WHERE photocat_prefix != 'none' GROUP BY photocat_prefix");
        if ($cat1->rowCount()) {
            while ($catDb = $cat1->fetch(PDO::FETCH_OBJ)) {
                if (substr($picture_org, 0, 3) == $catDb->photocat_prefix && is_dir($picture_path . '/' . substr($picture_org, 0, 2))) {  // there is a subfolder of this prefix
                    $picture_path .= substr($picture_org, 0, 2) . '/';  // look in that subfolder
                }
            }
        }
    }

    $picture["path"] = $picture_path; // *** Standard picture path. Will be overwritten if picture is removed ***
    $picture["picture"] = $picture_org;
    $found_picture = false; // *** Check if picture still exists ***

    // *** In some cases the picture name must be converted to lower case ***
    if (file_exists($picture["path"] . strtolower($picture['picture']))) {
        $found_picture = true;
        $picture['picture'] = strtolower($picture['picture']);
    }
    // *** Picture ***
    if (file_exists($picture["path"] . $picture['picture'])) {
        $found_picture = true;
    }

    $picture['thumb'] = '';
    // *** Lowercase thumbnail ***
    if (file_exists($picture["path"] . 'thumb_' . strtolower($picture['picture']))) {
        $found_picture = true;
        $picture['thumb'] = 'thumb_';
        $picture['picture'] = strtolower($picture['picture']);
    }
    // *** Thumbnail ***
    if (file_exists($picture["path"] . 'thumb_' . $picture['picture'])) {
        $found_picture = true;
        $picture['thumb'] = 'thumb_';
    }

    // *** Check if picture is in subdirectory ***
    // Example: subdir1_test/xy/2022_02_12 Scheveningen.jpg
    if ($picture['thumb'] === '') {
        $dirname = dirname($picture['picture']); // subdir1_test/xy/2022_02_12
        $basename = basename($picture['picture']); // 2022_02_12 Scheveningen.jpg
        if (file_exists($picture["path"] . $dirname . '/thumb_' . $basename)) {
            $picture["path"] = $picture["path"] . $dirname . '/'; // *** Add subdirectory to path ***
            $picture['thumb'] = 'thumb_';
            $picture['picture'] = $basename;
        }
    }

    // *** No picture selected yet (in editor) ***
    if (!$picture['picture']) {
        $picture['path'] = 'images/';
        if ($screen_mode == 'PDF' || $screen_mode == 'RTF') {
            $picture['path'] = __DIR__ . '/../images/';
        }
        $picture['thumb'] = 'thumb_';
        $picture['picture'] = 'missing-image.jpg';
    }

    if (!$found_picture) {
        $picture['path'] = 'images/';
        if ($screen_mode == 'PDF' || $screen_mode == 'RTF') {
            $picture['path'] = __DIR__ . '/../images/';
        }
        $picture['thumb'] = 'thumb_';
        $picture['picture'] = 'missing-image.jpg';
    }

    // *** Check for PDF, DOC file etc. Show standard icon ***
    $thumbnail_type = thumbnail_type($picture['picture']);
    if ($thumbnail_type[0]) {
        $picture["path"] = '';
        $picture['thumb'] = '';
        $picture['picture'] = $uri_path . $thumbnail_type[0];
    }

    // *** If photo is too wide, correct the size ***
    list($width, $height) = getimagesize($picture["path"] . $picture['thumb'] . $picture['picture']);

    if ($pict_width > 0 && $pict_height > 0) {
        /*
        // *** Change width and height ***
        $factor=$height/$pict_height;
        $picture['width']=floor($width/$factor);

        // *** If picture is too width, resize it ***
        if ($picture['width']>$pict_width){
            $factor=$width/$pict_width;
            $picture['height']=floor($height/$factor);
        }
        */
        if ($width > $height) {
            // *** Width picture: change width and height ***
            $factor = $width / $pict_width;
            $picture['width'] = floor($width / $factor);
            //$picture['height']=floor($height/$factor);
        } else {
            // *** High picture ***
            $factor = $height / $pict_height;
            $picture['width'] = floor($width / $factor);
            //$picture['height']=floor($height/$factor);
        }
    } elseif ($pict_width > 0) {
        // *** Change width ***
        if ($width > $pict_width) {
            $width = 190;
        }
        //if ($width>$pict_width){ $width=$pict_width; }
        $picture['width'] = floor($width);
    } elseif ($pict_height > 0) {
        // *** Change height ***
        if ($height > $pict_height) {
            $height = 120;
        }
        //if ($height>$pict_height){ $height=$pict_height; }
        $picture['height'] = floor($height);
    }

    return $picture;
}

function thumbnail_type($file)
{
    // *** Show PDF file ***
    if (strtolower(substr($file, -3, 3)) === "pdf") {
        $thumbnail_type[0] = 'images/pdf.jpeg';
        $thumbnail_type[1] = 'PDF';
        return $thumbnail_type;
    }
    // *** Show DOC file ***
    elseif (strtolower(substr($file, -3, 3)) === "doc" || substr($file, -4, 4) === "docx") {
        $thumbnail_type[0] = 'images/msdoc.gif';
        $thumbnail_type[1] = 'DOC';
        return $thumbnail_type;
    }
    // *** Show AVI Video file ***
    elseif (strtolower(substr($file, -3, 3)) === "avi") {
        $thumbnail_type[0] = 'images/video-file.png';
        $thumbnail_type[1] = 'AVI';
        return $thumbnail_type;
    }
    // *** Show WMV Video file ***
    elseif (strtolower(substr($file, -3, 3)) === "wmv") {
        $thumbnail_type[0] = 'images/video-file.png';
        $thumbnail_type[1] = 'WMV';
        return $thumbnail_type;
    }
    // *** Show MPG Video file ***
    elseif (strtolower(substr($file, -3, 3)) === "mpg") {
        $thumbnail_type[0] = 'images/video-file.png';
        $thumbnail_type[1] = 'MPG';
        return $thumbnail_type;
    }
    // *** Show MP4 Video file ***
    elseif (strtolower(substr($file, -3, 3)) === "mp4") {
        $thumbnail_type[0] = 'images/video-file.png';
        $thumbnail_type[1] = 'MP4';
        return $thumbnail_type;
    }
    // *** Show MOV Video file ***
    elseif (strtolower(substr($file, -3, 3)) === "mov") {
        $thumbnail_type[0] = 'images/video-file.png';
        $thumbnail_type[1] = 'MOV';
        return $thumbnail_type;
    }
    // *** Show WMA Audio file ***
    elseif (strtolower(substr($file, -3, 3)) === "wma") {
        $thumbnail_type[0] = 'images/audio.gif';
        $thumbnail_type[1] = 'WMA';
        return $thumbnail_type;
    }
    // *** Show MP3 Audio file ***
    elseif (strtolower(substr($file, -3, 3)) === "mp3") {
        $thumbnail_type[0] = 'images/audio.gif';
        $thumbnail_type[1] = 'MP3';
        return $thumbnail_type;
    }
    // *** Show WAV Audio file ***
    elseif (strtolower(substr($file, -3, 3)) === "wav") {
        $thumbnail_type[0] = 'images/audio.gif';
        $thumbnail_type[1] = 'WAV';
        return $thumbnail_type;
    }
    // *** Show MID Audio file ***
    elseif (strtolower(substr($file, -3, 3)) === "mid") {
        $thumbnail_type[0] = 'images/audio.gif';
        $thumbnail_type[1] = 'MID';
        return $thumbnail_type;
    }
    // *** Show RAM Audio file ***
    elseif (strtolower(substr($file, -3, 3)) === "ram") {
        $thumbnail_type[0] = 'images/audio.gif';
        $thumbnail_type[1] = 'RAM';
        return $thumbnail_type;
    }
    // *** Show RA Audio file ***
    elseif (strtolower(substr($file, -2, 2)) === "ra") {
        $thumbnail_type[0] = 'images/audio.gif';
        $thumbnail_type[1] = 'RA';
        return $thumbnail_type;
    }

    $thumbnail_type[0] = '';
    $thumbnail_type[1] = '';
    return $thumbnail_type;
}
