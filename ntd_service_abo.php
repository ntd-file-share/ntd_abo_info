<?php
/*
Plugin Name: ntd Service Abo
Description: Wir halten Sie auf dem Laufenden: Welches Service Abo habe ich? Bis wann muss die Rechung beglichen werden?
Author: New Time Design
Version: 9.0
Author URI: https://www.new-time.ch/
GitHub Plugin URI: ntd-file-share/ntd_abo_info
*/

register_activation_hook(__FILE__,'myplugin_activation');
/* The deactivation hook is executed when the plugin is deactivated */
register_deactivation_hook(__FILE__,'myplugin_deactivation');
/* This function is executed when the user activates the plugin */
function myplugin_activation(){
	// wp_schedule_event(time(), 'daily', 'daily_update_information');
	wp_schedule_event(strtotime('06:00:00'), 'daily', 'daily_update_information');
	// perform_update_check();
}
/* This function is executed when the user deactivates the plugin */
function myplugin_deactivation(){
	wp_clear_scheduled_hook('daily_update_information');
}
/* We add a function of our own to the daily_update_information action.*/
add_action('daily_update_information','perform_update_check');
// add_action('init','perform_update_check');
/* This is the function that is executed by the hourly recurring action daily_update_information */
function perform_update_check(){
	contact_SOAP("register_update_check");

	// // check for available updates:
	// $update_data = wp_get_update_data();
	// update_option('wp_update', $update_data['counts']['wordpress']);
	// update_option('plugin_update', $update_data['counts']['plugins']);
	// update_option('letzter_check', date('Y-m-d'));
}


function get_core_updates_intern( $options = array() ) {
	$options   = array_merge(
		array(
			'available' => true,
			'dismissed' => false,
		),
		$options
	);
	$dismissed = get_site_option( 'dismissed_update_core' );

	if ( ! is_array( $dismissed ) ) {
		$dismissed = array();
	}

	$from_api = get_site_transient( 'update_core' );

	if ( ! isset( $from_api->updates ) || ! is_array( $from_api->updates ) ) {
		return false;
	}

	$updates = $from_api->updates;
	$result  = array();
	foreach ( $updates as $update ) {
		if ( $update->response == 'autoupdate' ) {
			continue;
		}

		if ( array_key_exists( $update->current . '|' . $update->locale, $dismissed ) ) {
			if ( $options['dismissed'] ) {
				$update->dismissed = true;
				$result[]          = $update;
			}
		} else {
			if ( $options['available'] ) {
				$update->dismissed = false;
				$result[]          = $update;
			}
		}
	}
	return $result;
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
	} elseif ($action == "register_update_check") {

		// check if there is a new version of wordpress
		$WordPress_Core_Updates = get_core_updates_intern( array( 'dismissed' => false ) );
		if ( ! empty( $WordPress_Core_Updates ) && ! in_array( $WordPress_Core_Updates[0]->response, array( 'development', 'latest' ) ) ) {
			$wp_update = 1;
		} else {
			$wp_update = 0;
		}
		// get number of available plugin updates
		$update_plugins = get_site_transient( 'update_plugins' );
		if ( ! empty( $update_plugins->response ) ) {
			$plugin_update = count( $update_plugins->response );
		} else {
			$plugin_update = 0;
		}
		// get current version of this plugin
		$file_info = get_file_data(plugin_dir_path( __FILE__ )."ntd_service_abo.php", array('Version'), '');

		// send information of current situation for updating the database
		$soap->register_update_check($domain, $wp_update, $plugin_update, $file_info[0]);
	}
}

function formatDate($dateString){
	$timestamp = strtotime($dateString);
	$months = array('','Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember');
	if ($timestamp) {
		// $formattedDate = date('d.m.Y', $timestamp);
		$day = date('d.', $timestamp);
		$month = $months[date('n', $timestamp)];
		$year = date('Y', $timestamp);
		$formattedDate = $day .' '. $month .' '. $year;
		if ($formattedDate) {
			return $formattedDate;
		}
	}
	return '';
}

/**
* Add a widget to the dashboard.
*
* This function is hooked into the 'wp_dashboard_setup' action below.
*/
function add_dashboard_widgets() {
	if( current_user_can('administrator')) {
		// wp_add_dashboard_widget(
		//             // contact_SOAP("check_abo_status"),         // Widget slug.
		//             'ntd_abo_info',
		//             'new time design Abo Info',         // Title.
		//             'display_abo_info' // Display function.
		// );
		add_meta_box(
			'ntd_abo_info',
			'new time design Service Abonnement',
			'display_abo_info',
			'dashboard',
			'normal',
			'high'
		);
	}
}
add_action( 'wp_dashboard_setup', 'add_dashboard_widgets' );

