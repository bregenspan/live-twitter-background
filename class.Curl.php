<?
require_once 'lib/EpiCurl.php';

class Curl {
	public static function get_binary($url, $outfile) {
		$curl_manager = EpiCurl::getInstance();

		$get = curl_init($url);
		curl_setopt($get, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($get, CURLOPT_RETURNTRANSFER, 1);
		$request = $curl_manager->addCurl($get);

		$data = $request->data;

		if (!empty($data)) {
			$out = fopen($outfile, 'w+b');
			fwrite($out, $data);
			fclose($out);

			return true;
		}

		return false;
	}

}
?>
