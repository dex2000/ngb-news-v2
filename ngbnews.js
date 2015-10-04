var g_timer = null;
var g_nArticles = 0; //number of READ articles
var g_nUnread = 0; //number of UNREAD articles loaded via ajax
var g_pageTitle = "ngb:NEWS - Wirklich unabhängige News";




//entrypoint, all dom loaded
$( document ).ready(function()
{

	//document finished loading

	//add events for filter buttons in header
	onClickFilterButton();

	//automatically refreshes the page when
	//new articles are available
	startAutoRefresh(300000);

	//save the document title for later use when refreshing the page
	g_pageTitle = document.title;

	//fix the article's image size and positioning
	fixImageAlignment();


});


$(window).resize(function()
{
	fixImageAlignment();
});


function fixImageAlignment()
{
	
	
	$("#latest-news .article").each(function()
	{
	
		//only do this, if the article has a title image
		var objImage = $(this).find(".image img")		
		if(objImage.length > 0)
		{
			//article body and image
			var objBody = $(this).find(".body");
			var objImgContainer = $(this).find(".image");
			
			//get settings of our columns
			var columSpacing = parseInt(objBody.css("column-gap"));
			var numColumns = parseInt(objBody.css("column-count"));		
			
			//by default behave as if we had only 1 column
			var cxCol = objBody.width();
			
			//but if the article body has those 2 css styles, we got multiple
			if(isNaN(columSpacing) == false && isNaN(numColumns) == false)
			{
				cxCol = (objBody.width() - columSpacing) / numColumns;	
			}
						
			//create new image object
			var img = new Image();	
			//define a callback that is fired
			//when the image file finished loading
			//we can not get its widht before that
			img.onload = function()
			{						

				if(img.width < cxCol * 0.75)
				{
					objImgContainer.removeClass("full");
					objImgContainer.addClass("left");
				}
				else
				{
					objImgContainer.removeClass("left");
					objImgContainer.addClass("full");
				}
			}			
			
			var strImgSrc = objImage.attr("src");
			img.src = strImgSrc;					
			
		}
	});
}


//automatically refreshes the page when
//new articles are availab
function startAutoRefresh(nInterval)
{
	//initialize article counter with number of articles
	//in the current version of the index file
	g_nArticles = $("#latest-news .article").length;

	//start the refresh timer
	g_timer = window.setInterval
	(
		function()
		{
			loadNewArticles();
		},
		nInterval
	);


	//hook the scroll event to reset the counter
	//for unread articles. if we realize that the user
	//scrolls, we assume he saw the new articles and hence
	//we can reset the document title and the unread articles counter
	$(window).scroll(function()
	{
		if(g_nUnread)
		{
			//tell the refresh function, that we know all of the
			//new articles
			g_nArticles = 	$("#latest-news .article").length;
			g_nUnread = 0;

			//reset to the original page title without the number
			//of unread messages at the beginning
			document.title = g_pageTitle;
		}
	});
}



//start an ajax request to the index.html page
//if it got new articles, refresh content and
//set title to alert user of new article
function loadNewArticles()
{
	$.ajax(
	{
		url: "index.php",
		type: "GET",
		dataType: "html",
		success: function (res)
		{
		   //get object with latest news
		   var objNews = $(res).find("#latest-news");
		   var lstArticles = objNews.find(".article");


		   //are there any new articles? g_nArticles holds the number since the last update.
		   if(g_nArticles < lstArticles.length)
		   {
			   //get number of new unread articles
				g_nUnread += lstArticles.length - g_nArticles;

			   //update the window title and set number of new articles
			   document.title = "(" + g_nUnread + ") " + g_pageTitle;

			   $("#latest-news").fadeOut('slow', function()
			   {
				   $("#latest-news").html(objNews.html() );
				   $("#latest-news").fadeIn();
			   });

			   //update known number of articles
			   //lets do that when the user scrolls,
			   //so we can display number of all unread
			   //articles since last interaction
			   g_nArticles = lstArticles.length;
		   }

		}
	});
}


//user click event for top menu buttons
function onClickFilterButton()
{
	//if js is enabled, we remove click events
	//in topmenu, cause we dont want the browser
	//to perform default actions when anchor is clicked
	$("label #top-menu a").css('pointer-events', 'none');

	//click event for top menu buttons
	$("#top-menu span.menu span.menu-item").click(function()
	{
		//get the filter value
		var type = $(this).attr("alt");
		var strType = $(this).html();

		//this is default title and link of the link to the forum
		//at the end of all articles
		var strArticleBottomTitle = "Ältere ngb:news und Diskussionen";
		var strArticleBottomUrl = "https://ngb.to/forums/61-ngb-news";

		if(type == "allenews")
		{
			//show all news entries
			$("#latest-news .article").fadeIn();
			$("#latest-news > h1").html("Neueste Artikel")
		}
		else
		{
			//show the ones with the right category associated
			$("#latest-news .article").fadeOut();
			$("#latest-news .article." + type).fadeIn();
			$("#latest-news > h1").html("Neueste Artikel aus " + strType)

			//this is title and link of the link to the forum at the end of all articles
			strArticleBottomTitle = "Ältere ngb:news und Diskussionen zum Thema " + strType;
			strArticleBottomUrl = "https://ngb.to/forums/61-ngb-news?s=&pp=20&daysprune=-1&sort=lastpost&prefixid=" + type + "&order=desc";
		}

		//update text and link in article-bottom area
		$("a.link-to-forum").attr("href", strArticleBottomUrl);
		$("a.link-to-forum").html(strArticleBottomTitle);

		//activate the right menu button
		$("#top-menu span.menu > span").removeClass("selected");
		$(this).addClass("selected");

		//hide the mobile menu
		$("#dropdownmenu").attr("checked", false);

		return false;

	});
}



//these functions do not require jquery
//####################################################################



/*
gets url parameters
usage:
var url = window.location.href;
var strParams = url.substring(url.indexOf('?') + 1 );
var objParams = getUrlParams(strParams);
if(objParams.theme == 'something') {...}
*/
function getUrlParams(query)
{
    var pars = (query != null ? query : "").replace(/&+/g, "&").split('&'),
        par, key, val, re = /^([\w]+)\[(.*)\]/i, ra, ks, ki, i = 0,
        params = {};

    while ((par = pars.shift()) && (par = par.split('=', 2))) {
        key = decodeURIComponent(par[0]);
        // prevent param value going to be "undefined" as string
        val = decodeURIComponent(par[1] || "").replace(/\+/g, " ");
        // check array params
        if (ra = re.exec(key)) {
            ks = ra[1];
            // init array param
            if (!(ks in params)) {
                params[ks] = {};
            }
            // set int key
            ki = (ra[2] != "") ? ra[2] : i++;
            // set array param
            params[ks][ki] = val;
            // go on..
            continue;
        }
        // set param
        params[key] = val;
    }

    return params;
}
