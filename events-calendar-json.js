try {
  //Defining main functions
  function processTags(t4Tag) {
    myContent = content || null;
    return com.terminalfour.publish.utils.BrokerUtils.processT4Tags(dbStatement, publishCache, section, myContent, language, isPreview, t4Tag);
  }

  function getLayout(contentLayout) {
        var tid = content.getContentTypeID();
        formatter     = contentLayout;
        format        = publishCache.getTemplateFormatting(dbStatement, tid, formatter);
        formatString  = format.getFormatting();
        return processTags(formatString);
  }

  var list = [];


  list['content_id']      = processTags('<t4 type="meta" meta="content_id" />');
  list['name']            = processTags('<t4 type="content" name="Title" output="normal" modifiers="striptags,htmlentities" />');
  list['categories']      = processTags('<t4 type="content" name="Category" output="normal" display_field="name" delimiter="|" />');
  list['recurs']          = processTags('<t4 type="content" name="Recurs Every" output="normal" display_field="value" />');
  list['short_desc']      = processTags('<t4 type="content" name="Brief Description" output="normal" modifiers="medialibrary,nav_sections" />');
  list['main_desc']       = processTags('<t4 type="content" name="Main Text" output="normal" /><t4 type="meta" meta="content_id" />');
  list['location']        = processTags('<t4 type="content" name="Venue" output="normal" modifiers="striptags,htmlentities" />');
  list['url']             = processTags('<t4 type="content" name="Title" output="fulltext" use-element="true" filename-element="Title" modifiers="striptags,htmlentities" />');

// list['resultHTML'] 	  =	getLayout('text/single-calendar-event');


  //Dates
  list['all_day']         = processTags('<t4 type="content" name="All Day Event?" output="normal" display_field="value" />');

  if(list['all_day'] == "") {
    list['startdate']     = processTags('<t4 type="content" name="Start Date and Time" output="selective-output" modifiers="" date_format="dd/MM/yy HH:mm" format="$value" />');
    list['enddate']       = processTags('<t4 type="content" name="End Date and Time" output="selective-output" modifiers="" date_format="dd/MM/yy HH:mm" format="$value" />');
    list['recursend']     = processTags('<t4 type="content" name="Recurrence End Date" output="selective-output" modifiers="" date_format="dd/MM/yy HH:mm" format="$value" />');
  } else {
    list['startdate']     = processTags('<t4 type="content" name="Start Date and Time" output="selective-output" modifiers="" date_format="dd/MM/yy 00:00" format="$value" />');
    list['enddate']       = processTags('<t4 type="content" name="End Date and Time" output="selective-output" modifiers="" date_format="dd/MM/yy 23:59" format="$value" />');
    list['recursend']     = processTags('<t4 type="content" name="Recurrence End Date" output="selective-output" modifiers="" date_format="dd/MM/yy 23:59" format="$value" />');
  }

  if (list['recursend'] == '') {
    list['recursend'] 	  = '01/06/18 23:59';
  }

  list['ad_hoc_dates'] = [];
  var dates = 0;
  for (i = 1;i<=3;i++) {
    date = processTags('<t4 type="content" name="Ad-hoc Recurrence '+i+'" output="selective-output" modifiers="" date_format="dd/MM/yy" format="$value" />');
    time = processTags('<t4 type="content" name="Ad-hoc Recurrence '+i+'" output="selective-output" modifiers="" date_format=" HH:mm" format="$value" />');
    if (date != '' && list['all_day'] == '') {
      list['ad_hoc_dates'][dates] = date+time;
    } else if (date != '' && list['all_day'] != ''){
      list['ad_hoc_dates'][dates] = date+' 00:00';
    }
    dates++;
  }


  if (list['categories'] == '') {
  	list['categories'] = 'Uncategorized';
  }

  var jsonObj = new org.json.JSONObject(list);
  document.write(jsonObj.toString() + ',');

}
catch(err) {
	document.write(err);
}
