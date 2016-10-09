<?php

use Cloudmanic\Craft2Laravel\Craft2Laravel;


Route::get('craft', function () {

	// First argument is the database connection we want to use, from config/db.php
	$craft2laravel = new Craft2Laravel('craft');
	
	$data = $craft2laravel->get_entries();
  
  echo '<pre>' . print_r($data, TRUE) . '</pre>';

  
  return 'done';
});