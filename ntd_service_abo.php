<?php
/*
Plugin Name: ntd Service Abo
Description: Wir halten Sie auf dem Laufenden: Welches Service Abo habe ich? Bis wann muss die Rechung beglichen werden?
Author: New Time Design
Version: 2.0.0
Author URI: https://www.new-time.ch/
*/

if( ! class_exists( 'Smashing_Updater' ) ){
	include_once( plugin_dir_path( __FILE__ ) . 'updater.php' );
}
$updater = new Smashing_Updater( __FILE__ );
$updater->set_username( 'ntd-file-share' );
$updater->set_repository( 'ntd_abo_info' );
$updater->authorize( '6f637f478f70222eb8b65efaebd6230139609c1b' );
$updater->initialize();

function contact_SOAP($action){
	require(plugin_dir_path( __FILE__ ) . 'includes/client_access.php');
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

	// available updates:
	$update_data = wp_get_update_data();
	$wp_update = $update_data['counts']['wordpress'];
	$plugin_update = $update_data['counts']['plugins'];

	/**
	* Richtiger Soapservice aufrufen.
	*/
	if ($action=="get_abo_info") {
		return $soap->get_abo_info($domain, $key);
	} elseif ($action=="check_abo_status") {
		return $soap->check_abo_status($domain, $key, $wp_update, $plugin_update);
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
	echo "<p>Hier behalten Sie Ihr ntd Service Abo im Überblick. </p>";
	echo contact_SOAP("get_abo_info");
}


// add custom css to dashboard
function dashboard_widget_display_enqueues( $hook ) {
	if( current_user_can('administrator')) {
		if( 'index.php' != $hook ) {
			return;
		}

		// wp_enqueue_style( 'ntd_widget_style', plugins_url('/includes/ntd_widget_style.css',  __FILE__  ));
		// wp_enqueue_script('ntd_abo_info_handler', plugins_url('/includes/ntd_abo_info_handler.js',  __FILE__  ));
		wp_enqueue_style( 'ntd_widget_style', 'http://ntd-testumgebung.ch/ntd_abo_info/ntd_service_abo/includes/ntd_widget_style.css' );
		wp_enqueue_script('ntd_abo_info_handler', 'http://ntd-testumgebung.ch/ntd_abo_info/ntd_service_abo/includes/ntd_abo_info_handler.js');
	}
}
add_action( 'admin_enqueue_scripts', 'dashboard_widget_display_enqueues' );
?>
