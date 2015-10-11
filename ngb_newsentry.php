<?php

//a class, representing a single news entry
class NewsEntry
{
	public $title;
	public $url;
	public $image;
	public $author;
	public $authorUrl;
	public $authorImage;
	public $body;
	public $datePublished; //the publishing date as date object
	public $dateUpdated;	//the date of last update as date object
	public $lastCommentUrl; //link to the last user comment
	public $lastCommentAuthor; //username of last comment
	public $lastCommentAuthorUrl; //url to the profile of user who wrote last comment
	public $prefix;
	public $prefixClass;


	//returns checksum of this article object,
	//based on article's url. FALSE on error.
	public function checksum()
	{
		if(isset($this->url))
		{
			return hash("md5", $this->url);
		}
		else
		{
			return FALSE;
		}
	}




	//load news article info by it's URL from NGB page
	//FALSE on error, TRUE on success
	public function loadFromUrl($strUrl)
	{
		try
		{
			//fetch article detail page
			$html = file_get_html($strUrl);

			$this->url = $strUrl;
			$this->title = $html->find("#pagetitle .threadtitle")[0]->plaintext;

			//fetch the article prefix (Netzwelt, Technik, etc.)
			$tmpPrefix = $html->find("#breadcrumb .lastnavbit span span");
			if($tmpPrefix != FALSE)
			{
				//save the prefix
				$strPrefix = $tmpPrefix[0]->plaintext;
				//remove [ and ] characters
				$strPrefix = preg_replace('/[\]\[]/', '', $strPrefix);
				//remove leading/trailing whitespaces
				$strPrefix = trim($strPrefix);
				$this->prefix = $strPrefix;

				//save the prefix class, which is the prfix without special chars
				$strPrefix = str_replace(' ', '', $this->prefix);
				//$strPrefix = strtolower($strPrefix);
				$this->prefixClass = $strPrefix;
			}
			else
			{

				$this->prefix = "Wenn du das siehst, ist es ein Bug :(";
				$this->prefixClass = "empty-prefix";
			}


			$this->author = trim($html->find("#posts li.postcontainer .postdetails a.username")[0]->plaintext);
			$this->authorUrl = "https://ngb.to/" . $html->find("#posts li.postcontainer .postdetails a.username")[0]->href;

			//$this->datePublished = $html->find("#posts li.postcontainer .posthead .date")[0]->plaintext;
			$this->datePublished = $this->getPublishingDate($html);

			//get the article body as html
			$strArticleBody = $html->find("#posts li.postcontainer .postbody .postcontent")[0];
			
			//we need to clean the body a little here
			
			//strip all tags except the ones in param here
			$strArticleBody = strip_tags($strArticleBody, "<br><br/><a><b><i><ul><li><iframe>");
			//sometimes users places newlines at the beginning of an article, we want to remove those
			$strArticleBody  = preg_replace('/^(<br\s*\/*>|\s|<\/*p>)+/', '', $strArticleBody);
			//and double newslines should be removed as well (replaces 3 or more BRs with only 2 BRs)
			$strArticleBody  =  preg_replace('/(<br\s*\/*>\s*){3,}/', '<br />&nbsp;<br />', $strArticleBody);
			$this->body = $strArticleBody;


			//make sure, we only search in the first post for a user avatar. otherwise it shows the
			//next available avatar.
			$avatarImg = $html->find("#posts li.postcontainer"); //get list of psots
			$avatarImg = $avatarImg[0]->find(".postdetails a.postuseravatar img"); //find avatar in first post
			if($avatarImg != FALSE)
			{
				//save absolute url to avatar image
				$this->authorImage = "https://ngb.to/" . $avatarImg[0]->src;
			}
			else
			{
				//set ngb icon if user has no avatar
				$this->authorImage = "./images/favicon.ico"; //"./images/ico_avatar_small.png";
			}

			//savely try to find an image. dont raise an exception if we can't find one,
			//not every article does have an image currently.
			if( ($imageTmp = $this->getArticleImage($html)) != FALSE)
			{
				$this->image = $imageTmp;
			}
			else
			{
				//if we got no article image, we add the users avatar picture instead
				//$this->image = $this->authorImage;

				//if we got no article image, use the ngb logo
				//$this->image = "https://ngb.to/ngb_assets/ngb_logo.png";

				//if we got no article image, unset the image variable. we wont output it then later
				unset($this->image);
			}


			return TRUE;
		}
		catch(Exception $ex)
		{
			return FALSE;
		}
	}




	//trys to extract the publishing date from the html source of the article and create a date object
	private function getPublishingDate($html)
	{
		//read publishing date from the provided html source
		$strDate = $html->find("#posts li.postcontainer .posthead .date")[0]->plaintext;
		$strDate = $this->cleanParsedDatestring($strDate);

		//lets try to create a dateobject from the parsed string
		$objDate = DateTime::createFromFormat("d.m.y, H:i", $strDate );

		return $objDate;
	}


	//cleans a parsed string and returns a date object.
	//make sure the string has the proper dateformat d.m.y, H:i
	private function cleanParsedDatestring($strDate)
	{
		//->plaintext above returns "&nbsp;" for space strings. u cant see this in the browser ;).
		$strDate = str_replace("&nbsp;", " ", $strDate);
		//we gotta clean this string first, remove whitespaces and beginning and end
		$strDate = trim($strDate);
		//date display contains HEUTE or GESTERN which have to be replaced with a valid date like 11.11.2011
		$strDate = str_replace("Heute", date('d.m.y'), $strDate);
		$strDate = str_replace("Gestern", date("d.m.y", strtotime( '-1 days' ) ), $strDate);

		//return a clean string
		return $strDate;
	}


	//takes a string and creates a date object from it that
	//is stored in the articles last update field.
	public function setLastUpdateDate($strDate)
	{
		$strDate = $this->cleanParsedDatestring($strDate);
		//lets try to create a dateobject from the parsed string
		$this->dateUpdated = DateTime::createFromFormat("d.m.y, H:i", $strDate );
	}


	//fetches the link to the last comment for this article from
	//the provied html dom stuff
	public function setLastCommentUrl($strUrl)
	{
		//make sure to save the absolute url
		$this->lastCommentUrl = "https://ngb.to/$strUrl";
	}


	//fetches the name of the author of the last comment for this article from
	//the provied html dom stuff. requires author and link to author profile
	public function setLastCommentAuthor($strAuthor, $strAuthorUrl)
	{
		$this->lastCommentAuthor = $strAuthor;
		//make sure to save the absolute url
		$this->lastCommentAuthorUrl =  "https://ngb.to/$strAuthorUrl";
	}


	//trys to load the image associated with an article. if there's no image in the text/html
	//the author's avatar picture is used.
	private function getArticleImage($htmlSource)
	{
		$imageUrl = FALSE;

		//fetch list of all images within the article body
		$lstImages = $htmlSource->find("#posts li.postcontainer .postbody .postcontent img");

		foreach($lstImages as $image)
		{
			//currently if author adds a photograph to the article, it
			//is externally hosted and should always start with http/s. if it doesnt, it most likely is no article foto.
			if (substr( $image->src, 0, 4 ) == "http")
			{
				//we stop at the first image that is externally hosted
				$imageUrl = $image->src;
				break;
			}
		}

		return $imageUrl;
	}


} //eof NewsEntry class


?>