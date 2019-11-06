<?php
namespace Calendar;

/* Version 2.2 */
$t4_config['phar']      = '<t4 type="content" name="PHAR File" output="normal" formatter="path/*" />';
$t4_config['sources']   = '<t4 type="content" name="Sources" output="normal" display_field="value" />';
$t4_config['maxevents'] = '<t4 type="content" name="Max Events per Page" output="normal" />';
$t4_config['allevents'] = '<t4 type="content" name="Main View Section" output="linkurl" modifiers="nav_sections" />';
$t4_config['jsonxml']   = '<t4 type="content" name="Events JSON or XML Source" output="normal" modifiers="striptags,htmlentities" />';
$t4_config['gapi']      = '<t4 type="content" name="Google API Token" output="normal" modifiers="striptags,htmlentities" />';
$t4_config['gids']      = '<t4 type="content" name="Google Calendar IDs" output="normal" modifiers="striptags,htmlentities" />';
$t4_config['icals']     = '<t4 type="content" name="iCal Sources" output="normal" />';
$t4_config['language']  = '';
$t4_config['timezone']  = '<t4 type="content" name="Timezone" output="normal" modifiers="striptags,htmlentities" />';
$t4_config['options']   = '<t4 type="content" name="Options" output="normal" modifiers="striptags,js_var" />';


