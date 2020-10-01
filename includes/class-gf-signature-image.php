<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Handles creating the signature URL and processing requests to output the image.
 *
 * @since 4.0
 *
 * Class GF_Signature_Image
 */
class GF_Signature_Image {

	/**
	 * The signature filename excluding extension.
	 *
	 * @var null|string
	 */
	private $filename;

	/**
	 * The ID of the form used to create the signature.
	 *
	 * @var null|int
	 */
	private $form_id;

	/**
	 * The ID of the field used to create the signature.
	 *
	 * @var null|int
	 */
	private $field_id;

	/**
	 * The hash used when validating if the signature should be output.
	 *
	 * @var null|string
	 */
	private $hash;

	/**
	 * Indicates if transparency is enabled for signature output.
	 *
	 * @var null|bool
	 */
	private $transparent;

	/**
	 * Indicates if the signature should download on output.
	 *
	 * @var null|bool
	 */
	private $download;

	/**
	 * The current instance of the Signature Add-On.
	 *
	 * @var null|GFSignature
	 */
	private $addon;

	/**
	 * GF_Signature_Image constructor.
	 *
	 * @param GFSignature $addon       The current instance of the Signature Add-On.
	 * @param string      $filename    The signature filename excluding extension.
	 * @param int         $form_id     The ID of the form used to create the signature.
	 * @param int         $field_id    The ID of the field used to create the signature.
	 * @param bool        $transparent Indicates if transparency is enabled for signature output.
	 * @param bool        $download    Indicates if the signature should download on output.
	 * @param string      $hash        The hash to be used when validating permission to view the signature.
	 */
	public function __construct( $addon, $filename, $form_id, $field_id, $transparent, $download, $hash = '' ) {
		$this->addon       = $addon;
		$this->filename    = $filename;
		$this->form_id     = $form_id;
		$this->field_id    = $field_id;
		$this->transparent = $transparent;
		$this->download    = $download;
		$this->hash        = $hash;
	}

	/**
	 * Returns the URL for the requested signature file.
	 *
	 * @since 4.0
	 *
	 * @return string
	 */
	public function get_url() {
		if ( empty( $this->filename ) ) {
			return '';
		}

		$url    = add_query_arg( $this->get_url_args(), site_url( 'index.php' ) );
		$filter = array( 'gform_signature_url', $this->form_id, $this->field_id );

		if ( function_exists( 'gf_has_filters' ) && gf_has_filters( $filter ) ) {
			$this->addon->log_debug( __METHOD__ . '(): Executing functions hooked to gform_signature_url.' );
		}

		/**
		 * Allows the signature URL to be overridden.
		 *
		 * @since 4.0
		 *
		 * @param string $url      The signature URL.
		 * @param string $filename The signature filename excluding extension.
		 * @param int    $form_id  The ID of the form used to create the signature.
		 * @param int    $field_id The ID of the field used to create the signature.
		 */
		return gf_apply_filters( $filter, $url, $this->filename, $this->form_id, $this->field_id );
	}

	/**
	 * Returns an array of query arguments to be added to the signature URL.
	 *
	 * @since 4.0
	 *
	 * @return array
	 */
	private function get_url_args() {
		$args = array(
			$this->addon->get_query_var() => urlencode( $this->filename ),
			'form-id'                     => $this->form_id,
			'field-id'                    => $this->field_id,
			'hash'                        => GFCommon::generate_download_hash( $this->form_id, $this->field_id, $this->filename ),
		);

		if ( $this->transparent ) {
			$args['t'] = 1;
		}

		if ( $this->download ) {
			$args['dl'] = 1;
		}

		return $args;
	}

	/**
	 * Outputs the signature image if the request includes the appropriate query data.
	 *
	 * @since 4.0
	 */
	public function maybe_output() {
		if ( empty( $this->filename ) ) {
			return;
		}

		$this->addon->log_debug( __METHOD__ . "(): Processing request for file: {$this->filename}" );

		if ( ! empty( $this->hash ) ) {
			if ( ! $this->field_exists() ) {
				$this->addon->log_debug( __METHOD__ . "(): Aborting ({$this->filename}); field does not exist." );
				$this->die_404();
			}

			$this->maybe_require_login();

			if ( ! $this->is_permission_granted() ) {
				$this->addon->log_debug( __METHOD__ . "(): Aborting ({$this->filename}); permission denied." );
				$this->die_401();
			}

			$this->addon->log_debug( __METHOD__ . "(): Permission granted ({$this->filename}). Proceeding." );
		}

		$image = $this->get_image();

		if ( ! $image ) {
			$this->addon->log_debug( __METHOD__ . "(): Aborting ({$this->filename}); file does not exist." );
			$this->die_404();
		}

		$this->output( $image );
		exit();
	}

	/**
	 * Determines if the signature field exists.
	 *
	 * If the field no longer exists or is not a signature field then the file no longer exists.
	 *
	 * @since 4.0
	 *
	 * @return bool
	 */
	public function field_exists() {
		$field = GFAPI::get_field( $this->form_id, $this->field_id );

		return $field instanceof GF_Field_Signature;
	}

