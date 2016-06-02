<?php
/*
Plugin Name: Cranleigh Smug Mug Integration
Plugin URI: http://www.cranleigh.org
Description: This plugin uses a php Smugmug class wrapper written by github.com/lildude.
Author: Fred Bradley
Version: 1.0
Author URI: http://fred.im/
*/
require_once(dirname(__FILE__).'/settingsapiwrapper.php');
require_once(dirname(__FILE__).'/settings.php');
class Cranleigh_SmugMug_API {

	public $username = '';
	public $options = array('AppName' => "Cranleigh School", '_verbosity'=>1);
	function __construct() {
		$wordpress_settings = get_option('smugmug_settings', array('username'=>'cranleigh', 'api_key'=>'lKDuCaYbAGRdkTgnhbbLcgCK7EddinlQ'));
		$this->username = $wordpress_settings['username'];
		require_once(dirname(__FILE__).'/phpSmug/vendor/autoload.php');
		$this->smug = new phpSmug\Client($wordpress_settings['api_key'], $this->options);
		add_shortcode("smugmug_photos", array($this, 'shortcode'));
	}
	
	function shortcode($atts, $content=null) {
		$a = shortcode_atts(array(
			"path" => null
		), $atts);
		
		return $this->get_highlight_image($a['path']);
	}
	
	function fixpath($p) {
		$p=str_replace('\\','/',trim($p));
		return (substr($p,-1)!='/') ? $p.='/' : $p;
	}
	/**
	 * get_highlight_image function.
	 * 
	 * @access public
	 * @param string $path (default: "/2015-2016/Sport/Athletics/Atheletics-Bracknell-April-30")
	 * @return void
	 */
	function get_highlight_image($path="/2015-2016/Sport/Athletics/Atheletics-Bracknell-April-30") {
		if (strpos($path, "cranleigh.smugmug.com")) {
			$ex = explode("cranleigh.smugmug.com", $path);
			$path = rtrim($ex[1], '/');
		}
		$path = '/'.ltrim($path, '/');

		$api = $this->smug->get("user/{$this->username}!urlpathlookup?urlpath=".$path);

		if ($api->Locator=="Folder,Album,Page") {
			// If the locator is this, then the API will break, so lets quit now!
			return $this->output_display(false);
		}

		if (is_object($api->{$api->Locator})):
			if (isset($api->{$api->Locator}->Title)) {
				$title = $api->{$api->Locator}->Title;
			} else {
				$title = "Latest Photos";
			}
			$weburi = $api->{$api->Locator}->WebUri;
			$highlight_img = $api->{$api->Locator}->Uris->HighlightImage;
			$highlight_img = $this->smug->get($highlight_img);
			if (is_object($highlight_img->{$highlight_img->Locator})):
				$thumb = $highlight_img->{$highlight_img->Locator}->ThumbnailUrl;
				$hack = explode("/Th/", $thumb);
				$image_url = $hack[0]."/M/".$hack[1];
				
				$return = new stdClass();
				$return->title = $title;
				$return->thumb = $thumb;
				$return->image = $image_url;
				$return->uri	= $weburi;
				return $this->output_display($return);
			endif;
		endif;
		return false;
	}
	
	
	/**
	 * output_display function.
	 * 
	 * @access public
	 * @param mixed $image_obj
	 * @return void
	 */
	function output_display($image_obj) {
		?>
		<div class="cs_smugmug_container">
		<?php
		if ($image_obj===false):
			echo "<h3 class=\"cs_smugmug_title\">Latest Photos</h3>";
			
		else:
		?>
			<h3 class="cs_smugmug_title"><?php echo $image_obj->title; ?></h3>
			<a href="<?php echo $image_obj->uri; ?>" target="_blank">
				<img class="img-responsive" src="<?php echo $image_obj->image; ?>" />
			</a>
			
		<?php
		endif;
		?>
		<p>View, download and purchase the best photos on our Smugmug.</p>
		<a href="<?php echo $image_obj->uri; ?>" class="cs_smugmug_button">View and Purchase</a>
		</div>
		
		<?php
	}
	
	
	/**
	 * get_sport_galleries function.
	 * 
	 * IN DEVELOPMENT
	 *
	 * @access public
	 * @param string $year (default: "2015-2016")
	 * @param string $sport (default: "Athletics")
	 * @return void
	 */
	function get_sport_galleries($year="2015-2016", $sport="Athletics") {
		$api = $this->smug->get("user/{$this->username}!urlpathlookup?urlpath=/".$year."/Sport/".$sport);
		if (is_object($api->{$api->Locator})) {
			$data = $api->{$api->Locator};
			if (is_object($data->Uris)) {
				$new_api_call = $this->smug->get($data->Uris->AlbumList);
				if (($new_api_call->AlbumList)) {
					$albumns = array();
					foreach ($new_api_call->AlbumList as $album):
						$albums[] = $this->smug->get($album->Uri);
					endforeach;
					foreach ($albums as $album):
						$highlight_img = $album->Album->Uris->AlbumHighlightImage;
						$highlight_imgs[] = $this->smug->get($highlight_img);
					endforeach;
					foreach ($highlight_imgs as $image):
						$thumbnail = $image->{$image->Locator}->ThumbnailUrl;
						$hack = explode("/Th/", $thumbnail);
						$new_image = $hack[0]."/M/".$hack[1];
						$images[] = $new_image;
					endforeach;
					$output = "";
					foreach ($images as $image):
						$output .= "<img src=\"".$image."\" />";
					endforeach;;
					return $output;
				}
			}
		}
		return $api;
//		http://www.smugmug.com/api/v2/user/cranleigh!urlpathlookup?urlpath=%2F2015-2016%2FSport%2FAthletics
	}
}
$smugapi = new Cranleigh_SmugMug_API();