if (!class_exists('T4EventsCalendar')) {
    class T4EventsCalendar
    {
        public static $runConfig = false;

        protected static $variables_to_skip = [
            "all_event_url",
            "calendar_language",
            "timezone",
            "enabled_sources",
            "events_data_source",
            "google_client_email",
            "google_api_p12_token_file",
            "google_calendar_id",
            "ical_source_location",
            "max_pagination"
        ];

        protected static $variables_to_skip_in_module = array("cache_directory");

        protected static $configSettings = array();

        public static function config($t4_config)
        {

            self::$runConfig = true;
            $requirementError = array();

            if (version_compare(phpversion(), '5.5.0', '<') || !extension_loaded('mbstring') || !extension_loaded('curl') || !extension_loaded('ctype') || !extension_loaded('tokenizer')) {
                $errorMessage = '<header><strong>Server Configuration Requirements Error</strong>'.'<br />'.
                'PHP Version 5.5+: ' . (version_compare(phpversion(), '5.5.0', '<') ? '<strong>FALSE</strong>' : 'true'). '<br />'.
                'Enabled "mbstring" extension: ' . (!extension_loaded('mbstring') ? '<strong>FALSE</strong>' : 'true'). '<br />'.
                'Enabled "curl" extension:' . (!extension_loaded('curl') ? '<strong>FALSE</strong>' : 'true'). '<br />'.
                'Enabled "ctype" extension: ' . (!extension_loaded('ctype') ? '<strong>FALSE</strong>' : 'true'). '<br />'.
                'Enabled "tokenizer" extension: ' . (!extension_loaded('tokenizer') ? '<strong>FALSE</strong>' : 'true'). '<br />';
                throw new \Exception($errorMessage);
            }

            //Load PHAR file
            include_once(((strpos($t4_config['phar'], '.phar') !== false) ? 'phar://' : '').$_SERVER['DOCUMENT_ROOT'].$t4_config['phar'].'/vendor/autoload.php');

            if (!class_exists("\Calendar\Core\Config")) {
                throw new \Exception("PHAR file not configured");
            }

            // set the locale
            if ($t4_config['language'] != "") {
                self::$configSettings["calendar_language"] = $t4_config['language'];
            }

            if ($t4_config['timezone'] != "") {
                self::$configSettings["timezone"] = $t4_config['timezone'];
                @date_default_timezone_set(self::$configSettings["timezone"]);
            }


            self::$configSettings["view_directory"] = dirname($_SERVER['DOCUMENT_ROOT'] . $t4_config['phar']).'/views/';
            if (!is_dir(self::$configSettings["view_directory"])) {
                self::$configSettings["view_directory"] = dirname($_SERVER['DOCUMENT_ROOT'] . $t4_config['phar']). '/views/';
            }

            $isInOptions  = preg_grep("/view_directory/", explode("\n", $t4_config["options"]));
            if (!is_dir(self::$configSettings["view_directory"])  && empty($isInOptions)) {
                throw new Exception("Add View in the Same directory of the PHAR File or check if the PHP Files is enabled to always published in the channel");
            }

            self::$configSettings["cache_directory"] = sys_get_temp_dir().'/php-events-calendar/';

            // set the enabled sources
            Config::set('enabled_sources', ['TerminalFourEvents', 'GoogleCalendarEvents', 'IcalEvents']);
            if (empty($t4_config['sources'])) {
                throw new \Exception("Sources is empty");
            }

            $sources_array = explode(", ", $t4_config['sources']);

            self::$configSettings["enabled_sources"] = $sources_array;


            //Max Events per Page
            if ($t4_config['maxevents'] > 0) {
                self::$configSettings["max_pagination"] = intval($t4_config['maxevents']);
            }

            if ($t4_config['allevents'] != "") {
                self::$configSettings["all_event_url"] = $t4_config['allevents'];
            } else {
                self::$configSettings["all_event_url"] = str_replace('/index.php', '/', $_SERVER['SCRIPT_NAME']);
            }


            if (strpos($t4_config['sources'], "TerminalFourEvents") !== false) {
                if (empty($t4_config['jsonxml'])) {
                    throw new Exception("Terminal Fours Event is set as source, but Events JSON or XML Source is empty", 1);
                }
                self::$configSettings["events_data_source"] = $_SERVER['DOCUMENT_ROOT'] . $t4_config['jsonxml'];
            }

            if (strpos($t4_config['sources'], "GoogleCalendarEvents") !== false) {
                if (empty($t4_config['gapi'])) {
                    throw new Exception("Google Calendar Events is set as source, but Google API Token is empty", 1);
                }
                if (empty($t4_config['gids'])) {
                    throw new Exception("Google Calendar Events is set as source, but Google Calendar ID is empty", 1);
                }
                self::$configSettings["google_api_p12_token_file"] = $_SERVER['DOCUMENT_ROOT'] . $t4_config['gapi'];
                self::$configSettings["google_calendar_id"] = $t4_config['gids'];
            }

            // Set the ical source location
            if (strpos($t4_config['sources'], "IcalEvents") !== false) {
                if (empty($t4_config['icals'])) {
                    throw new Exception("iCal Events is set as source, but iCal Sources is empty", 1);
                }
                $icals = explode(PHP_EOL, $t4_config['icals']);

                foreach ($icals as $ical) {
                    if (preg_match("/((https?):\/\/)/iU", $ical) === 0) {
                        self::$configSettings["ical_source_location"][] = str_replace('=', '='.$_SERVER['DOCUMENT_ROOT'], $ical);
                    } else {
                        self::$configSettings["ical_source_location"][] = $ical;
                    }
                }
            }

            // set the Timezone e language
            setlocale(LC_TIME, \Calendar\Core\Config::get('calendar_language'));
            date_default_timezone_set(\Calendar\Core\Config::get('timezone'));

            if (isset($t4_config['custom_search']) && is_callable($t4_config['custom_search'])) {
                \Calendar\Core\Config::set('custom_search', $t4_config['custom_search']);
            }

            // initialise Container
            $cont = new Container();

            $options = $t4_config['options'];

            if (!empty($options)) {
                \Calendar\Core\Config::setFromString($options, self::$variables_to_skip, self::$variables_to_skip_in_module, true);
            }
            $t4_config = [];
        }

        public static function module($t4_config, $t4_module, $force_view = null)
        {
            $runConfig = false;
            if (self::$runConfig == false) {
                self::config($t4_config);
                $runConfig = true;
            }

            //Check if PHAR is loaded
            if (!class_exists("\Calendar\Core\Config") || self::$runConfig == false) {
                throw new \Exception("PHAR file not configured");
            }

            //Set All Event URL
            if ($t4_module['allevents'] != "") {
                if (isset(self::$configSettings["all_event_url"])) {
                    $old_all_event_url =  self::$configSettings["all_event_url"];
                }
                self::$configSettings["all_event_url"] = $t4_module['allevents'];
            }

            //Process Custom Query
            if (!empty($t4_module['custom_query'])) {
                $custom_query = array();
                $custom_query['day']            = !empty($t4_module['custom_query']['day']) ? $t4_module['custom_query']['day'] : date("d");
                $custom_query['month']          = !empty($t4_module['custom_query']['month']) ? $t4_module['custom_query']['month'] : date("m");
                $custom_query['year']           = !empty($t4_module['custom_query']['year']) ? $t4_module['custom_query']['year'] : date('Y');
                $custom_query['search']         = !empty($t4_module['custom_query']['search']) ? $t4_module['custom_query']['search'] : 'all';
                $custom_query['categories']     = !empty($t4_module['custom_query']['categories']) ? explode("|", $t4_module['custom_query']['categories']) : null;
                $custom_query['past']           = !empty($t4_module['custom_query']['past']) ? true : false;
                if (preg_match("/Calendar-(.+)$/iU", $t4_module['module'])) {
                    $custom_query['paginate']         = !empty($t4_module['custom_query']['paginate']) ? $t4_module['custom_query']['paginate'] : 9999;
                } else {
                    $custom_query['paginate']         = !empty($t4_module['custom_query']['paginate']) ? $t4_module['custom_query']['paginate'] : null;
                }

                $query = http_build_query($custom_query);
                $query = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $query);

                $t4_module['options']     .= "\ncustom_query = ".$query;
                $t4_module['options']     .= "\nview_for_date_multi = ".(!empty($t4_module['custom_query']['multi']) ? 'true' : 'false');
                $t4_module['options']     .= "\nview_for_date_recur = ".(!empty($t4_module['custom_query']['recurs']) ? 'true' : 'false');
                $t4_module['options']     .= "\nstrict_past = ".(!empty($t4_module['custom_query']['strict']) ? 'true' : 'false');
            }



            //T4 Options variable
            if (!empty($t4_module['options'])) {
                $module_options = $t4_module['options'];
            } else {
                $module_options = "";
            }

            $module = !empty($t4_module['module']) ? $t4_module['module']: "Results-Page";

            $customdays = false;

            if (isset($_GET['search']) && empty($_GET['search'])) {
                unset($_GET['search']);
            }

            if (isset($_GET['keywords']) && empty($_GET['keywords'])) {
                unset($_GET['keywords']);
            }

            if (preg_match("/Calendar-(.+)$/iU", $module)) {
                $module_options .= "\ncustom_query = paginate=9999";
            } elseif (preg_match("/Results-(.+)(day|days|week|weeks|month|months|year|years)Box$/iU", $module)) {
                $module_custom = str_replace("results-", "custom-", mb_strtolower($module));
                $module_custom = str_replace("box", "", $module_custom);
                if (strpos(\Calendar\Core\Config::get("allowed_searches"), $module_custom)  === false) {
                    $module_options .= "\nallowed_searches = ".$module_custom;
                    $module_options .= "\ncustom_query = search=".str_replace('extended.', '', $module_custom)."&year=".date('Y')."&month=".date('m')."&day=".date('d')."&categories=&keywords=&paginate=9999";
                    if (\Calendar\Core\Config::get("custom_date") === false) {
                        $module_options .= "\ncustom_date = +0days";
                    }
                } else {
                    $module_options .= "\ncustom_query = year=".date('Y')."&month=".date('m')."&day=".date('d')."&categories=&keywords=&paginate=9999";
                }
            } elseif (preg_match("/Results-(.+)(day|days|week|weeks|month|months|year|years)$/iU", $module)) {
                if (isset($_GET['search']) && !preg_match("/custom-(.+)(day|days|week|weeks|month|months|year|years)$/iU", $_GET['search'])) {
                    $module = "Results-Page";
                } else {
                    if (isset($_GET['search'])) {
                        $module = str_replace("Custom-", "Results-", ucwords($_GET['search'], " \t\r\n\f\v01234567890+-"));
                    }

                    $module_custom = str_replace("results-", "custom-", mb_strtolower($module));

                    if (strpos(\Calendar\Core\Config::get("allowed_searches"), $module_custom)  === false) {
                        $module_options .= "\nallowed_searches = ".str_replace('extended.', '', $module_custom);

                        if (\Calendar\Core\Config::get("custom_date") === false) {
                            $module_options .= "\ncustom_date = +0days";
                        }
                        $customdays = true;
                    }

                    $module_options .= "\ncustom_query = search=". str_replace('extended.', '', $module_custom) ."&year=".date('Y')."&month=".date('m')."&day=".date('d');
                }
            }

            \Calendar\Core\Config::setArray(self::$configSettings);
            $options = $t4_config['options'];

            if (!empty($options)) {
                \Calendar\Core\Config::setFromString($options, self::$variables_to_skip, self::$variables_to_skip_in_module, true);
            }

            if (!empty($module_options)) {
                if (isset(self::$variables_to_skip)) {
                    \Calendar\Core\Config::setFromString($module_options, self::$variables_to_skip, self::$variables_to_skip_in_module, false);
                }
            }

            if (!is_dir(\Calendar\Core\Config::get("cache_directory"))) {
                if (!mkdir(\Calendar\Core\Config::get("cache_directory"))) {
                    throw new Exception("Cache Directory is not a real directory");
                }
            }

            global $cont;

            if ($runConfig) {
                $cont = new \Calendar\Core\Container();
            } else {
                //I will reset the QueryHandler and the Search Information with the option set in the View content type
                $cont->registerConatiners();
            }

            $query_handler = $cont->make('\Calendar\Core\QueryHandler');
            $blade = $cont->make('\Calendar\Core\CalendarBlade');

            //Load View
            if ($query_handler->param_exists('event_id') && ($module == "Results-Page" || $customdays === true)) {
                $module = "Results-Single";
            }

            if (isset($force_view) && !empty($force_view)) {
                $module = $force_view;
                $force_view = '';
            }


            list($class, $view) = explode("-", $module);
            $view = $module;
            $class = 'Calendar\Modules\\'.str_replace('extended.', '', $class);
            if ($class != 'Download') {
                echo "<!-- PHP Events Calendar v2 by TERMINALFOUR -->\n";
                echo "<!-- T4\Calendar :: Version: ".\Calendar\Core\T4Version::getVersion()." -->\n";
            }
            $moduleClass[$class] = $cont->make($class);

            if ($view) {
                echo $moduleClass[$class]->output($view);
            } else {
                echo $moduleClass[$class]->output();
            }




            //Rollback value to All Event URL and T4 Options
            if ($t4_module['allevents'] != "") {
                if (isset($old_all_event_url)) {
                    \Calendar\Core\Config::set('all_event_url', $old_all_event_url);
                }
            }

            \Calendar\Core\Config::rollbackFromString();

            return $moduleClass;
        }
    }
}