	/**
	 * Redirects to the login page if login is required to access the signature.
	 *
	 * @since 4.0
	 */
	private function maybe_require_login() {
		if ( ! has_filter( 'gform_signature_url_require_login' ) ) {
			return;
		}

		$this->addon->log_debug( __METHOD__ . '(): Executing functions hooked to gform_signature_url_require_login.' );

		/**
		 * Allows login to be required to access the signature.
		 *
		 * @since 4.0
		 *
		 * @param bool $require_login Does the user need to be logged in to access the signature? Default false.
		 * @param int  $form_id       The ID of the form used to create the signature.
		 * @param int  $field_id      The ID of the field used to create the signature.
		 */
		$require_login = apply_filters( 'gform_signature_url_require_login', false, $this->form_id, $this->field_id );

		if ( $require_login && ! is_user_logged_in() ) {
			$this->addon->log_debug( __METHOD__ . '(): Redirecting to the login page.' );
			auth_redirect();
		}
	}

	/**
	 * Validates the hash to determine if the signature can be accessed.
	 *
	 * @since 4.0
	 *
	 * @return bool
	 */
	private function is_permission_granted() {
		$permission_granted = hash_equals( $this->hash, GFCommon::generate_download_hash( $this->form_id, $this->field_id, $this->filename ) );

		if ( has_filter( 'gform_signature_url_permission_granted' ) ) {
			$this->addon->log_debug( __METHOD__ . '(): Executing functions hooked to gform_signature_url_permission_granted.' );

			/**
			 * Allow custom logic to be used to determine if the signature can be accessed.
			 *
			 * @since 4.0
			 *
			 * @param bool $permission_granted Indicates if access to the signature has been granted. Default is the result of the hash validation.
			 * @param int  $form_id            The ID of the form used to create the signature.
			 * @param int  $field_id           The ID of the field used to create the signature.
			 */
			$permission_granted = apply_filters( 'gform_signature_url_permission_granted', $permission_granted, $this->form_id, $this->field_id );
		}

		return $permission_granted;
	}

	/**
	 * Returns the resource identifier of the signature image to be output.
	 *
	 * @since 4.0
	 *
	 * @return false|resource
	 */
	private function get_image() {
		$this->addon->log_debug( __METHOD__ . "(): Running for file: {$this->filename}" );
		$file_path = trailingslashit( GFSignature::get_signatures_folder() ) . $this->filename . '.png';

		if ( ! $this->is_valid_file( $file_path ) ) {
			return false;
		}

		// Preventing errors from being displayed.
		$prev_level = error_reporting( 0 );

		$image = imagecreatefrompng( $file_path );

		// Restoring error reporting level.
		error_reporting( $prev_level );

		if ( ! $image || $this->is_transparent_image() ) {
			return $image;
		}

		return $this->get_flattened_image( $image );
	}

	/**
	 * Determines if the file can be used to get the signature image.
	 *
	 * @since 4.0
	 *
	 * @param string $file_path The signature file path.
	 *
	 * @return bool
	 */
	private function is_valid_file( $file_path ) {
		// If mime_content_type function is defined, use it to validate that the local file is a PNG, otherwise assume it is valid.
		if ( stream_is_local( $file_path ) && function_exists( 'mime_content_type' ) && mime_content_type( $file_path ) !== 'image/png' ) {
			return false;
		}

		if ( ! is_readable( $file_path ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determines if the transparent image has been requested.
	 *
	 * @since 4.0
	 *
	 * @return bool
	 */
	public function is_transparent_image() {
		return (bool) $this->transparent;
	}

	/**
	 * Creates a flattened version of the signature image.
	 *
	 * @since 4.0
	 *
	 * @param resource $image The signature resource identifier.
	 *
	 * @return resource
	 */
	private function get_flattened_image( $image ) {
		$width        = imagesx( $image );
		$height       = imagesy( $image );
		$return_image = imagecreatetruecolor( $width, $height );

		if ( ! $return_image ) {
			return $image;
		}

		// If any of the remaining steps in flattening the image fail destroy the new image and return the original.
		if ( ! imagealphablending( $return_image, false ) ||
		     ! imagesavealpha( $return_image, true ) ||
		     ! imagecopyresampled( $return_image, $image, 0, 0, 0, 0, $width, $height, $width, $height )
		) {
			imagedestroy( $return_image );

			return $image;
		}

		imagedestroy( $image );

		return $return_image;
	}

	/**
	 * Outputs the signature image.
	 *
	 * @since 4.0
	 *
	 * @param resource $image The signature resource identifier.
	 */
	private function output( $image ) {
		$this->addon->log_debug( __METHOD__ . "(): Running for file: {$this->filename}" );

		if ( ob_get_length() > 0 ) {
			ob_clean();
		}

		$content_disposition = $this->download ? 'attachment' : 'inline';

		nocache_headers();
		header( 'X-Robots-Tag: noindex', true );
		header( 'Content-Type: image/png' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: ' . $content_disposition . '; filename="' . $this->filename . '.png"' );
		header( 'Content-Transfer-Encoding: binary' );
		imagepng( $image );
		imagedestroy( $image );
	}

	/**
	 * Ends the request with a 404 (Not Found) HTTP status code. Loads the 404 template if it exists.
	 *
	 * @since 4.0
	 */
	private function die_404() {
		global $wp_query;
		status_header( 404 );
		$wp_query->set_404();
		$template_path = get_404_template();
		if ( file_exists( $template_path ) ) {
			require_once( $template_path );
		}
		die();
	}

	/**
	 * Ends the request with a 401 (Unauthorized) HTTP status code.
	 *
	 * @since 4.0
	 */
	private function die_401() {
		status_header( 401 );
		die();
	}

}
