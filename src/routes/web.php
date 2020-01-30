<?php

Route::get('hello', function(){
	echo 'Hello from the laravel-modelmap package!';
});
Route::get('timezones/{timezone}', 'luria\laravel-modelmap\ModelmapController@index');