/**
* Create the function to output the contents of the Dashboard Widget.
*/
function display_abo_info() {

	$customerInfo = json_decode(contact_SOAP("get_abo_info"));

	?>

	<?php if($customerInfo->error == 1): ?>

		<div class="info-text">
			<h3>Authentifizierung Ihres Abonnements</h3>
			<p>Bitte geben Sie den Sicherheitsschlüssel, den Sie von uns erhalten haben, in das Eingabefeld unten ein.</p>
		</div>

		<form class="register-form flex flex-wrap" method='post'>
			<input type='text' name='ntd_authentication_key' autofocus autocomplete='off'>
			<button type='submit'>Bestätigen</button>
		</form>

		<div class="info-box">
			<div class="flex gap-20">
				<p class="flex-icon-text"><i class="info">Dieser Schritt ist einmalig und dient dem Schutz Ihrer Daten. Falls Sie über keinen solchen Schlüssel verfügen, kontaktieren Sie uns bitte, damit wir diesen erneut versenden können.</i></p>
				<span class="dashicons dashicons-admin-network flex-icon"></span>
			</div>
		</div>

	<?php elseif ($customerInfo->error < 3): ?>

		<div class="info-text">
			<p>Hier behalten Sie Ihr Service Abonnement im Überblick.</p>
		</div>

		<div class='info-box info-customer'>
			<ul>
				<li><span class="info-separator separator-large"><b>Domain / Kunde: </b></span><?php echo $customerInfo->customer ?></li>
				<?php if ( $customerInfo->customer_nr != '' ): ?>
					<li><span class="info-separator separator-large"><b>Kundennummer: </b></span><?php echo $customerInfo->customer_nr ?></li>
				<?php endif; ?>
				<li><span class="info-separator separator-large"><b>Servicepaket: </b></span><?php echo $customerInfo->service ?></li>
			</ul>
		</div>

		<?php if ($customerInfo->error == 0): ?>

			<div class="info-box info-bill">
				<h3 class="openable" onclick="ntd_open(event)">Laufende Rechnung <span class="ntd-arrow"></span></h3>

				<?php if (!empty($customerInfo->bill_outstanding) && !empty($customerInfo->bill_nr) && !empty($customerInfo->bill_date) && !empty($customerInfo->bill_amount) && !empty($customerInfo->bill_maturity)): ?>
					<ul>
						<li><span class="info-separator separator-large"><b>Rechnungs-Nr: </b></span><?php echo $customerInfo->bill_nr ?></li>
						<li><span class="info-separator separator-large"><b>Rechnungsdatum: </b></span><?php echo formatDate($customerInfo->bill_date) ?></li>
						<li><span class="info-separator separator-large"><b>Zahlbar bis: </b></span><?php echo formatDate($customerInfo->bill_maturity) ?></li>
						<li><span class="info-separator separator-large"><b>Rechnungsbetrag: </b></span>CHF <?php echo $customerInfo->bill_amount ?></li>
						<?php if ($customerInfo->bill_message != ''): ?>
							<li><span class="info-separator separator-large"><b>Bemerkungen: </b></span><?php echo $customerInfo->bill_message ?></li>
						<?php endif; ?>
					</ul>
				<?php else: ?>
					<p><i class="info">Keine offene Rechnung</i></p>
				<?php endif; ?>
			</div>


			<?php if (!empty($customerInfo->entries)): ?>
				<div class='info-box info-history'>
					<h3 class="openable" onclick='ntd_open(event)'>Letzte Arbeiten <span class='ntd-arrow'></span></h3>
						<ul>
							<?php  foreach ($customerInfo->entries as $key => $value): ?>
								<li>
									<span class="info-separator separator-large"><b><?php echo formatDate($value->date_of_entry); ?> </b></span><?php echo $value->entry ?>
								</li>
							<?php endforeach; ?>
						</ul>
				</div>
			<?php endif; ?>

		<?php elseif($customerInfo->error == 2): // -> kunde aber kein abo ?>
			<div class="info-text">
				<p><i class="error">Sie haben kein Service Abonnement.</i></p>
				<p>Möchten Sie von regelmässigen Updates der Sicherheitsmassnahmen und Backups Ihres Systems profitieren? Dann <a href="https://www.new-time.ch/webdesign/webdesign-pakete-und-preise/" target="_blank">informieren Sie sich hier</a> über unser Angebot und kontaktieren Sie uns.</p>
			</div>
		<?php endif; ?>

	<?php elseif($customerInfo->error == 3): // -> fehler ?>
		<div class="info-text">
			<p><i class="error">Ein Fehler ist aufgetreten - Bitte nehmen Sie Kontakt mit uns auf.</i></p>
		</div>

	<?php elseif($customerInfo->error > 3): // -> kein kunde ?>
		<div class="info-text">
			<p><i class="error">Sie haben kein Service Abonnement.</i></p>
			<p>Möchten Sie von regelmässigen Updates der Sicherheitsmassnahmen und Backups Ihres Systems profitieren? Dann <a href="https://www.new-time.ch/webdesign/webdesign-pakete-und-preise/" target="_blank">informieren Sie sich hier</a> über unser Angebot und kontaktieren Sie uns.</p>
		</div>
	<?php endif; ?>

	<div class="info-box info-branding">
		<div class="flex gap-20">

			<div class="branding-adress">
				<p class="adress-block">
					<b>new time design</b><br>
					Hauptstrasse 94a<br>
					9434 Au SG
				</p>
				<p class="adress-contactdata">
					<span class="info-separator">Telefon: </span><a href='tel:071 744 81 30'>071 744 81 30 </a><br>
					<span class="info-separator">E-Mail: </span><a href='mailto:info@new-time.ch'>info@new-time.ch</a><br>
					<span class="info-separator">Website: </span><a href="https://www.new-time.ch/" target="_blank">new-time.ch</a>
				</p>
			</div>

			<div class="branding-logo">
				<a href="https://new-time.ch" title="zur Website" target="_blank">
					<img id="ntd_logo" src="<?php echo plugin_dir_url( __FILE__ ) . 'includes/logo.svg' ?>" alt="Logo von new time design">
				</a>
			</div>

		</div>
	</div>
	<?php
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
