<?php
// *** Safety line ***
if (!defined('ADMIN_PAGE')) {
    exit;
}

$file = 'include/prefixes.php';
$message = '';
if (isset($_POST['save_language'])) {
    $message = '<b>' . __('Saved') . '</b>';
    if (file_exists($file)) {
        $language_text = $_POST['language_text'];
        file_put_contents($file, $language_text);
    } else {
        $message = 'ERROR: FAULT IN SAVE PROCESS';
    }
}
?>

<h1 align=center><?= __('Prefix editor'); ?></h1>

<?= __('These prefixes are used to process name-prefixes if a GEDCOM file is read.'); ?><br><br>

<form method="POST" action="index.php?page=prefix_editor">
    <?php
    if (is_writable($file)) {
        echo ' <input type="submit" class="btn btn-success btn-sm" name="save_language" value="' . __('Save') . '"> ';
    } else {
        echo '<b>' . __('FILE IS NOT WRITABLE!') . '</b>';
    }
    // *** Show "Save" message ***
    echo $message;
    ?>
    <br><br>

    <textarea rows="35" cols="120" name="language_text" style="direction:ltr"><?= file_get_contents($file); ?></textarea>
</form>
<br>