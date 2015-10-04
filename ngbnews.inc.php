<?php

//some includes ##############################################
//NewsEntry class is defined here
include_once("./ngb_newsentry.php");
//jQuery like html dom element selection library
include_once("./simple_html_dom.php");



//takes a list of article objects as argument.
//returns a list of strings with unique <li> entries, that
//can be printed to the DOM source.
function getArticlePrefixes($lstArticles, $heading)
{
	$lstPrefixes = array();

	//collect a list of prefixes. because we're lazy we save the entire html dom string in this array
	foreach($lstArticles as $n)
	{
		//print $heading . " --- " . $n->prefix;

		if($n->prefixClass === $heading)
		{
			//mark this entry as being the selected one
			$lstPrefixes[] = "<span alt='$n->prefixClass' class='selected menu-item $n->prefixClass'><a href='./?heading=$n->prefixClass'>$n->prefix</a></span>";
		}
		else
		{
			//this is menu item is not highlighted
			$lstPrefixes[] = "<span alt='$n->prefixClass' class='menu-item $n->prefixClass'><a href='./?heading=$n->prefixClass'>$n->prefix</a></span>";
		}
	}

	//lets remove duplicate entries from the array
	sort($lstPrefixes);
	$lstPrefixes = array_unique($lstPrefixes);

	return $lstPrefixes;
}


//loads articles from a given absolute url and returns an array of articles
//strNewsPageAbsoluteUrl: absolute url
//max_articles_to_load: number of articles to load at max. defaults to 10
function loadArticlesFromUrl($strNewsPageAbsoluteUrl, $max_articles_to_load = 10)
{

	//fetch first page on ngb news
	$html = file_get_html($strNewsPageAbsoluteUrl);


	//list of all articles found on this page
	$lstArticles = array();
	$numArticles = 0;

	// Find all news threads on first page
	$threadInfoSelector = "ol#threads .threadbit"; //dom selector for basic thread infos
	foreach($html->find($threadInfoSelector) as $element)
	{
		//maximum number of articles to load each run
		if($numArticles >= $max_articles_to_load) break;

		//get url to article
		$strUrl = $element->find(".threadtitle .title")[0]->href; //relative url here
		$strUrl = 'https://ngb.to/' . $strUrl; //absolute url now

		//create new news entry object
		$objNews = new NewsEntry();

		//try to load article details from this url
		if($objNews->loadFromUrl($strUrl) == TRUE)
		{
			//remember date of last update/reply. we gotta fetch
			//this here, not inside of loadFromUrl. other approach would have to go throug all pages to find last post date.
			$strUpdated = $element->find(".threadlastpost dd")[1]->plaintext;
			$objNews->setLastUpdateDate($strUpdated);

			//get url to last comment on this article
			$strLastPostUrl = $element->find(".threadlastpost a.lastpostdate")[0]->href;
			$objNews->setLastCommentUrl($strLastPostUrl);

			$strLastCommentName = $element->find(".threadlastpost a.username strong")[0]->plaintext;
			$strLastCommentUrl = $element->find(".threadlastpost a.username")[0]->href;
			//set author of the last comment. name and url to profile
			$objNews->setLastCommentAuthor($strLastCommentName, $strLastCommentUrl);


			//save the object to our list of articles.
			$lstArticles[] = $objNews;
			$numArticles++;
		}

	} //eof foreach

	//returns list of all articles fetched from the url passed to this function
	return $lstArticles;
}


//loads articles from the tarnkappe RSS feed. returns array of articles
//strNewsPageAbsoluteUrl: absolute url
//max_articles_to_load: number of articles to load at max. defaults to 10
function loadArticlesFromTarnkappeRss($strTarnkappeRssAbsoluteUrl, $max_articles_to_load = 10)
{
	//list of all articles found on this page
	$lstArticles = array();
	$numArticles = 0;

	//fetch first page on ngb news
	$html = file_get_html($strTarnkappeRssAbsoluteUrl);



	foreach($html->find("item") as $item)
	{
		//maximum number of articles to load each run
		if($numArticles >= $max_articles_to_load) break;

		//create new news entry object
		$objNews = new NewsEntry();

		$objNews->title = $item->find("title")[0]->plaintext;
		$objNews->url = $item->find("guid")[0]->plaintext;

		$objNews->prefix = "Tarnkappe";
		$objNews->prefixClass = "tarnkappe";

		//find and clean the name of the author
		$strAuthor = $item->find("dc:creator")[0]->plaintext;
		$strAuthor = str_replace("<![CDATA[", "", $strAuthor);
		$strAuthor = str_replace("]]>", "", $strAuthor);
		$objNews->author = $strAuthor . " // Tarnkappe";
		$objNews->authorUrl = "https://tarnkappe.info/ueber-tarnkappe/";
		$objNews->authorImage = "./images/favicon.ico";

		//the last comment. the RSS feed does not show the name
		//so for now we just set it to hardcoded string
		$objNews->lastCommentAuthor = "Tarnkappe";
		//make sure to save the absolute url
		$objNews->lastCommentAuthorUrl =  "https://tarnkappe.info/";
		//set the link to the comments
		$objNews->lastCommentUrl = $item->find("comments")[0]->plaintext;


		//get and clean article text
		$strArticleBody = $item->find("content:encoded")[0]->plaintext;
		//remove twitter shares
		$strArticleBody = preg_replace('/<div class="twitter-share">.+<\/div>/siU', '', $strArticleBody);
		//remove embedded scripts
		$strArticleBody = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $strArticleBody);
		//remove cdata infos
		$strArticleBody = str_replace("<![CDATA[", "", $strArticleBody);
		$strArticleBody = str_replace("]]>", "", $strArticleBody);
		//remove all formatting except listed ones
		$strArticleBody = strip_tags($strArticleBody, "<br><br/><p><a><b><strong><em><i><ul><li><img><img/>");

		$objNews->body = $strArticleBody;

		//get publishing date
		$strDate = $item->find("pubDate")[0]->plaintext;
		$objNews->datePublished = DateTime::createFromFormat("D, d M Y H:i:s O", $strDate );

		//get date of last comment
		$strDate = $item->find("pubDate")[0]->plaintext;
		$objNews->dateUpdated = DateTime::createFromFormat("D, d M Y H:i:s O", $strDate );

		$lstArticles[] = $objNews;
		$numArticles++;
		
	} //eof for each item

	return $lstArticles;

}

?>