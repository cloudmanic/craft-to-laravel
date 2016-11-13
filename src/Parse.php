<?php
	
namespace Cloudmanic\Craft2Laravel;

class Parse
{
	public $craft2laravel = null;
	
	//
	// Construct...
	//
	public function __construct()
	{
		$this->craft2laravel = new Craft2Laravel('craft');
	}
	
	//
	// Get an instance of Parse.
	//
	public static function instance()
	{
		return new Parse();	
	}
	
	//
	// Parse the text we pass in to see if there is any replacing we need to do.
	//
	public function run($str)
	{
		$this->parse_craft_asset_tag($str);
		
		return $str;
	}
	
	//
	// Parse for Craft asset tags.
	//
	// {asset:317:url}
	//
	public function parse_craft_asset_tag(&$str)
	{
		// See if we have any matches
		preg_match_all('/{asset\:(\d+)\:url}/', $str, $matches);
		
		// Loop through and replace the tag with the html.
		foreach($matches[0] AS $key => $row)
		{			
			// Query the database and get the asset path.
			$asset = $this->craft2laravel->get_asset_by_id($matches[1][$key]);

			// Replace the tag with the html			
			$str = str_replace($row, $asset->fullUrl, $str);			
		}
	} 
}

/* End File */