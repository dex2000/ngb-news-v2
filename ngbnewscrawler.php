<?php

	//show error output ###########################################
	//ini_set('display_errors', 1);
	//error_reporting(E_ALL);


	//some includes ##############################################
	//include constants/settings for this script
	include_once("./settings.php");
	//include the worker functions for this script
	include_once("./ngbnews.inc.php");

	//set the default timezone to prevent warnings
	date_default_timezone_set('Europe/Vienna');

	//load articles and create lists for output further down below
	//##############################################################

	//fetch first and second page on ngb news
	$lstAllArticlesPage1 = loadArticlesFromUrl('https://ngb.to/forums/61-ngb-news', $MAX_ARTICLES_TO_LOAD);
	$lstAllArticlesPage2 = loadArticlesFromUrl('https://ngb.to/forums/61-ngb-news/page2', $MAX_ARTICLES_TO_LOAD);
	//fetch articles from tarnkappe rss feed
	$lstTarnkappeArticles = array(); //loadArticlesFromTarnkappeRss("https://tarnkappe.info/feed/", $MAX_ARTICLES_TO_LOAD);

	//merge articles collected from the first two sites of news page
	$lstAllArticles = array_merge($lstAllArticlesPage1, $lstAllArticlesPage2, $lstTarnkappeArticles);


	//split NEWS and TRENDING articles. we display them differently further down below
	//#################################################################################

	$lstNews = array(); //an array that holds news articles
	$numNews = 0; //counts number of loaded news articles
	$lstInteresting = array(); //holds list of older articles that are still interesting
	$numInteresting = 0;


	//experimental: show all articles as news AND in the newest comments sidebar.
	$lstNews = $lstAllArticles;
	//show all ngb news in the latest comments, but no tarnkappe stuff.
	$lstInteresting = array_merge($lstAllArticlesPage1, $lstAllArticlesPage2);

	$numNews = sizeof($lstNews);
	$numInteresting = sizeof($lstInteresting);


	//clean up a little
	unset($lstAllArticlesPage1);
	unset($lstAllArticlesPage2);
	unset($lstTarnkappeArticles);


	//sort the array of articles by publishing date
	//this way of sorting requires php5.3
	usort($lstNews, function($a, $b)
	{
		//sort ascending
		//return strtotime($a->datePublished->format('U')) - strtotime($b->datePublished->format('U'));
		//sort descending
		return $a->datePublished <= $b->datePublished;
	});

	//sort the array of older but recently commented articles by comment date
	//this way of sorting requires php5.3
	usort($lstInteresting, function($a, $b)
	{
		//sort ascending
		//return strtotime($a->datePublished->format('U')) - strtotime($b->datePublished->format('U'));
		//sort descending
		return $a->dateUpdated <= $b->dateUpdated;
	});

	//experimental: make sure that the first regular article and the first interesting article are not the same.
	if(sizeof($lstInteresting) > 0 && $lstInteresting[0] == $lstNews[0])
	{
		array_shift($lstInteresting);
	}

	//output info about scraped content
	print "ngb:newsCrawler loaded " . $numNews . " articles and " . $numInteresting . " comments.\r\n";

	//save articles and comments to file
	try
	{
		//output filename for articles
		$articlesPath = $DATAFILE_DIR . "/" . $DATAFILE_ARTICLES;
		//output filename for latest comments
		$commentsPath = $DATAFILE_DIR . "/" . $DATAFILE_COMMENTS;

		//check if output dir is writeable
		if (is_writable($DATAFILE_DIR))
		{
			//save articles to output file
			$objOutput = serialize($lstNews);
			$fp = fopen($articlesPath, "w");
			fwrite($fp, $objOutput);
			fclose($fp);

			//save comments to second output file
			$objOutput = serialize($lstNews);
			$fp = fopen($commentsPath, "w");
			fwrite($fp, $objOutput);
			fclose($fp);
		}
		else
		{
			///output ain't writeable :(
			throw new Exception("Couldn't open output directory for writing.");
		}

		print "Data was successfuly saved to output dir.\r\n";
	}
	//something went wrong while trying to save articles or comments
	catch(Exception $exc)
	{
		print "ERROR: " . $exc->getMessage() . "\r\n";
	}

?>