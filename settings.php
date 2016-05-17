<?php

/**
 * WordPress settings API demo class
 *
 * @author Tareq Hasan
 */
if ( !class_exists('CranleighSmugMugSettings' ) ):
class CranleighSmugMugSettings {

    private $settings_api;

    function __construct() {
        $this->settings_api = new WeDevs_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_options_page( 'Smugmug Settings', 'Smugmug Settings', 'manage_options', 'smugmug-config', array($this, 'plugin_page') );
//		add_submenu_page('edit.php?post_type=vacancy','Vacancy Config','Config','manage_options','vacancy-config',array($this,'plugin_page'));

    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'smugmug_settings',
                'title' => __( 'Smugmug Plugin Settings', 'wedevs' )
            ),
        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
	function get_settings_fields() {
		$settings_fields = array(
			'smugmug_settings' => array(
				array(
					'name'  => 'api_key',
					'label' => __( 'API Key', 'wedevs' ),
					'desc'  => __( 'API KEY', 'wedevs' ),
					'type'  => 'text'
				),
				array(
					'name' => 'username',
					'label' => __("Username", 'webdevs'),
					"desc" => __("EG: 'cranleigh'", "cranleigh"),
					"type" => "text"
				)
            ),
        );

        return $settings_fields;
    }

    function plugin_page() {
        echo '<div class="wrap">';

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

}
$settings_api = new CranleighSmugmugSettings();
endif;