<?php

namespace WordProofTimestamp\includes\Controller;

use WordProofTimestamp\includes\OptionsHelper;

class ECommerceController {

	public function __construct() {

		if ( ! $this->isWooCommerceActivated() ) {
			return;
		}

		add_action( 'wordproof_after_saving_timestamp_meta_data', [ $this, 'onTimestamp' ] );
		add_filter( 'woocommerce_email_attachments', [ $this, 'attachFiles' ], 10, 3 );
		add_action( 'woocommerce_after_order_notes', [ $this, 'addCustomerForProductTimestamps' ] );
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'saveReceiveTimestampPreference' ] );
	}

	private function isWooCommerceActivated() {
		return class_exists( 'woocommerce' );
	}

	/**
	 * @param \WC_Checkout $checkout
	 */
	public function addCustomerForProductTimestamps( \WC_Checkout $checkout ) {
		$option = OptionsHelper::getSendTimestampsWithOrder();

		if ( in_array( $option, [ 'never', 'always' ] ) ) {
			return;
		}

		$checked = ( $option === 'ask_user_to_disable' );

		$response = '<div id="wordproof-ecommerce-ask-user-title"><h3>' . __( 'Do you want proof of this order?', 'wordproof-timestamp' ) . '</h3>';

		$response .= woocommerce_form_field( 'wordproof_receive_timestamps', array(
			'type'     => 'checkbox',
			'class'    => array( 'input-checkbox' ),
			'label'    => __( 'Send me indisputable proof (via email) of the current Terms & Conditions and the products in this order.',
				'wordproof-timestamp' ),
			'required' => false,
			'return'   => true
		), $checked );

		$response .= "<input type='hidden' name='wordproof_receive_timestamps_nonce' value='" . wp_create_nonce( 'wordproof_receive_timestamps_nonce' ) . "'>";

		$response .= '</div>';

		echo esc_html( $response );
	}

	/**
	 * Update the order meta with field value
	 *
	 * @param $orderId
	 */

	function saveReceiveTimestampPreference( $orderId ) {
		if ( ! isset( $_REQUEST['wordproof_receive_timestamps_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wordproof_receive_timestamps_nonce'] ) ), 'wordproof_receive_timestamps_nonce' ) ) {
			return;
		}

		if ( empty( $_POST ) ) {
			return;
		}

		if ( isset( $_POST['wordproof_receive_timestamps'] ) ) {
			update_post_meta( $orderId, 'wordproof_receive_timestamps',
				sanitize_text_field( wp_unslash( $_POST['wordproof_receive_timestamps'] ) ) );
		}
	}

	public function onTimestamp( $postId ) {
		$post        = get_post( $postId );
		$termsPostId = wc_get_page_id( 'terms' );

		if ( $termsPostId && $termsPostId === $termsPostId ) {
			self::createTxtFile( $post );
		}

		if ( $post->post_type === 'product' ) {
			self::createTxtFile( $post );
		}
	}

	public function attachFiles( $attachments, $email_id, $order ) {
		if ( ! is_a( $order, 'WC_Order' ) || ! isset( $email_id ) ) {
			return $attachments;
		}

		if ( ! $order->get_meta( 'wordproof_receive_timestamps' ) && OptionsHelper::getSendTimestampsWithOrder() !== 'always' ) {
			return $attachments;
		}

		if ( ! in_array( $email_id,
			[ 'customer_completed_order', 'customer_processing_order', 'customer_on_hold_order' ] ) ) {
			return $attachments;
		}

		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			$file    = $this->getFilePath( $product->get_title(), $product->get_id() );

			if ( file_exists( $file ) ) {
				$attachments[] = $file;
			}
		}

		$termsPostId = wc_get_page_id( 'terms' );
		if ( $termsPostId ) {
			$post = get_post( $termsPostId );
			$file = $this->getFilePath( $post->post_title, $post->ID );

			if ( file_exists( $file ) ) {
				$attachments[] = $file;
			}
		}

		return $attachments;
	}

	public static function createTxtFile( $post ) {
		$data = HashController::getHashInput( $post );
		$data .= PHP_EOL . PHP_EOL . 'Blockchain Transaction: ';
		$data .= HashController::getBlockchainLink( $post->ID );

		$note = OptionsHelper::getTimestampOrderText();

		if ( $note ) {
			$data .= PHP_EOL . PHP_EOL . 'Note: ' . $note;
		}

		file_put_contents( self::getFilePath( $post->post_title, $post->ID ), $data );
	}

	private static function getFilePath( $title, $id ) {
		$uploadDir    = wp_upload_dir( null, false );
		$wordproofDir = $uploadDir['basedir'] . '/' . 'wordproof';

		if ( ! file_exists( $wordproofDir ) ) {
			wp_mkdir_p( $wordproofDir );
		}

		$fileName = self::getFileName( $title, $id, 'txt' );

		return $wordproofDir . '/' . $fileName;
	}

	private static function getFileName( $title, $id, $extension ) {
		return preg_replace( '/[^a-zA-Z0-9_-]+/', '-', strtolower( $title ) ) . '-' . $id . '.' . $extension;
	}
}
