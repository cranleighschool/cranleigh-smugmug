<?php
/*
Plugin Name: Cranleigh Smug Mug Integration
Plugin URI: http://www.cranleigh.org
Description: This plugin uses a php Smugmug class wrapper written by github.com/lildude.
Author: Fred Bradley
Version: 2.1
Author URI: http://fred.im/
*/

require_once(dirname(__FILE__).'/settingsapiwrapper.php');
require_once(dirname(__FILE__).'/settings.php');

class Cranleigh_SmugMug_API {

	public $username = '';
	public $options = array('AppName' => "Cranleigh School", '_verbosity'=>1);


	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$wordpress_settings = get_option(
			'smugmug_settings',
			array(
				'username'=>'dummy_username',
				'api_key'=>'dummy_api_key'
			)
		);

		$this->api_key = $wordpress_settings['api_key'];
		$this->username = $wordpress_settings['username'];

		add_shortcode("smugmug_photos", array($this, 'shortcode'));
		add_shortcode("smugmug", array($this, 'shortcode'));
		add_shortcode("smugmug_latest", array($this, 'latest_galleries'));
		add_action('wp_enqueue_scripts',array($this,'enqueue_styles'));
		add_action('media_buttons', array($this, 'add_media_button'), 900);
		add_action('wp_enqueue_media', array($this, 'include_media_button_js_file'));
		add_action( 'admin_print_footer_scripts', array( $this, 'add_mce_popup' ) );

	}

	function enqueue_styles() {
//		wp_register_style('font-awesome', plugins_url('font-awesome-4.6.3/css/font-awesome.min.css', __FILE__));
	}

	private function showAlbumHighlightImage(string $thumb, string $name, string $uri, $lastUpdated) {
		ob_start();

		?>
		<div class="card landscape">
			<div class="row">
				<div class="col-xs-4">
					<div class="card-image">
						<a href="<?php echo $uri; ?>" target="_blank"><img src="<?php echo $thumb; ?>" alt="<?php echo $name; ?>" title="<?php echo $name; ?>" /></a>
					</div>
				</div>
				<div class="col-xs-8">
					<div class="card-text">
						<p>
							<a href="<?php echo $uri; ?>" target="_blank"><?php echo $name; ?></a>
						</p>
						<p><small>Updated: <strong><?php echo date("D j M Y", strtotime($lastUpdated)); ?></strong></small></p>
					</div>
				</div>
			</div>
		</div>

		<?php
		$return = ob_get_contents();
		ob_end_clean();

		return $return;
	}

	private function cleanAlbumObj($album) {
		$keep = [
			"Name","Title","AlbumKey","Description","WebUri","NodeID","ImagesLastUpdated"
		];
		foreach (array_keys((array) $album) as $key):
			if (!in_array($key, $keep)) {
				unset($album->$key);
			}
		endforeach;
		return $album;
	}

	private function getCleanHighlightNode($nodeID) {
		$obj = $this->smug->get("highlight/node/".$nodeID);

		$keep = [
			"Image"
		];
		foreach(array_keys((array) $obj) as $key):
			if (!in_array($key, $keep)) {
				unset($obj->$key);
			}
		endforeach;
		return $obj;
	}
	public function latest_galleries($atts, $content=null) {
		require_once(dirname(__FILE__).'/vendor/autoload.php');
		$this->smug = new phpSmug\Client($this->api_key, $this->options);
		try {
			if (!get_transient( 'sm_latest_albums' )):
				$albums = [];
				$api = $this->smug->get("user/{$this->username}!albums?count=12&Order=Date Added (Descending)");
				foreach ($api->Album as $album):
					$album = $this->cleanAlbumObj($album);
					$album->ObjHighlightImage = $this->getCleanHighlightNode($album->NodeID);

					array_push($albums, $album);
				endforeach;
				set_transient( 'sm_latest_albums', $albums, HOUR_IN_SECONDS );
			else:
				$albums = get_transient( 'sm_latest_albums' );
			endif;

			$output = "<div class=\"row smugmug-grid\">";

			foreach ($albums as $key => $album):
				$image = $album->ObjHighlightImage;
				$thumb = $image->Image->ThumbnailUrl;
				$name = $album->Name;
				$title = $album->Title;
				$uri = $album->WebUri;
				$lastUpdated = $album->ImagesLastUpdated;

				if ($key % 2 == 0) {
					$output.= "</div><div class=\"row\">";
				}
				$output .= "<div class=\"col-lg-6 col-sm-12 thumb-photo\">";
				$output .= $this->showAlbumHighlightImage($thumb, $name, $uri, $lastUpdated);
				$output .= "</div>";

			endforeach;

			$output .= "</div>";
			return $output;
		} catch (Exception $e) {
			echo "<div class=\"alert alert-warning\">".$e->getMessage()."</div>";
			return false;
		}
	}

	/**
	 * shortcode function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return void
	 */
	function shortcode($atts, $content=null) {
		//wp_enqueue_style('font-awesome');
		//wp_enqueue_style('dashicons');
		add_action('wp_footer', array($this, 'google_event_tracking'));


		require_once(dirname(__FILE__).'/phpSmug/vendor/autoload.php');

		$this->smug = new phpSmug\Client($this->api_key, $this->options);

		$a = shortcode_atts(array(
			"path" => null
		), $atts, 'smugmug');

		return $this->get_highlight_image($a['path']);
	}


	/**
	 * fixpath function.
	 *
	 * @access public
	 * @param mixed $p
	 * @return void
	 */
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
		$url = parse_url($path);
		if (!in_array($url['host'], ['cranleigh.smugmug.com', 'cranprep.smugmug.com', 'smugmug.cranleigh.org', 'smugmug.cranprep.org'])) {
			error_log("Trying to use plugin to link to Non Cranleigh Smugmug account. This is not allowed.");
			return false;
		}

		$path = trim($url['path'], '/');
		$path = '/'.ltrim($path, '/');

		try {
			$api = $this->smug->get("user/{$this->username}!urlpathlookup?urlpath=".$path);
		} catch (Exception $e) {
			echo 'Error: Incorrect Smugmug Credentials';
			return false;
		}
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
		global $post;
		if ($image_obj===false)
			return false;

