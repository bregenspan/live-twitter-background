<?

require 'class.TwitterHelper.php';
require 'class.ImageGrid.php';
require 'class.Curl.php';

$config = json_decode(file_get_contents('config.json'), true);
if (empty($config)) {
	die("Config file not found or invalid.");
}

// get recent followers and avatars from Twitter
$twh = new TwitterHelper(array_merge($config['auth'], array('followers' => 20, 'data_path' => $config['paths']['data']))); 
$twh->updateFollowerInfo();
$twh->updateFollowerAvatars();
$followers = $twh->getFollowerInfo();
$follower_grid_info = array();
foreach ($followers as $follower_info) {
	$follower_grid_info[] = array('label' => $follower_info['screen_name'], 'image' => $twh->getAvatarPath($follower_info['twitter_id']));
};

// make canvas containing background image and follower grid
$canvas = new IMagick($config['paths']['images'] . $config['display']['background_image']);

$grid = new ImageGrid( array('rows' => $config['display']['rows'],
	'margin_top' => $config['display']['grid_y'], 'background_color' => $config['display']['grid_color'],
	'font_path' => $config['paths']['fonts'], 'image_path' => $config['paths']['images'] ));
$grid->write($canvas, $follower_grid_info);

$grid->annotate($canvas, array('font_size' => '28', 'font_color' => $config['display']['grid_color'], 'font' => 'League Gothic.otf', 'font_style' => IMagick::STYLE_OBLIQUE, 'text' => 'LATEST FOLLOWERS', 'offset_y' => $config['display']['grid_y'], 'offset_x' => 25, 'angle' => 0));

// Optional display of Skype status
if (!empty($config['skype_name]'])) {
	if (true === Curl::get_binary('http://mystatus.skype.com/smallclassic/' . $config['skype_name'], 'retrieved/skype_status.png')) {
		$skype = new IMagick($config['paths']['images'] . 'skype.png');
		$status = new IMagick('retrieved/skype_status.png');
		$grid->composite(&$canvas, &$skype, IMagick::COMPOSITE_OVER, 0, $grid->height + $grid->margin_top + 16);
		$grid->composite(&$canvas, &$status, IMagick::COMPOSITE_OVER, 90, $grid->height + $grid->margin_top + 34);
	
	}

	$grid->annotate($canvas, array('font_size' => '12', 'font_color' => '#000000', 'font' => 'verdana.ttf', 'text' => $config['skype_name'], 'offset_y' => $grid->height + $grid->margin_top + 30, 'offset_x' => 98));
}

$canvas->writeImage('background.jpg');

print "Setting background...\n";
//$twh->setBackground('background.jpg');

?>
