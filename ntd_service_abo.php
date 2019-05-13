<?php
/*
Plugin Name: ntd Service Abo
Description: Wir halten Sie auf dem Laufenden: Welches Service Abo habe ich? Bis wann muss die Rechung beglichen werden?
Author: New Time Design
Version: 4.0.0
Author URI: https://www.new-time.ch/
*/
register_activation_hook(__FILE__,'myplugin_activation');
/* The deactivation hook is executed when the plugin is deactivated */
register_deactivation_hook(__FILE__,'myplugin_deactivation');
/* This function is executed when the user activates the plugin */
function myplugin_activation(){
	// wp_schedule_event(time(), 'daily', 'daily_check_for_github_updates');
	wp_schedule_event(strtotime('06:00:00'), 'daily', 'daily_check_for_github_updates');
}
/* This function is executed when the user deactivates the plugin */
function myplugin_deactivation(){
	wp_clear_scheduled_hook('daily_check_for_github_updates');
}
/* We add a function of our own to the daily_check_for_github_updates action.*/
// add_action('daily_check_for_github_updates','perform_update_check');
add_action('init','perform_update_check');
/* This is the function that is executed by the hourly recurring action daily_check_for_github_updates */
function perform_update_check(){
	contact_SOAP("register_update_check");

	// // check for available updates:
	// $update_data = wp_get_update_data();
	// update_option('wp_update', $update_data['counts']['wordpress']);
	// update_option('plugin_update', $update_data['counts']['plugins']);
}

add_action( 'daily_check_for_github_updates', 'github_plugin_updater_init' );
function github_plugin_updater_init() {

	include_once 'updater.php';
	define( 'WP_GITHUB_FORCE_UPDATE', true );
	if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin
		$config = array(
			'slug' => plugin_basename( __FILE__ ),
			'proper_folder_name' => dirname( plugin_basename( __FILE__ ) ),
			'api_url' => 'https://api.github.com/repos/ntd-file-share/ntd_abo_info',
			'raw_url' => 'https://raw.github.com/ntd-file-share/ntd_abo_info/master',
			'github_url' => 'https://github.com/ntd-file-share/ntd_abo_info',
			'zip_url' => 'https://github.com/ntd-file-share/ntd_abo_info/archive/master.zip',
			'sslverify' => true,
			'requires' => '3.0',
			'tested' => '3.3',
			'readme' => '/includes/README.txt',
			'access_token' => '',
		);
		new WP_GitHub_Updater( $config );
	}
	// // Aktuelle Updates übermitteln
	// contact_SOAP("register_update_check");
}

function contact_SOAP($action){
	// require(plugin_dir_path( __FILE__ ) . 'includes/client_access.php');
	if (isset($_POST["ntd_authentication_key"])) {
		update_option('ntd_authentication_key', $_POST["ntd_authentication_key"]);
	}
	if (get_option("ntd_authentication_key")) {
		$key = get_option("ntd_authentication_key");
	} else {
		$key = "";
	}
	$domain=home_url();
	/**
	* SOAP Service aufrufen.
	*/
	$soap = new SoapClient(null, array(
		"location" => "http://ntd-testumgebung.ch/ntd_abo_info/ntd_abo_soap_service.php",
		"uri" => "http://ntd-testumgebung.ch/ntd_abo_info",
		'encoding' => 'UTF-8', // Zeichensatz
		'soap_version' => SOAP_1_2
	));

	/**
	* Richtiger Soapservice aufrufen.
	*/
	if ($action=="get_abo_info") {
		return $soap->get_abo_info($domain, $key);
	} elseif ($action=="check_abo_status") {
		return $soap->check_abo_status($domain, $key);
	} elseif ($action == "register_update_check") {
		// available updates:
		$update_data = wp_get_update_data();
		$wp_update = $update_data['counts']['wordpress'];
		$plugin_update = $update_data['counts']['plugins'];
		// $wp_update = get_option("wp_update");
		// $plugin_update = get_option("plugin_update");
		$soap->register_update_check($domain, $wp_update, $plugin_update);
	}
}

/**
* Add a widget to the dashboard.
*
* This function is hooked into the 'wp_dashboard_setup' action below.
*/
function add_dashboard_widgets() {
	if( current_user_can('administrator')) {
		wp_add_dashboard_widget(
			contact_SOAP("check_abo_status"),         // Widget slug.
			'new time design Abo Info',         // Title.
			'display_abo_info' // Display function.
		);
	}
}
add_action( 'wp_dashboard_setup', 'add_dashboard_widgets' );

