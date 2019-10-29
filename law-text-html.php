<?php
/* Version 2.2 */
$current_content_layout_name = 'law/text/html';

$position               = '<t4 type="content" name="Position in the page" output="normal" display_field="value" />';
$t4_module['config']    = '<t4 type="content" name="PHP Events Calendar Config Link" output="linkurl" modifiers="nav_sections" />';
$t4_module['allevents'] = '<t4 type="content" name="Main View Section" output="linkurl" modifiers="nav_sections" />';
$t4_module['module']    = '<t4 type="content" name="View" output="normal" display_field="value" />';
$t4_module['options']   = '<t4 type="content" name="Options" output="normal" modifiers="striptags,js_var" />';


try {
    //Check if we are in preview
    if (preg_match("/t4_([0-9]{16,20}+)\.php/Ui", $_SERVER['REQUEST_URI'], $output_array)) {
        throw new Exception("Sorry, PHP Events Calendar is not available in preview.", 1);
    }

    //load T4EventsCalendar
    if (!is_file($_SERVER['DOCUMENT_ROOT']  . $t4_module['config'].'config.php')) {
        throw new Exception("You need to load the T4EventsCalendar Class 5264" . $_SERVER['DOCUMENT_ROOT'], 1);
    }

    //load PHAR file
    include_once($_SERVER['DOCUMENT_ROOT']  . $t4_module['config'].'config.php');
    if ($t4_module['module'] != 'Results-Single' && $position == $current_content_layout_name) {
        $moduleClass = \Calendar\T4EventsCalendar::module($t4_config, $t4_module);
        unset($t4_module);
    }

    /* Start Catch */
} catch (\Exception $e) {
    if (!isset($eventErrors)) {
        $eventErrors = [];
    }
    if (!in_array($e->getMessage(), $eventErrors)) {
        $eventErrors[] = $e->getMessage();
    }
    echo '<div class="eventCalError" style="background:rgb(250,200,200); padding: 1em; border: 1px solid rgb(180,20,20); border-radius: 3px; margin-bottom: 1em;color: rgb(130,20,20)">'.implode('<br />', $eventErrors).'</div>';
}
/* End Catch */
?>
