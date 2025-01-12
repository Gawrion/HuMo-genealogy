<?php
require_once __DIR__ . "/../models/trees.php";
include_once(__DIR__ . "/../include/select_tree.php");

class TreesController
{
    /*
    private $editor_cls;

    public function __construct()
    {
        $this->editor_cls = new editor_cls;
    }
    */

    public function detail($dbh, $tree_id, $db_functions, $selected_language)
    {
        $treesModel = new TreesModel($dbh);
        $treesModel->set_tree_id($tree_id);
        $treesModel->update_tree($dbh, $db_functions);
        $trees['tree_id'] = $treesModel->get_tree_id();
        $trees['language'] = $treesModel->get_language($selected_language);
        // *** Select language for texts at page ***
        $trees['language2'] = $treesModel->get_language2($trees['language'], $selected_language);
        $trees['menu_tab'] = $treesModel->get_menu_tab();

        // *** Use a seperate model for each menu tab ***
        if ($trees['menu_tab'] == 'tree_main') {
            include_once(__DIR__ . "/../../include/show_tree_date.php");
            include_once(__DIR__ . "/../../views/partial/select_language.php");

            include(__DIR__ . '/../../languages/' . $trees['language2'] . '/language_data.php');

            //require_once __DIR__ . "/../models/tree_admin.php";
            $trees['language_path'] = 'index.php?page=tree&amp;tree_id=' . $trees['tree_id'] . '&amp;';
        } elseif ($trees['menu_tab'] == 'tree_gedcom') {
            include_once(__DIR__ . "/../include/gedcom_asciihtml.php");
            include_once(__DIR__ . "/../include/gedcom_anselhtml.php");
            include_once(__DIR__ . "/../include/gedcom_ansihtml.php");

            // *** Support for GEDCOM files for MAC computers ***
            // *** Still needed in april 2023. Will be deprecated in PHP 9.0!***
            // *** TODO improve processing of line_endings ***
            @ini_set('auto_detect_line_endings', TRUE);

            // Because of processing very large GEDCOM files.
            @set_time_limit(4000);

            require_once __DIR__ . "/../models/gedcom.php";
            $gedcomModel = new GedcomModel($dbh);
            $trees['step'] = $gedcomModel->get_step();

            if ($trees['step'] == '1') {
                $upload_status = $gedcomModel->upload_gedcom();
                $trees = array_merge($trees, $upload_status);

                $trees['gedcom_directory'] = $gedcomModel->get_gedcom_directory();
            }
        } elseif ($trees['menu_tab'] == 'tree_data') {
            //require_once __DIR__ . "/../models/tree_data.php";
        } elseif ($trees['menu_tab'] == 'tree_text') {
            require_once __DIR__ . "/../models/tree_text.php";
            $tree_textModel = new TreeTextModel($dbh);

            // *** Select language for texts at page ***
            include(__DIR__ . '/../../languages/' . $trees['language2'] . '/language_data.php');

            $tree_texts = $tree_textModel->get_tree_texts($dbh, $trees['tree_id'], $trees['language']);
            $trees = array_merge($trees, $tree_texts);
        } elseif ($trees['menu_tab'] == 'tree_merge') {
            require_once __DIR__ . "/../models/tree_merge.php";
            $treeMergeModel = new TreeMergeModel($dbh);
            $trees['relatives_merge'] = $treeMergeModel->get_relatives_merge($dbh, $trees['tree_id']);
            $treeMergeModel->update_settings($db_functions); // *** Store and reset tree merge settings ***
        }

        return $trees;
    }
}
