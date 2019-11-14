function load_jQuery () {
    <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
}
var cache = [];

$(window).on('popstate', function() {
    var url = window.location.search;
    console.log(cache);
    if (cache[url] == undefined) {
        url = 'index.php'+url;
    }
    if (cache[url] != undefined) {
        link = cache[url][0];
        loadArea = cache[url][1];
        reloadLoadArea =  cache[url][2];
        runAjax(link,loadArea,reloadLoadArea,false);
        //delete cache[link];
    }
});

function runAjax(link,loadArea,reloadLoadArea,addcache) {
  var loadAreaID  = loadArea.prop('id');
  var contentID   = loadArea.data("ajaxloadalso");
  if (typeof addcache === 'undefined') {
    addcache = true;
  }
  if (reloadLoadArea == true) {
    loadArea.css('opacity',0.5);
  }
  if (contentID != undefined ) {
    for (i = 0; i < contentID.length; ++i) {
          $("#"+contentID[i]).css('opacity',0.5);
    }
  }

  $.ajax({
    url: link,
    context: document.body
  }).done(function( data) {
     if (addcache) {
        cache[link] = [link,loadArea,reloadLoadArea];
        window.history.pushState(link, link, link);
     }

    if (reloadLoadArea == true) {
      loadArea.html($(data).find("#"+loadAreaID).html()).css('opacity',1);
    }

    if (contentID != undefined ) {
      for (i = 0; i < contentID.length; ++i) {
        $("#"+contentID[i]).html($(data).find("#"+contentID[i]).html()).css('opacity',1);
      }
    }
    categories_trigger();
  });
}



$("body").on("click",".ajax-load-area .ajax-load-link a,a.ajax-load-link",function(event){
  if(($('#calendar_events').length || $('#calendar_page').length)) {
    event.preventDefault();
    var link = $(this).attr("href");
    var loadArea = $(this).parents('.ajax-load-area');
    var loadAreaID = loadArea.prop('id');
    if(loadAreaID != 'searchoptions' && loadAreaID != 'searchoptions-generic' )
    runAjax(link,loadArea,true);

    if($(this).parents('.pagination').length) {
    	$('html, body').animate({scrollTop: $("#calendar_events").offset().top - $('header').height() }, 1000);
    }
  }
});

function handleForm(container) {
    if($('#calendar_events').length || $('#calendar_page').length) {
        var loadArea = container.parents('.ajax-load-area');
        var loadAreaID = loadArea.prop('id');
        console.log(loadAreaID);
        var link = $("#" + loadAreaID + " form").attr("action")+"?"+$("#" + loadAreaID + " form").serialize();
        runAjax(link,loadArea,false);
    }
}

$("body").on('click', "#searchoptions form :checkbox, #searchoptions-generic form :checkbox, #searchoptions-categories form :checkbox, #past_events form :checkbox, #searchoptions-dates input[type=submit], #jumptoform input[type=submit]",function(event){
    handleForm($(this));
});

//input[text] keywords
var delay = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();

$("body").on('keyup',"#searchoptions input[type=text], #searchoptions-generic input[type=text]",function(event){
    if($('#calendar_events').length || $('#calendar_page').length) {
        var inputText = $(this);
        delay(function(){
          handleForm(inputText);
        }, 500 );
    }
});

$("body").on("keypress","#searchoptions form, #searchoptions-generic form", function(e) {
  if (e.keyCode == 13) {
    e.preventDefault();
    return false;
  }
});

$("#calendar_page").on('mouseenter mouseleave'," .cal-event a",function(event){
    $(this).removeAttr("title");
    event.preventDefault();
    var contentID = $(this).data("tooltipcal");
    $("#" + contentID).toggleClass("active");
});

function categories_trigger(){
  $('.ajax-load-area .categories_trigger a').each(function(){
    var baseurl = $(this).parent('.categories_trigger').data("baseurl");
    var catname = $(this).text();
    caturl = catname.replace(/ /gi,"+");
    caturl = caturl.replace(/&/gi,"%26");


    $(this).attr("href",baseurl+"&categories[]="+caturl);
    $(this).data("catname",catname);
    var sub = catname.split(">");
    if (sub[1] != "") {
      $(this).html(sub[1]);
    }
  });
}
categories_trigger();


function remove_filter() {
    if(!$('.event-filter').length) {
        $('.clear-filters').addClass('is-hidden');
    } else {
        $('.clear-filters').removeClass('is-hidden');
    }

    $('body').on('click','.event-filter', function(e) {
        var clicked = $(this);
        if (clicked.attr('data-category') && clicked.hasClass('category-filter')) {
            var category = clicked.attr('data-category');
            $('input[data-category="' + category + '"]').trigger('click');

        }
        if(clicked.hasClass('keywords-filter')) {
            if($('#searchoptions').length) {
                $('#searchoptions input[type=text]').val('').trigger('keyup');
            }
            if($('#searchoptions-generic').length) {
                $('#searchoptions-generic input[type=text]').val('').trigger('keyup');
            }
        }
        if(clicked.hasClass('past-filter')) {
            $('input#past').trigger('click');
        }

        clicked.remove();
        if(!$('.event-filter').length) {
            $('.clear-filters').addClass('is-hidden');
        } else {
            $('.clear-filters').removeClass('is-hidden');
        }
    });
}
remove_filter();
