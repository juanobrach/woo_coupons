<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://https://github.com/juanobrach
 * @since      1.0.0
 *
 * @package    Woo_Coupons
 * @subpackage Woo_Coupons/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woo_Coupons
 * @subpackage Woo_Coupons/public
 * @author     obrach <juanobrach@gmail.com>
 */
class Woo_Coupons_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $max_uses;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->max_uses = 50;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-coupons-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-coupons-public.js', array( 'jquery' ), $this->version, false );

	}


	private function getEmailValue($array) {
		foreach ($array as $item) {
			if (isset($item['type']) && $item['type'] === 'email') {
				return $item['value'];
			}
		}
		return null;  // or return false, depending on how you want to handle when no email type is found.
	}


	private function create_email_body($coupon_code) {
        $logo_url = 'http://heromacandles.com/wp-content/uploads/2023/09/LOGO-HEROMA-PNG-1-e1694984073843.png'; // replace with your logo's URL
        $background_color = '#8e4b50'; // choose a color you prefer
		$font_color = '#fff'; // choose a color you prefer
		$store_url = 'http://heromacandles.com'; // Replace with your store's URL
		$email_body = "
			<html>
		<head>
			<title>Your Coupon Code</title>
			<style>
				@media only screen and (max-width: 600px) {
					table[class='main-table'] {
						width: 90% !important;
					}
					td[class='padding'] {
						padding-left: 20px !important;
						padding-right: 20px !important;
					}
				}
			</style>
		</head>
		<body style='margin: 0; padding: 50px 0; font-family: Arial, sans-serif; background-color: $background_color;'>
			<table align='center' border='0' cellpadding='0' cellspacing='0' width='600' class='main-table'>
				<tr>
					<td align='center'  style='padding: 20px 0;'>
						<img src='$logo_url' alt='Company Logo' width='300' style='display: block; max-width: 100%; height: auto;' />
					</td>
				</tr>
				<tr>
					<td bgcolor='#C0916F' class='padding' style='padding: 40px 30px 40px 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
						<table border='0' cellpadding='0' cellspacing='0' width='100%'>
							<tr>
								<td style='color:$font_color; font-size: 24px;'>
									<b>Tu Código de Cupón Exclusivo</b>
								</td>
							</tr>
							<tr>
								<td style='padding: 20px 0 30px 0; color: $font_color; font-size: 16px;'>
									Usa el siguiente código en tu próxima compra:
								</td>
							</tr>
							<tr>
								<td style='font-size: 24px; font-weight: bold; color: $font_color; border: 2px dashed $background_color; padding: 15px; text-align: center;'>
									$coupon_code
								</td>
							</tr>
							<tr>
								<td style='padding: 30px 0 0 0; font-size: 16px; text-align: center;color: $font_color'>
									<a href='$store_url' style='color: $font_color; text-decoration: none;'>Visita Nuestra Tienda</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		";
        return $email_body;
    }




	public function after_wpforms_submission( $fields, $entry, $form_data, $entry_id ) {

		$email = $this->getEmailValue($fields);
		$existing_coupon = $this->get_coupon_by_email($email);
		if($existing_coupon){
			// print_r($existing_coupon);die;
			$this->send_coupon_email($existing_coupon);
			return;
		}

		$new_coupon = $this->generate_coupon($email);
    }



	private function send_coupon_email($coupon ){
		// Your custom code after WPForms submission goes here.
		$to = 'juanobrach@gmail.com';
		$subject = 'Código de Cupón Exclusivo 😃';
		$body =  $this->create_email_body( $coupon->post_title );
		$headers = array('Content-Type: text/html; charset=UTF-8');
		wp_mail( $to, $subject, $body, $headers );
	}

	private function coupon_is_available($coupon){
		$coupon_id = $coupon['id'];
		$WC_coupon = new WC_Coupon($coupon_id);
		$usages = (int) $WC_coupon->get_usage_count();
		$usage_limit =  $WC_coupon->get_usage_limit() ?? 2;
		$emails = $WC_coupon->get_email_restrictions();

		if(  count($emails) >=  $usage_limit  || $usages >= $usage_limit ){
			return false;
		}

		return true;
	}	


	public function get_coupon_available(){
		$coupon_codes = [
			[
			'id'=> 'HEROMA15%OFF',
			'amount'=> 15,
			], 
			[
			'id'=> 'HEROMA10%OFF',
			'amount'=> 10,
			]		
		];

		foreach ($coupon_codes as $coupon) {
			if($this->coupon_is_available($coupon)){
				return $coupon;
			}
		}
		return false;
	}


	private function generate_coupon($form_email){
		// Check if coupon is valid
		$MAX_COUPON_USES = 50;
		$discount_type = 'percent'; // Type: fixed_cart, percent, fixed_product, percent_product
		$coupon = $this->get_coupon_available();
		$coupon_amount = $coupon['value'];
		$coupon_id = $coupon['id'];

		if(!$coupon_code){
			return false;
		}

		$WC_coupon = new WC_Coupon($coupon_id);
		$emails_registered = $WC_coupon->get_email_restrictions();
		if(!in_array($form_email, $emails_registered)){
			array_push($emails_registered, $form_email);
			$emails = [$emails_registered , $form_email];
			$WC_coupon->set_email_restrictions($emails_registered );
		}else{
			// User already has assigned a coupon
			return false;
		}

		$WC_coupon->set_discount_type($discount_type);
		$WC_coupon->set_amount($coupon_amount);
		$WC_coupon->set_individual_use(true); // set to true if the coupon cannot be used in conjunction with other coupons
		$WC_coupon->set_usage_limit($MAX_COUPON_USES); // number of times the coupon can be used
		$WC_coupon->set_usage_limit_per_user(1); // number of times a specific user can use the coupon
		$WC_coupon->set_limit_usage_to_x_items(3); // maximum number of individual items this coupon can apply to when using product discounts
		$WC_coupon->save();
		return $coupon;
	}


	private function get_coupon_by_email($email) {
		$args = array(
			'posts_per_page' => 1,
			'post_type' => 'shop_coupon',
			'post_status' => 'publish',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'customer_email',
					'value' => $email,
					'compare' => 'LIKE'
				)
			)
		);
		$coupons = get_posts($args);
		return !empty($coupons) ? $coupons[0] : false;
	}

	

	public function filter_confirmation_message(  $message, $form_data, $fields, $entry_id ) {
		$email = $this->getEmailValue($fields);
		$coupon = $this->get_coupon_by_email($email_field);
		$message = "<h1>Tu cupon es: " . $coupon->post_title . "</h1>";
		return $message;
	}

}