/**
* Create the function to output the contents of the Dashboard Widget.
*/
function display_abo_info() {
	// ntd logo as svg code as part of the later return value
	$address = "<div><p><span class='new_time_design'> new time design</span><br> <b>Ihre IT-, Web- und Medien-Agentur</b> <br> Hauptstrasse 94a <br> 9434 Au SG <br> <span>Tel: </span><a href='tel:071 744 81 30'>071 744 81 30 </a><br> <span>Mail: </span><a href='mailto:info@new-time.ch'>info@new-time.ch</a></p></div>";
	$logo = '<div>
	<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Ebene_1" x="0px" y="0px" width="200px" height="120px" viewBox="0 0 200 120" style="enable-background:new 0 0 200 120;" xml:space="preserve">
	<style type="text/css">
	.st0{fill:#9FA4A8;}
	.st1{fill:#59DB00;}
	</style>
	<g>
	<g>
	<g>
	<path class="st0" d="M0,98.6h2.4v2.4l0,0c1.2-1.6,2.8-2.8,4.8-2.8c3.6,0,4.8,2,4.8,5.2v9.6H9.6v-9.6c0-1.6-1.2-2.8-2.8-2.8     c-2.8,0-4.4,2-4.4,4.4v8H0V98.6z"/>
	<path class="st0" d="M27.5,108.2c-0.8,3.2-2.8,4.8-6,4.8c-4.4,0-6.8-3.2-6.8-7.6s2.8-7.6,6.8-7.6c5.2,0,6.8,4.8,6.4,8.4H17.2     c0,2.4,1.2,4.8,4.4,4.8c2,0,3.2-0.8,3.6-2.8L27.5,108.2L27.5,108.2z M25.5,104.2c0-2.4-2-4-4-4c-2.4,0-4,2-4,4H25.5z"/>
	<path class="st0" d="M44.7,113h-2.4l-2.8-11.6l0,0L36.7,113h-3.2l-4.8-14.4h2.8l3.2,11.6l0,0l2.8-11.6h2.4l3.2,11.6l0,0l3.2-11.6     h2.4L44.7,113z"/>
	<path class="st0" d="M62.3,98.6h2.8v2h-2.8v8.8c0,1.2,0.4,1.2,1.6,1.2h1.2v2h-2c-2.4,0-3.6-0.4-3.6-3.2v-9.2h-2.4v-2h2.4v-4.4     h2.4L62.3,98.6L62.3,98.6z"/>
	<path class="st0" d="M70.3,95.8h-2.4V93h2.4V95.8z M67.9,98.6h2.4V113h-2.4V98.6z"/>
	<path class="st0" d="M74.3,98.6h2.4v2l0,0c1.2-1.6,2.8-2.4,4.8-2.4c1.6,0,3.2,0.8,4,2.4c0.8-1.6,2.8-2.4,4.4-2.4     c2.8,0,4.8,1.2,4.8,4v10.4h-2.8V103c0-1.6-0.4-3.2-2.8-3.2s-3.6,1.6-3.6,3.6v8.8H83v-9.6c0-2-0.4-3.2-2.8-3.2c-2.8,0-4,2.4-4,3.6     v8.8h-2.4L74.3,98.6L74.3,98.6z"/>
	<path class="st0" d="M110.2,108.2c-0.8,3.2-2.8,4.8-6,4.8c-4.4,0-6.8-3.2-6.8-7.6s2.8-7.6,6.8-7.6c5.2,0,6.8,4.8,6.4,8.4H99.8     c0,2.4,1.2,4.8,4.4,4.8c2,0,3.2-0.8,3.6-2.8L110.2,108.2L110.2,108.2z M107.8,104.2c0-2.4-2-4-4-4c-2.4,0-4,2-4,4H107.8z"/>
	<path class="st0" d="M133.3,113h-2.4v-2l0,0c-0.8,1.6-2.8,2.4-4.4,2.4c-4.4,0-6.8-3.6-6.8-7.6c0-4,2-7.6,6.4-7.6     c1.6,0,3.6,0.4,4.8,2.4l0,0V93h2.4V113z M126.5,111c3.2,0,4.4-2.8,4.4-5.6s-1.2-5.6-4.4-5.6s-4.4,2.8-4.4,5.6     C122.2,108.6,123.4,111,126.5,111z"/>
	<path class="st0" d="M148.9,108.2c-0.8,3.2-2.8,4.8-6,4.8c-4.4,0-6.8-3.2-6.8-7.6s2.8-7.6,6.8-7.6c5.2,0,6.8,4.8,6.4,8.4h-10.8     c0,2.4,1.2,4.8,4.4,4.8c2,0,3.2-0.8,3.6-2.8L148.9,108.2L148.9,108.2z M146.9,104.2c0-2.4-2-4-4-4c-2.4,0-4,2-4,4H146.9z"/>
	<path class="st0" d="M153.3,108.2c0,2,2,2.8,4,2.8c1.6,0,3.6-0.4,3.6-2c0-2-2.4-2-4.8-2.8s-4.8-1.2-4.8-4c0-2.8,2.8-4,5.2-4     c3.2,0,5.6,1.2,6,4.4h-2.4c0-2-1.6-2.4-3.2-2.4c-1.6,0-3.2,0.4-3.2,2c0,1.6,2.4,2,4.8,2.4c2.4,0.4,4.8,1.2,4.8,4     c0,3.6-3.2,4.4-6,4.4c-3.2,0-6-1.2-6-4.8H153.3z"/>
	<path class="st0" d="M168.1,95.8h-2.4V93h2.4V95.8z M165.7,98.6h2.4V113h-2.4V98.6z"/>
	<path class="st0" d="M184,111.8c0,4.8-2,7.2-6.8,7.2c-2.8,0-6-1.2-6-4.4h2.4c0,1.6,2,2.4,3.6,2.4c3.2,0,4.4-2.4,4.4-5.6v-0.8l0,0     c-0.8,1.6-2.8,2.8-4.4,2.8c-4.4,0-6.4-3.2-6.4-7.2c0-3.2,1.6-7.6,6.8-7.6c2,0,3.6,0.8,4.4,2.4l0,0v-2h2.4L184,111.8L184,111.8z      M182,105.4c0-2.4-1.2-5.2-4-5.2c-3.2,0-4.4,2.4-4.4,5.2c0,2.4,0.8,5.6,4,5.6C180.8,111,182,108.2,182,105.4z"/>
	<path class="st0" d="M187.6,98.6h2.4v2.4l0,0c1.2-1.6,2.8-2.8,4.8-2.8c3.6,0,4.8,2,4.8,5.2v9.6h-2.4v-9.6c0-1.6-1.2-2.8-2.8-2.8     c-2.8,0-4.4,2-4.4,4.4v8h-2.4V98.6L187.6,98.6z"/>
	</g>
	</g>
	<g>
	<path class="st1" d="M89.4,31.9h21.2c0.8-4.4,4-9.2,10-13.2H85.4V0H71.1v13.6C79,15.2,87.4,24,89.4,31.9z"/>
	<path class="st1" d="M185.6,0v20.8c-2.4-1.6-6-2-10-2h-33.5c-20,0-27.5,12-27.5,24.8v11.2c0,2,1.2,4.8,2.8,6.4h13.6    c-1.2-1.6-2-3.6-2-6.4V43.1c0-5.6,6-11.6,14-11.6h31.9c6.4,0,10.8,3.2,10.8,8.4v14.8c0,7.6-2.4,12.4-10.8,12.4H97    c-6,0-12-5.2-12-12.4V43.9c0-13.2-8.4-25.1-24.8-25.1H26.3C9.2,18.8,0,31.5,0,43.9v36.3h14.4V43.9c0-6.8,5.2-12,13.2-12h31.9    c6.8,0,11.6,3.6,11.6,13.6l0,0v10c0,11.2,7.2,25.1,25.9,25.1h82.2c14.8,0,20.8-12.4,20.8-25.1V0H185.6z"/>
	</g>
	</g>
	</svg>
	</div>';
	// $update_data = wp_get_update_data();
	// $wp_update = $update_data['counts']['wordpress'];
	// $plugin_update = $update_data['counts']['plugins'];
	// echo $wp_update."<br>".$plugin_update;
	// echo 	contact_SOAP("register_update_check");
	// echo get_option("wp_update");
	echo "<p>Hier behalten Sie Ihr ntd Service Abo im Überblick. </p>";
	echo contact_SOAP("get_abo_info");
	echo "<div id='ntd_address'>".$address.$logo."</div>";
}


// add custom css to dashboard
function dashboard_widget_display_enqueues( $hook ) {
	if( current_user_can('administrator')) {
		if( 'index.php' != $hook ) {
			return;
		}

		wp_enqueue_style( 'ntd_widget_style', plugins_url('/includes/ntd_widget_style.css',  __FILE__  ));
		wp_enqueue_script('ntd_abo_info_handler', plugins_url('/includes/ntd_abo_info_handler.js',  __FILE__  ));
		// wp_enqueue_style( 'ntd_widget_style', 'http://ntd-testumgebung.ch/ntd_abo_info/ntd_service_abo/includes/ntd_widget_style.css' );
		// wp_enqueue_script('ntd_abo_info_handler', 'http://ntd-testumgebung.ch/ntd_abo_info/ntd_service_abo/includes/ntd_abo_info_handler.js');
	}
}
add_action( 'admin_enqueue_scripts', 'dashboard_widget_display_enqueues' );
?>
