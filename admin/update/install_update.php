<?php
// *** Safety line ***
if (!defined('ADMIN_PAGE')) {
    exit;
}

@set_time_limit(300);
?>

<h1 class="center">
    <?php printf(__('%s Update'), 'HuMo-genealogy'); ?>
</h1>

<?php
// *** Installation of beta version ***
if (isset($_GET['install_beta'])) {
    $update['up_to_date'] = 'no';
    $update['version_auto_download'] = $update['beta_version_auto_download'];
}

// *** Re-install some or all files ***
if (isset($_GET['re_install'])) {
    $update['up_to_date'] = 'no';
}

if (isset($update['up_to_date']) && $update['up_to_date'] == 'yes') {
    // *** Show HuMo-genealogy version number ***
    echo '<br><br><h2>HuMo-genealogy</h2>';
    echo __('Version:') . ' ';
    if (isset($humo_option["version"])) {
        echo $humo_option["version"] . '.';
    } else {
        echo __('no version number available...');
    }
    echo ' ';
    printf(__('%s is up-to-date!'), 'HuMo-genealogy');
    echo ' <a href="' . $path_tmp . 'page=install_update&re_install=1&auto=1&update_check=1">';
    printf(__('If needed: re-install all or some of the %s files'), 'HuMo-genealogy');
    echo '</a>';
    echo '<br><br>';

    // *** Check for HuMo-genealogy beta version ***
    echo '<br><br><h2>';
    printf(__('%s beta version'), 'HuMo-genealogy');
    echo '</h2>';
    echo __('Sometimes there is a beta version available.');
    if (strtotime($update['beta_version_date']) - strtotime($humo_option["version_date"]) > 0) {
        echo ' <a href="' . $path_tmp . 'page=install_update&install_beta=1&auto=1&update_check=1">';
        printf(__('%s beta version available'), 'HuMo-genealogy');
        echo ' (' . $update['beta_version'] . ')!</a>';
    } else {
        echo ' ' . __('No beta version available.');
    }
    echo '<br><br>';

    // *** Check for HuMo-genealogy beta version SAME CODE AS CODE BELOW... ***
    $check = ' checked';
    if ($humo_option['update_last_check'] == 'DISABLED') {
        $check = '';
    }
    echo '<br><br><h2>';
    printf(__('Enable/ disable %s update check.'), 'HuMo-genealogy');
    echo '</h2>';
?>

    <form method="post" action="index.php?page=install_update&update_check=1" style="display : inline">
        <input type="checkbox" name="enable_update_check" <?= $check; ?> onChange="this.form.submit();">
        <?php printf(__('Check regularly for %s updates.'), 'HuMo-genealogy'); ?><br>
        <input type="hidden" name="enable_update_check_change" value="1">
    </form>

    <?php
    // *** Debug update check ***
    $check = ' checked';
    if ($humo_option['update_last_check'] == 'DISABLED') {
        $check = '';
    }
    echo '<br><br><h2>';
    printf(__('Debug %s update'), 'HuMo-genealogy');
    echo '</h2>';
    echo '<form method="post" action="index.php?page=install_update&update_check=1" style="display : inline">';
    echo '<input type="submit" name="debug_update" value="' . __('Debug') . '" class="btn btn-secondary btn-sm">';
    echo '</form>';
    echo '<br><br>';
} elseif (isset($update['up_to_date']) && $update['up_to_date'] == 'no') {

    if (isset($_GET['auto'])) {
        echo '<h2>' . __('Automatic update') . '</h2>';

        echo __('Every step can take some time, please be patient and wait till the step is completed!') . '<br><br>';


        /*
        // Problem: HuMo-genealogy = 11 MB. Could be too large to upload for some providers in standard form without changing parameters.

        // *** Only upload .ged or .zip files ***
        if (isset($_POST['optional_upload'])) {
            if (strtolower(substr($_FILES['upload_file']['name'], -4)) == '.zip' or strtolower(substr($_FILES['upload_file']['name'], -4)) == '.ged') {
                $new_upload = 'update/humo-gen_update.zip';
                // *** Move and check for succesful upload ***
                echo '<p><b>' . $new_upload . '<br>';
                if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $new_upload))
                    echo __('File successfully uploaded.') . '</b>';
                else
                    echo __('Upload has failed.') . '</b>';
            }
        }

        $reinstall = '';
        if (isset($_GET['re_install'])) {
            $reinstall = '&amp;re_install=1';
        }

        <?= __('Optional: manually upload new version.'); ?>
        <?php printf(__('First download latest %s version from Github or Sourceforge.'), 'HuMo-genealogy'); ?>

        <form name='uploadform' enctype='multipart/form-data' action="<?= $path_tmp; ?>page=install_update&auto=1&step=1&update_check=1<?= $reinstall; ?>" method="post">
            <input type="file" name="upload_file">
            <input type="submit" name="optional_upload" value="Upload">
        </form><br>
*/


        printf(__('Step 1) Download and unzip new %s version'), 'HuMo-genealogy');
        echo '<br>';

        if (!isset($_GET['step'])) {
            echo '<a href="' . $path_tmp . 'page=install_update&auto=1&step=1&update_check=1';
            if (isset($_GET['install_beta'])) {
                echo '&install_beta=1';
            }
            if (isset($_GET['re_install'])) {
                echo '&re_install=1';
            }
            echo '">';
            printf(__('Download and unzip new %s version'), 'HuMo-genealogy');
            echo '</a><br>';

            echo '<h2>';
            printf(__('%s version history'), 'HuMo-genealogy');
            echo '</h2>';

            echo '<p><iframe height="300" width="80%" src="https://humo-gen.com/genforum/viewforum.php?f=19"></iframe>';
        }

        // *** STEP 1: Download humo-genealogy.zip and unzip to update folder ***
        if (isset($_GET['step']) && $_GET['step'] == '1') {
            $download = false;

            // *** Check update folder permissions ***
            if (!is_writable('update')) {
                echo '<b>ERROR: Folder admin/update is NOT WRITABLE. Please change permissions.</b><br>';
            }

            // *** No download of software update, manually put in update folder! ***
            if (file_exists('update/humo-gen_update.zip')) {
                echo '<b>' . __('Found update file, skipped download of update!') . '</b><br>';
                $download = true;
            } else {
                // *** Copy HuMo-genealogy update to server using curl ***
                if (function_exists('curl_exec')) {
                    // *** First try to download from GitHub ***
                    //$source='https://github.com/HuubMons/HuMo-genealogy/archive/refs/heads/master.zip';
                    $source = $update['new_version_auto_download_github'];
                    $destination = 'update/humo-gen_update.zip';
                    $resource = curl_init();
                    curl_setopt($resource, CURLOPT_URL, $source);

                    curl_setopt($resource, CURLOPT_HEADER, false);
                    /*
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Accept: application/vnd.github+json',
                        'Authorization: Bearer <YOUR-TOKEN>',
                        'X-GitHub-Api-Version: 2022-11-28',
                        'User-Agent: Awesome-App'
                    ]);
                    */

                    curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($resource, CURLOPT_CONNECTTIMEOUT, 30);

                    // *** Nov 2023: Don't use CURL cache ***
                    curl_setopt($resource, CURLOPT_FRESH_CONNECT, true); // don't use a cached version of the url

                    // *** Added for GitHub ***
                    curl_setopt($resource, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($resource, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

                    $content = curl_exec($resource);
                    curl_close($resource);
                    if ($content != '') {
                        $fp = fopen($destination, 'w');
                        $fw = fwrite($fp, $content); //returns false on failure
                        fclose($fp);
                        if ($fw != false) {
                            $download = true;
                        }
                    }

                    // TEST
                    /*
                    $curl = curl_init();
                    $fp = fopen($destination, "w+");
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($curl, CURLOPT_FILE, $fp);
                    curl_setopt($curl, CURLOPT_URL, $source);
                    curl_exec ($curl);
                    curl_close($curl);
                    fclose($fp);

                    // Er is een LEEG zip bestand...
                    if (is_file($destination)) $download = true;

                    end; //test
                    */

                    //You can use CURLOPT_FILE to directly download to a file, just set that option to a previously
                    // opened file pointer created with fopen($filename, 'w').

                    /*
                    # Open the file for writing...
                    $GlobalFileHandle = fopen($destination, 'w+');
                    curl_setopt($resource, CURLOPT_FILE, $GlobalFileHandle);

                    # Assign a callback function to the CURL Write-Function
                    curl_setopt($resource, CURLOPT_WRITEFUNCTION, 'curlWriteFile');

                    # Execute the download - note we DO NOT put the result into a variable!
                    curl_exec($resource);

                    # Close CURL
                    curl_close($resource);

                    # Close the file pointer
                    fclose($GlobalFileHandle);

                    if (is_file($destination)) $download = true;
                    */

                    // *** Download failed from Github, now try humo-gen.com ***
                    if ($download == false) {
                        // TODO translate?
                        echo '<b>Download from Github failed. Now trying to download from HuMo-genealogy website.</b><br>';

                        $source = $update['version_auto_download'];
                        $destination = 'update/humo-gen_update.zip';
                        $resource = curl_init();
                        curl_setopt($resource, CURLOPT_URL, $source);
                        curl_setopt($resource, CURLOPT_HEADER, false);
                        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($resource, CURLOPT_CONNECTTIMEOUT, 30);
                        $content = curl_exec($resource);
                        curl_close($resource);
                        if ($content != '') {
                            $fp = fopen($destination, 'w');
                            $fw = fwrite($fp, $content);
                            fclose($fp);
                            if ($fw != false) {
                                $download = true;
                            }
                        }
                    }
                }
                // *** copy HuMo-genealogy to server using copy ***
                else {
                    // TODO translate?
                    echo '<b>Option curl is disabled. Now trying to copy file from HuMo-genealogy website.</b><br>';

                    if (copy($update['version_auto_download'], 'update/humo-gen_update.zip')) {
                        $download = true;
                    }
                }
            }

            if ($download == false) {
                echo 'ERROR: automatic download failed...<br>';
            } else {
                if (is_file('update/humo-gen_update.zip')) {
                    echo __('Automatic download successfull!') . '<br>';
                } else {
                    echo __('Automatic download of file failed.') . '<br>';
                    exit;
                }

                // *** Unzip downloaded file ***
                $zip = new ZipArchive;
                if ($zip->open("update/humo-gen_update.zip") === TRUE) {
                    $zip->extractTo('update/humo-gen_update');
                    $zip->close();
                    echo __('File successfully unzipped!') . '<br>';

                    // *** July 2022: Archive from GitHub contains master folder ***
                    // Change folder: update/humo-gen_update/HuMo-genealogy-master
                    // Into: update/humo-gen_update
                    if (is_dir('update/humo-gen_update/HuMo-genealogy-master')) {
                        rename('update/humo-gen_update', 'update/humo-gen_update_temp');
                        rename('update/humo-gen_update_temp/HuMo-genealogy-master', 'update/humo-gen_update');
                        rmdir('update/humo-gen_update_temp');
                    }

                    echo '<br>' . __('Step 2)') . ' <a href="' . $path_tmp . 'page=install_update&auto=1&step=2&update_check=1';
                    if (isset($_GET['install_beta'])) {
                        echo '&install_beta=1';
                    }
                    if (isset($_GET['re_install'])) {
                        echo '&re_install=1';
                    }
                    echo '">' . __('Check files') . '</a><br>';
                } else {
                    echo 'ERROR: unzip failed!<br>';

                    unlink('update/humo-gen_update.zip');
                    echo 'Update failed, update file is removed.<br>';
                }
            }
        }

        // *** STEP 2: check files ***
        if (isset($_GET['step']) && ($_GET['step'] == '2' || $_GET['step'] == '3')) {
            echo '<br>' . __('Step 2) Compare existing and update files (no installation yet)...') . '<br>';

            if ($_GET['step'] == '3') {
                echo '<br>' . __('Step 3) Installation of new files...') . '<br>';
            }

            function listFolderFiles($dir, $exclude, $file_array)
            {
                global $existing_dir_files, $existing_dir, $existing_files;
                global $update_dir_files, $update_dir, $update_files;
                $ffs = scandir($dir);
                foreach ($ffs as $ff) {
                    if (is_array($exclude) and !in_array($ff, $exclude)) {
                        if ($ff != '.' && $ff != '..') {
                            // *** Skip all media files in folders ../media/, ../media/cms/ etc.
                            if (substr($dir, 0, 8) == '../media' and !is_dir($dir . '/' . $ff) and $ff != 'readme.txt') {
                                // skip media files
                            }
                            // *** Skip all backup files in folder backup_files/
                            // ../admin/backup_files/2023_02_11_09_56_humo-genealogy_backup.sql.zip
                            elseif (substr($dir, 0, 21) == '../admin/backup_files' and !is_dir($dir . '/' . $ff) and $ff != 'readme.txt') {
                                // skip backup files
                            } else {
                                if ($file_array == 'existing_files') {
                                    $existing_dir[] = $dir;
                                    $existing_files[] = $ff;
                                    $existing_dir_files[] = substr($dir . '/' . $ff, 3);
                                } else {
                                    $update_dir[] = $dir;
                                    $update_files[] = $ff;
                                    $update_dir_files[] = substr($dir . '/' . $ff, 25);
                                }
                                if (is_dir($dir . '/' . $ff)) listFolderFiles($dir . '/' . $ff, $exclude, $file_array);
                            }
                        }
                    }
                }
            }

            // *** Find all existing HuMo-genealogy files, skip humo-gen_update.zip, humo-gen_update folder and ip_files folders. ***
            listFolderFiles('..', array('humo-gen_update.zip', 'humo-gen_update', 'ip_files'), 'existing_files');

            // *** Find all update HuMo-genealogy files, a__ is just some random text (skip items)... ***
            listFolderFiles('./update/humo-gen_update', array('a__', 'a__'), 'update_files');

            echo '<form method="POST" action="' . $path_tmp . 'page=install_update&auto=1&step=3&update_check=1';
            if (isset($_GET['install_beta'])) {
                echo '&install_beta=1';
            }
            if (isset($_GET['re_install'])) {
                echo '&re_install=1';
            }
            echo '" style="display : inline;">';
            echo '<input type="hidden" name="page" value="' . $page . '">';
    ?>
            <div style="border:1px solid black;height:200px;width:700px;overflow:scroll">
                <?php
                // *** Compare new files with old files, show list of renewed files ***
                for ($i = 0; $i <= count($update_files) - 1; $i++) {
                    if (!is_dir($update_dir[$i] . '/' . $update_files[$i])) {
                        $key = array_search($update_dir_files[$i], $existing_dir_files);

                        $exist_sha1 = sha1_file($existing_dir[$key] . '/' . $existing_files[$key]);
                        $update_sha1 = sha1_file($update_dir[$i] . '/' . $update_files[$i]);
                        if ($exist_sha1 !== $update_sha1) {
                            $create_file = '../' . substr($update_dir[$i] . '/' . $update_files[$i], 25);

                            // *** Optional installation of file ***
                            $check = ' checked';
                            if ($_GET['step'] == '3') {
                                $check = '';
                                if (isset($_POST['install_file' . $i])) {
                                    $check = ' checked';
                                }
                            }
                            echo '<input type="Checkbox" name="install_file' . $i . '" value="yes"' . $check . '> ';
                            echo $create_file;

                            // *** Copy update file to existing file ***
                            if ($_GET['step'] == '3' && isset($_POST['install_file' . $i])) {
                                if (!copy($update_dir[$i] . '/' . $update_files[$i], $create_file)) {
                                    echo ' <b>' . __('Installation of file failed') . '</b>';
                                } else {
                                    echo ' ' . __('File installed!');
                                }
                            }
                            echo '<br>';
                        }
                    } else {
                        // *** Compare directory ***
                        $key = array_search($update_dir_files[$i], $existing_dir_files);
                        if ($key) {
                            //echo $update_dir[$i].'/'.$update_files[$i];
                            //echo 'OK';
                        } else {
                            $create_dir = '../' . substr($update_dir[$i] . '/' . $update_files[$i], 25);
                            echo $create_dir;
                            if ($_GET['step'] == '3' && mkdir($create_dir)) {
                                echo ' ' . __('Directory created.');
                            }
                            echo '<br>';
                        }
                    }
                }
                ?>
            </div><br>

            <?php
            printf(__('The following files are not part of the %s system files (anymore). If you want, they can be removed.'), 'HuMo-genealogy');

            echo '<br><a href="' . $path_tmp . 'page=install_update&auto=1&step=2&update_check=1';
            if (isset($_GET['install_beta'])) {
                echo '&install_beta=1';
            }
            if (isset($_GET['re_install'])) {
                echo '&re_install=1';
            }
            echo '&amp;select_all=1">' . __('Select all files (recommended option, please check these files first!)') . '</a><br>';
            ?>
            <div style="border:1px solid black;height:200px;width:700px;overflow:scroll">
                <?php
                // *** Compare new files with old files, show list of old system files ***
                //for ($i=0; $i<=count($existing_files)-1; $i++){
                // *** Skip first file (.htaccess) because of comparison problems ***
                for ($i = 1; $i <= count($existing_files) - 1; $i++) {
                    if (!is_dir($existing_dir[$i] . '/' . $existing_files[$i])) {
                        $key = array_search($existing_dir_files[$i], $update_dir_files);
                        if (!$key) {
                            $create_file = $existing_dir[$i] . '/' . $existing_files[$i];
                            // *** Optional removal of file ***
                            $check = '';
                            if (isset($_GET['select_all'])) {
                                $check = ' checked';
                            }
                            if ($_GET['step'] == '3' && isset($_POST['remove_file' . $i])) {
                                $check = ' checked';
                            }
                            echo '<input type="Checkbox" name="remove_file' . $i . '" value="yes"' . $check . '> ';
                            echo $create_file;

                            if ($_GET['step'] == '3' && isset($_POST['remove_file' . $i])) {
                                unlink($create_file);
                            }
                            echo '<br>';
                        }
                    }
                }
                ?>
            </div>
<?php
            if ($_GET['step'] == '3' && DATABASE_HOST) {
                echo '<br>' . __('Update new db_login.php file...') . '<br>';

                $login_file = "../include/db_login.php";
                if (!is_writable($login_file)) {
                    $result_message = '<b> *** ' . __('The configuration file is not writable! Please change the include/db_login.php file manually.') . ' ***</b>';
                } else {
                    // *** Read file ***
                    $handle = fopen($login_file, "r");
                    while (!feof($handle)) {
                        $buffer[] = fgets($handle, 4096);
                    }

                    // *** Write file ***
                    $check_config = false;
                    $bestand_config = fopen($login_file, "w");
                    for ($i = 0; $i <= (count($buffer) - 1); $i++) {
                        // *** Use ' character to prevent problems with $ character in password ***
                        //define("DATABASE_HOST",     'localhost');
                        //define("DATABASE_USERNAME", 'root');
                        //define("DATABASE_PASSWORD", '');
                        //define("DATABASE_NAME",     'humo-gen');

                        if (substr($buffer[$i], 8, 13) == 'DATABASE_HOST') {
                            $buffer[$i] = 'define("DATABASE_HOST",     ' . "'" . DATABASE_HOST . "');\n";
                            $check_config = true;
                        }

                        if (substr($buffer[$i], 8, 17) == 'DATABASE_USERNAME') {
                            $buffer[$i] = 'define("DATABASE_USERNAME", ' . "'" . DATABASE_USERNAME . "');\n";
                            $check_config = true;
                        }

                        if (substr($buffer[$i], 8, 17) == 'DATABASE_PASSWORD') {
                            $buffer[$i] = 'define("DATABASE_PASSWORD", ' . "'" . DATABASE_PASSWORD . "');\n";
                            $check_config = true;
                        }

                        if (substr($buffer[$i], 8, 13) == 'DATABASE_NAME') {
                            $buffer[$i] = 'define("DATABASE_NAME",     ' . "'" . DATABASE_NAME . "');\n";
                            $check_config = true;
                        }

                        fwrite($bestand_config, $buffer[$i]);
                    }
                    fclose($bestand_config);
                    if ($check_config == false) {
                        $result_message = '<b> *** ' . __('There is a problem in the db_login file, maybe an old db_login file is used.') . ' ***</b>';
                    } else {
                        $result_message = __('File is updated.');
                    }
                    echo $result_message;
                }
            }

            if ($_GET['step'] == '2') {
                //echo '<br><br>'.__('Step 3)').' <a href="'.$path_tmp.'page=install_update&auto=1&step=3';
                //if (isset($_GET['install_beta'])){ echo '&install_beta=1'; }
                //echo '">'.__('Install files!').'</a><br>';
                echo '<br><input type="submit" name="submit" value="' . __('Install files!') . '" class="btn btn-success btn-sm"><br><br>';
            } else {
                // *** Update settings ***
                $result = $dbh->query("UPDATE humo_settings SET setting_value='2012-01-01' WHERE setting_variable='update_last_check'");
                $humo_option['update_last_check'] = '2012-01-01';

                // *** Remove installation files ***
                unlink("update/humo-gen_update.zip");
                // *** Count down, because files must be removed first before removing directories ***
                for ($i = count($update_files) - 1; $i >= 0; $i--) {
                    if (!is_dir($update_dir[$i] . '/' . $update_files[$i])) {
                        unlink($update_dir[$i] . '/' . $update_files[$i]);
                    } else {
                        rmdir($update_dir[$i] . '/' . $update_files[$i]);
                    }
                }
                rmdir("update/humo-gen_update");

                echo '<br><br>' . __('Update completed and installation files removed!') . '<br><br>';
            }
        }
    } else {

        if (isset($humo_option["version"])) {
            printf(__('Current %s version:'), 'HuMo-genealogy');
            echo ' ' . $humo_option["version"] . '.<br>';

            printf(__('Available %s version:'), 'HuMo-genealogy');
            echo ' ' . $update['version'] . '.<br><br>';
        }

        echo __('There are 2 update methods: automatic and manually.');

        echo '<h2>';
        printf(__('1) Automatic update of %s'), 'HuMo-genealogy');
        echo '</h2>';
        echo __('a) [Optional, but highly recommended] Do a database backup first') . ': <a href="' . $path_tmp . 'page=backup">' . __('backup page') . '.</a><br>';
        echo __('b)') . ' <a href="' . $path_tmp . 'page=install_update&auto=1&update_check=1">' . __('Start automatic update') . '.</a><br>';

        echo '<h2>';
        printf(__('1) Manual update of %s'), 'HuMo-genealogy');
        echo '</h2>';
        echo __('a) [Optional, but highly recommended] Do a database backup first') . ': <a href="' . $path_tmp . 'page=backup">' . __('backup page') . '.</a><br>';

        echo __('b) Download the new version: ') . ' <a href="' . $update['version_download'] . '" target="_blank">';
        printf(__('%s version:'), 'HuMo-genealogy');
        echo ' ' . $update['version'] . '</a><br>';

        printf(__('c) Unzip the file and replace the old %s files by the new %s files.'), 'HuMo-genealogy', 'HuMo-genealogy');
        echo '<br>';
        echo __('Full installation and update instructions can be found at:') . ' <a href="https://sourceforge.net/projects/humo-gen/files/HuMo-gen_Manual/" target="_blank">';
        printf(__('%s manual'), 'HuMo-genealogy');
        echo '</a>';
    }
} else {
    echo __('Online version check unavailable.');

    // *** Semi-automatic update ***
    // *** REMARK: to test this, just disable two lines "$content_array" in index.php ***
    echo '<h2>' . __('Semi-automatic update') . '</h2>';
    echo __('In some cases the automatic update doesn\'t work. Then use this semi-automatic update method.') . '<br>';
    printf(__('1. Download a new version of %s.'), 'HuMo-genealogy');
    echo '<br>' . __('2. Rename the zip file into: humo-gen_update.zip') . '<br>';

    // *** Upload file ***
    if (isset($_FILES['update_file']) && $_FILES['update_file']['name']) {
        $fault = "";
        if (!$fault) {
            $update_new = 'update/humo-gen_update.zip';
            if (!move_uploaded_file($_FILES['update_file']['tmp_name'], $update_new)) {
                echo __('Upload failed, check folder rights');
            }
        }
    }
    echo '<form method="POST" action="' . $path_tmp . 'page=install_update" style="display : inline;" enctype="multipart/form-data"  name="formx" id="formx">';
    echo __('3. Upload humo-gen_update.zip:') . ' <input type="file" name="update_file">';
    echo '<input type="submit" name="submit" title="submit" value="' . __('Upload') . '">';
    echo '</form><br>';
    echo '&nbsp;&nbsp;&nbsp;&nbsp;' . __('OR: manually upload the file to folder: admin/update/') . '<br>';

    // *** Start update ***
    echo __('4. Then click:');
    echo ' <a href="' . $path_tmp . 'page=install_update&auto=1&step=1&update_check=1';
    if (isset($_GET['re_install'])) {
        echo '&re_install=1';
    }
    // *** Force update ***
    echo '&re_install=1';
    echo '">';
    printf(__('start update of %s.'), 'HuMo-genealogy');
    echo '</a><br>';

    // *** Show version ***
    echo '<h2>';
    printf(__('%s version history'), 'HuMo-genealogy');
    echo '</h2>';
    printf(__('%s version'), 'HuMo-genealogy');
    echo ' ' . $humo_option["version"];
    echo '<p><iframe height="300" width="80%" src="https://humo-gen.com/genforum/viewforum.php?f=19"></iframe>';

    // *** Check for HuMo-genealogy beta version SAME CODE AS CODE ABOVE ***
    $check = ' checked';
    if ($humo_option['update_last_check'] == 'DISABLED') {
        $check = '';
    }
    echo '<h2>';
    printf(__('Enable/ disable %s update check.'), 'HuMo-genealogy');
    echo '</h2>';

    echo '<form method="post" action="index.php?page=install_update&update_check=1" style="display : inline">';
    echo '<input type="checkbox" name="enable_update_check"' . $check . ' onChange="this.form.submit();"> ';
    printf(__('Check regularly for %s updates.'), 'HuMo-genealogy');
    echo '<br>';

    //print '<input type="submit" name="enable_update_check_change" value="'.__('Change').'">';
    print '<input type="hidden" name="enable_update_check_change" value="1">';
    echo '</form>';
}
