<?php

require_once 'lib/EpiOAuth.php';
require_once 'lib/EpiCurl.php';
require_once 'lib/EpiTwitter.php';

/*
 Helper class to get recent followers and their
 avatar images
 */

class TwitterHelper {
	const FOLLOWER_FILE = 'data/followers.txt';

	const MAX_DATA_AGE = 3600; // 1 hour

	private $twitter_obj;
	private $curl_manager;
	private $max_followers;
	private $data_path;

	private $followers;
	private $follower_ids;
	private $follower_info;
	private $instantiated_at;

	function __construct($config = array()) {
		$this->twitter_obj = new EpiTwitter($config['app_key'], $config['app_secret'],
			$config['user_token'], $config['user_secret']);
			
		$this->max_followers = isset($config['followers']) ? $config['followers'] : 20;
		$this->data_path = isset($config['data_path']) ? $config['data_path'] : 'retrieved/';
		$this->curl_manager = EpiCurl::getInstance();

		$stored_data = $this->getFollowerInfoFromFile();
		$this->follower_info = !empty($stored_data['info']) ? $stored_data['info'] : array();
		$this->follower_ids = !empty($stored_data['ranking']) ? $stored_data['ranking'] : array();
		$this->instantiated_at = time();
	}

	private function getFollowerInfoFromFile() {
		if (file_exists($this->data_path . self::FOLLOWER_FILE)) {
			return unserialize(file_get_contents($this->data_path . self::FOLLOWER_FILE));
		}
		return array();
	}

	private function setFollowerInfoToFile() {
		$data = array('ranking' => $this->follower_ids, 'info' => $this->follower_info);
		file_put_contents($this->data_path . self::FOLLOWER_FILE, serialize($data));
	}

	public function getAvatarPath($twitter_id) {
		$extension = 'jpg';

		if (empty($this->follower_info[$twitter_id])) {
			die("No info was loaded for $twitter_id");
		}

		if (preg_match('/\.(gif|jpg|jpeg|png)/', $this->follower_info[$twitter_id]['profile_image'], $matches))
			$extension = $matches[1];

		return $this->data_path . "avatars/$twitter_id.$extension";
	}

	public function updateFollowerInfo() {
		try {
			$follower_ids = $this->twitter_obj->get('/followers/ids.json');
		} catch (Exception $e) {
			die('Error retrieving follower IDs');
		}

		if (!count($follower_ids['ids'])) {
			die('No followers retrieved');
		}

		$this->follower_ids = $follower_ids['ids'];

		// get follower details if needed-- TODO: use newer batch /users/lookup method
		$max_index = min(count($this->follower_ids), $this->max_followers);
		for ($i = 0; $i < $max_index; $i++) {
			$follower_id = $this->follower_ids[$i];
			if (empty($this->follower_info[$follower_id]) || $this->follower_info[$follower_id]['updated'] < $this->instantiated_at - self::MAX_DATA_AGE) {
				$info = $this->twitter_obj->get('/users/show.json', array("user_id" => $follower_id));
				$this->follower_info[$follower_id] = array('profile_image' => $info->profile_image_url, 'screen_name' => $info->screen_name, 'updated' => time());

			} else {
				print "Info already updated for $follower_id\n";
			}
			$i++;
		}

		// clean old follower info
		$stored_followers_ids = array_keys($this->follower_info);
		$count = count($stored_followers_ids);

		for ($i = 0; $i < $count; $i++) {
			if (!in_array($stored_followers_ids[$i], $this->follower_ids)) {
				try {
					unlink($this->getAvatarPath($stored_followers_ids[$i]));
				} catch (Exception $e) {
					// unlink failed...
				}
				unset($this->follower_info[$stored_followers_ids[$i]]);
			}
		}

		$this->setFollowerInfoToFile();
	}

	public function setBackground($image_path) {
		$filename = basename($image_path);
		$directory = realpath(dirname($image_path));
		$absolute_path = '@' . $directory . '/' . $filename;
		try {
			$this->twitter_obj->post('/account/update_profile_background_image.json', array('@image' => $absolute_path ));
		} catch (EpiTwitterException $e) {
			print "Error setting background! Details: \n";
			print_r($e);
		}
	}

	public function getFollowerInfo() {
		$ordered = array();

		foreach ($this->follower_ids as $follower_id) {
			if (!empty($this->follower_info[$follower_id])) {
				$info = $this->follower_info[$follower_id];
				$info['twitter_id'] = $follower_id;
				$ordered[] = $info;
			}
		}

		return $ordered;
	}

	public function updateFollowerAvatars() {
		$image_requests = array();

		foreach ($this->follower_info as $follower_id => $follower) {
			if (empty($follower))
				die('Encountered empty follower object');

			$avatar_file = $this->getAvatarPath($follower_id);

			// already fresh avatar?
			if (file_exists($avatar_file) && (filesize($avatar_file)) && filemtime($avatar_file) > $this->instantiated_at - self::MAX_DATA_AGE) {
				// print "$avatar_file was fresh already\n";
				continue;
			}

			if (!empty($follower['profile_image'])) {
				// get profile image
				$get = curl_init($follower['profile_image']);
				curl_setopt($get, CURLOPT_BINARYTRANSFER, 1);
				curl_setopt($get, CURLOPT_RETURNTRANSFER, 1);

				$image_requests[$follower_id] = $this->curl_manager->addCurl($get);
			}
		}

		foreach ($image_requests as $follower_id => $image_request) {
			$avatar_file = $this->getAvatarPath($follower_id);
			$out = fopen($avatar_file, 'w+b');
			fwrite($out, $image_request->data);
			fclose($out);
			// print 'Wrote ' . $follower_id . "\n";
		}
	}

}