//a target="_blank" class="tracked" data-action="Image Click" data-category="Calendar" data-label="https://parents.cranleigh.org/visit/calendar/" href="https://parents.cranleigh.org/visit/calendar/"

		$output = '<div class="cs_smugmug_container">';

		if ($image_obj===false):
			$widget_title = "Latest Photos";
			$output .= "<h3 class=\"cs_smugmug_title\">".$widget_title."</h3>";

		else:
			$widget_title = $image_obj->title;
			$output .= '<h3 class="cs_smugmug_title">'.$widget_title.'</h3>';
			$output .= '<a href="'.$image_obj->uri.'" class="tracked" target="_blank" data-action="Smugmug" data-category="'.$post->post_name.'" data-label="'.$widget_title.'">';
			$output .= '<img class="img-responsive" src="'.$image_obj->image.'" />';
			$output .= '</a>';


		endif;
		$output .= '<p>View, download or purchase the best photos on our Smugmug.</p>';
		$output .= '<a data-action="Smugmug" data-category="'.$post->post_name.'" data-label="'.$widget_title.'" target="_blank" href="'.$image_obj->uri.'" class="cs_smugmug_button tracked">Visit Site <i class="fa fa-fw fa-external-link"></i></a>';
		$output .= '<div class="clear clearfix"></div></div>';

		return $output;
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
/*		http://www.smugmug.com/api/v2/user/cranleigh!urlpathlookup?urlpath=%2F2015-2016%2FSport%2FAthletics */
	}


	function add_media_button() {
		echo '<style>.wp-media-buttons .smugmug_insert span.wp-media-buttons-icon:before {
			font:400 18px/1 dashicons;
			content:"\f306";
			} </style>';
		echo '<a href="#" class="button smugmug_insert" id="add_smugmug_shortcode"><span class="wp-media-buttons-icon"></span>' . esc_html__( 'Smugmug', 'cranleigh' ) . '</a>';

	}

	function include_media_button_js_file() {
		wp_enqueue_script('media_button', plugins_url('popme.js', __FILE__), array('jquery'), time(), true);
	}

	function add_mce_popup() {
		?>
		<script>

			function SmugmugInsertShortcode(){

				var smugmug_url = jQuery("#smugmug_url").val();
				smugmug_url = smugmug_url.trim();
				if (smugmug_url.substr(0,4) != "http") {
					alert(<?php echo json_encode( __( 'Please enter a valid URL, ensuring it starts with https://', 'cranleigh' ) ); ?>);
					return;
				}
				window.send_to_editor("[smugmug path=\"" + smugmug_url + "\"]");
				return;

    }
		</script>

		<div id="insert_smugmug" style="display:none;">
			<div id="insert_smugmug_wrapper" class="wrap">
				<div id="insert-smugmug-container">
					<label>Enter the full SmugMug URL:</label><input type="text" id="smugmug_url" style="padding:10px;width:100%;border-radius: 5px; font-size:1.4em;" placeholder="Smugmug URL" />
					<br /><small>eg: https://cranleigh.smugmug.com/2015-2016/Sport/Hockey/Hockey-Common-Room-v-Upper/</small>
					<div style="padding:15px;">
						<input type="button" class="button-primary" value="Insert Shortcode" onclick="SmugmugInsertShortcode();"/>
						<a class="button" href="#" onclick="tb_remove(); return false;"><?php _e("Cancel", "js_shortcode"); ?></a>
        			</div>
        			<br />
        			<br />
        			<strong>Reminder: please always check the output of the page to ensure that you have successfully added the Smugmug widget!</strong>

				</div>
			</div>
		</div>

	<?php
	}

	function google_event_tracking() {
		?>
		<script type="text/javascript">

			jQuery(document).ready(function() {


				jQuery('.cs_smugmug_container a.tracked').click(function() {
					var action 		= jQuery(this).data("action");
					var category 	= jQuery(this).data("category");
					var label 		= jQuery(this).data("label");
					ga('send', 'event',  action, category, label);
				});

			});
		</script>

		<?php
	}

}
$smugapi = new Cranleigh_SmugMug_API();

