<?php
	
namespace Cloudmanic\Craft2Laravel;

use DB;
	
class Craft2Laravel 
{
	private $_wheres = [];
	private $_db_connection = 'mysql';
	
	//
	// Construct....
	//
	public function __construct($_db_connection = 'mysql')
	{
		$this->_db_connection = $_db_connection;
	}
	
	//
	// Add where clause
	//
	public function add_where($key, $value, $type = '=')
	{
		$this->_wheres[] = [ 'key' => $key, 'value' => $value, 'type' => $type ];
	}
	
	//
	// Get entry by slug
	//
	public function get_entry_by_slug($entry_type = '', $slug)
	{
		$this->add_where('craft_elements_i18n.slug', $slug);
		$entries = $this->get_entries($entry_type, 1);
		return (isset($entries[0])) ? $entries[0] : false;
	}
	
	//
	// Return entries
	//
	public function get_entries($entry_type = '', $limit = 1000000, $offset = 0, $order_by = 'postDate', $sort_by = 'desc')
	{
		$db = DB::connection($this->_db_connection)->table('craft_entries');
		
		// Query the entries table.
		$db->select('craft_content.*', 'craft_elements.enabled', 'craft_users.username', 'craft_users.firstName',
		              					'craft_users.lastName', 'craft_elements.id AS element_id', 'craft_entries.postDate', 
		              					'craft_elements_i18n.slug AS slug');
		              					
		$db->join('craft_content', 'craft_entries.id', '=', 'craft_content.id');
		$db->join('craft_entrytypes', 'craft_entries.typeId', '=', 'craft_entrytypes.id');
		$db->join('craft_elements', 'craft_content.elementId', '=', 'craft_elements.id');                
		$db->join('craft_users', 'craft_entries.authorId', '=', 'craft_users.id');
		$db->join('craft_elements_i18n', 'craft_elements.id', '=', 'craft_elements_i18n.elementId');  
		$db->where('craft_entrytypes.handle', $entry_type);
		
		// Add in custom wheres
		foreach($this->_wheres AS $key => $row)
		{
			$db->where($row['key'], $row['type'], $row['value']);
		}
		
		// Clear wheres
		$this->_wheres = [];
		
		$db->orderBy($order_by, 'desc');
		$db->limit($limit);
		$db->offset($offset);
		$content = $db->get();
		
		// Add in relation data.
		foreach($content AS $key => $row)
		{
		  $relations = DB::connection($this->_db_connection)
		                ->table('craft_relations')
		                ->select('craft_assetfiles.*', 'craft_assetfolders.name AS assetfolders_name', 'craft_assetfolders.path AS assetfolders_path', 
		                          'craft_assetsources.settings AS craft_assetsources_settings', 'craft_fields.name AS craft_fields_name', 'craft_fields.handle AS craft_fields_handle')
		                ->join('craft_fields', 'craft_relations.fieldId', '=', 'craft_fields.id')
		                ->join('craft_assetfiles', 'craft_relations.targetId', '=', 'craft_assetfiles.id')
		                ->join('craft_assetfolders', 'craft_assetfiles.folderId', '=', 'craft_assetfolders.id') 
		                ->join('craft_assetsources', 'craft_assetfolders.sourceId', '=', 'craft_assetsources.id')                                    
		                ->where('craft_relations.sourceId', $row->element_id)
		                ->get();
		      
		  // Loop through relations and do some processing and merry relationship to a field. 
		  foreach($relations AS $key2 => $row2)
		  {
		    if(isset($relations[$key2]->craft_assetsources_settings) && (! empty($relations[$key2]->craft_assetsources_settings)))
		    {
		      $relations[$key2]->craft_assetsources_settings = json_decode($relations[$key2]->craft_assetsources_settings);
		    
		      // Build full url.
		      $relations[$key2]->url = $relations[$key2]->craft_assetsources_settings->urlPrefix . $relations[$key2]->craft_assetsources_settings->subfolder . $row2->filename;
		    }
		  
		    // Merry the relationship to a field.
		    if(! isset($content[$key]->{'field_' . $relations[$key2]->craft_fields_handle}))
		    {
		      $content[$key]->{'field_' . $relations[$key2]->craft_fields_handle} = [];
		    }
		    
		    $content[$key]->{'field_' . $relations[$key2]->craft_fields_handle}[] = $relations[$key2];
		  } 
		}
		
		// Return the data.
		return $content;
	}
	
	//
	// Get an asset by id
	//
	public function get_asset_by_id($id)
	{
		$db = DB::connection($this->_db_connection)->table('craft_assetfiles')
						->select('craft_assetfiles.*', 'craft_assetsources.settings')
						->join('craft_assetsources', 'craft_assetfiles.sourceId', '=', 'craft_assetsources.id') 
						->where('craft_assetfiles.id', $id);
		
		// Decode settings.
		if($data = $db->first())
		{
			if(isset($data->settings))
			{
				$data->settings = json_decode($data->settings);
			}
			
			// Build the full url.
			$data->fullUrl = $data->settings->urlPrefix . $data->settings->subfolder . $data->filename;
		}
		
		return $data;
	}
	
	// ------------------- Private Helper Functions -------------------- //
	
	//
	// Get db connection.
	//
	private function _get_db()
	{
		return DB::connection($this->_db_connection);
	}
}

/* End File */