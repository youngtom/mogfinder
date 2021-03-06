<?php

return array(
	'bnet_api_key' => env('BNET_API_KEY', true),
	'bnet_api_locale' => 'en_US',
	'bnet_api_base_url_default' => 'https://us.api.battle.net/wow',
	'bnet_api_cache_expiration' => (60*60*24*90), // 90 days
	'bnet_max_item_id' => 142021,
	'bnet_item_renders_base_url' => 'http://media.blizzard.com/wow/renders/items/',
	'upload_file_dir' => 'app/uploads',
	'download_file_dir' => 'app/downloads',
	'temp_file_dir' => 'app/temp'
);

?>