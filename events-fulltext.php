<article>
  <h1><t4 type="content" name="Title" output="normal" modifiers="striptags"  /></h1>
  <p class="meta">
    <?php
    try {
    /* Version 2.2 */
      $_GET['event_id'] = '<t4 type="meta" meta="content_id" />';
      $event_type = isset($_GET['event_type']) ? filter_var($_GET['event_type'], FILTER_SANITIZE_STRING) : '';

      //Check if we are in preview
        if (preg_match("/t4_([0-9]{16,20}+)\.php/Ui", $_SERVER['REQUEST_URI'], $output_array)) {
            throw new Exception("Sorry, PHP Events Calendar is not available in preview.", 1);
        }

        //load T4EventsCalendar
        if (!is_file($_SERVER['DOCUMENT_ROOT']  . $t4_module['config'].'config.php')) {
            throw new Exception("You need to load the T4EventsCalendar Class dude", 1);
        }

        //load PHAR file
        include_once($_SERVER['DOCUMENT_ROOT']  . $t4_module['config'].'config.php');
        $moduleClass = \Calendar\T4EventsCalendar::module($t4_config, $t4_module);

      /* Start Catch */
    } catch (\Exception $e) {
        echo '<!--'.$e->getMessage().'-->';
        ?>
        <time class="sdate" datetime="<t4 type='content' name='Start Date and Time' date_format='yyyy-d-MM' output='normal' />">
            <t4 type="content" name="Start Date and Time" date_format="MMMM d, yyyy" output="normal" modifiers=""  />
        </time>
        <span class="to-date sr-only"> to <time datetime="<t4 type='content' name='End Date and Time' date_format='yyyy-d-MM' output='normal' />"><t4 type="content" name="End Date and Time" date_format="MMMM d, yyyy" output="normal" modifiers=""  /></time>
        </span> - <a href="#location-map"><t4 type="content" name="Venue" output="normal" modifiers="striptags"  /></a>
        <?php
    }
    /* End Catch */
    ?>
  </p>
  <p class="intro"><t4 type="content" name="Brief Description" output="normal" modifiers="striptags"  /></p>

  <t4 type="content" name="Main Text" output="normal" modifiers="medialibrary, nav_sections"  />

  <h2 id="h3 location-map"><t4 type="content" name="Venue" output="normal" modifiers="striptags"  /></h2>
  <div class="row">
    <div class="small-10 small-centered large-12 columns">
      <div id="event-map"></div>
    </div>
  </div><!-- /.row -->
  <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCbsBU6J6ivi_YqZkc2XVeyU7hcbj_EHPE&sensor=false"></script>
  <script type="text/javascript">
    function initialize() {
      var myLatlng = new google.maps.LatLng(<t4 type="content" name="Location Lattitude" output="normal" modifiers="striptags"  />, <t4 type="content" name="Location Longitude" output="normal" modifiers="striptags"  />);
      var mapOptions = {
        center: myLatlng,
        zoom: 15
      };
      var map = new google.maps.Map(document.getElementById("event-map"),mapOptions);
      var eventMarker = new google.maps.Marker({
        position: myLatlng,
        map: map,
        animation: google.maps.Animation.DROP
      });
    }
    google.maps.event.addDomListener(window, 'load', initialize);
  </script>
</article>
<div class="ical">
    <a class="button btn btn-default calendar-button" href="<?php echo $t4_module['allevents']; ?>download.php?event_id=<t4 type="meta" meta="content_id" /><?php echo isset($event_type) && !empty($event_type) ? '&event_type='.$event_type : ''; ?>">Download Event to Outlook</a>
</div>
