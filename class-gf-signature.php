<?php

GFForms::include_addon_framework();

class GFSignature extends GFAddOn {

	protected $_version = GF_SIGNATURE_VERSION;
	protected $_min_gravityforms_version = '1.9.14';
	protected $_slug = 'gravityformssignature';
	protected $_path = 'gravityformssignature/signature.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms Signature Add-On';
	protected $_short_title = 'Signature';
	protected $_enable_rg_autoupgrade = true;

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFSignature
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFSignature();
		}

		return self::$_instance;
	}

	private function __clone() {
	} /* do nothing */

	/**
	 * Handles anything which requires early initialization.
	 */
	public function pre_init() {
		parent::pre_init();

		if ( $this->is_gravityforms_supported() && class_exists( 'GF_Field' ) ) {
			require_once( 'includes/class-gf-field-signature.php' );

			add_action( 'parse_request', array( $this, 'display_signature' ) );
		}
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();

		add_filter( 'gform_merge_tag_filter', array( $this, 'merge_tag_filter' ), 10, 5 );
	}

	/**
	 * Initialize the admin specific hooks.
	 */
	public function init_admin() {
		parent::init_admin();

		add_filter( 'gform_tooltips', array( $this, 'tooltips' ) );
		add_action( 'gform_field_appearance_settings', array( $this, 'field_settings' ), 10, 2 );
		add_action( 'gform_delete_lead', array( $this, 'delete_lead' ) );
		add_action( 'gform_delete_entries', array( $this, 'delete_entries' ) );
		add_filter( 'gform_admin_pre_render', array( $this, 'edit_lead_script' ) );
	}

	/**
	 * Initialize the AJAX hooks.
	 */
	public function init_ajax() {
		parent::init_ajax();

		add_action( 'wp_ajax_gf_delete_signature', array( $this, 'ajax_delete_signature' ) );
	}

	/**
	 * The Signature add-on does not support logging.
	 *
	 * @param array $plugins The plugins which support logging.
	 *
	 * @return array
	 */
	public function set_logging_supported( $plugins ) {

		return $plugins;

	}

	// # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'maskedinput',
				'src'     => GFCommon::get_base_url() . '/js/jquery.maskedinput.min.js',
				'version' => GFCommon::$version,
				'deps'    => array( 'jquery' ),
				'enqueue' => array(
					array( 'admin_page' => array( 'form_editor' ) ),
				)
			),
			array(
				'handle'    => 'super_signature_script',
				'src'       => $this->get_base_url() . '/includes/super_signature/ss.js',
				'version'   => $this->_version,
				'deps'      => array( 'jquery' ),
				'in_footer' => true,
				'enqueue'   => array(
					array( 'field_types' => array( 'signature' ) ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}


	// # FIELD SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Add the tooltips for the field.
	 *
	 * @param array $tooltips An associative array of tooltips where the key is the tooltip name and the value is the tooltip.
	 *
	 * @return array
	 */
	public function tooltips( $tooltips ) {
		$signature_tooltips = array(
			'signature_background_color' => '<h6>' . esc_html__( 'Background Color', 'gravityformssignature' ) . '</h6>' . esc_html__( 'Select the color to be used for the background of the signature area.', 'gravityformssignature' ),
			'signature_border_color'     => '<h6>' . esc_html__( 'Border Color', 'gravityformssignature' ) . '</h6>' . esc_html__( 'Select the color to be used for the border around the signature area.', 'gravityformssignature' ),
			'signature_pen_color'        => '<h6>' . esc_html__( 'Pen Color', 'gravityformssignature' ) . '</h6>' . esc_html__( 'Select the color of the pen to be used for the signature.', 'gravityformssignature' ),
			'signature_box_width'        => '<h6>' . esc_html__( 'Width', 'gravityformssignature' ) . '</h6>' . esc_html__( 'Enter the width for the signature area in pixels.', 'gravityformssignature' ),
			'signature_border_style'     => '<h6>' . esc_html__( 'Border Style', 'gravityformssignature' ) . '</h6>' . esc_html__( 'Select the border style to be used around the signature area.', 'gravityformssignature' ),
			'signature_pen_size'         => '<h6>' . esc_html__( 'Pen Size', 'gravityformssignature' ) . '</h6>' . esc_html__( 'Select the width of the pen to be used for the signature.', 'gravityformssignature' ),
			'signature_border_width'     => '<h6>' . esc_html__( 'Border Width', 'gravityformssignature' ) . '</h6>' . esc_html__( 'Select the border width to be used around the signature area.', 'gravityformssignature' ),
			'signature_message'          => '<h6>' . esc_html__( 'Message', 'gravityformssignature' ) . '</h6>' . esc_html__( "Write the message you would like to be sent. You can insert fields submitted by the user by selecting them from the 'Insert Variable' drop down.", 'gravityformssignature' )
		);

		return array_merge( $tooltips, $signature_tooltips );
	}

	/**
	 * Add the custom settings for the Signature field to the Appearance tab.
	 *
	 * @param int $position The position the settings should be located at.
	 * @param int $form_id The ID of the form currently being edited.
	 */
	public function field_settings( $position, $form_id ) {

		if ( $position == 0 ) {
			?>
			<li class="background_color_setting field_setting gform_setting_left_half">
				<label for="field_signature_background_color" class="section_label">
					<?php esc_html_e( 'Background Color', 'gravityformssignature' ); ?>
					<?php gform_tooltip( 'signature_background_color' ) ?>
				</label>
				<?php GFFormDetail::color_picker( 'field_signature_background_color', 'SetSignatureBackColor' ) ?>
			</li>
			<li class="border_color_setting field_setting gform_setting_right_half">
				<label for="field_signature_border_color" class="section_label">
					<?php esc_html_e( 'Border Color', 'gravityformssignature' ); ?>
					<?php gform_tooltip( 'signature_border_color' ) ?>
				</label>
				<?php GFFormDetail::color_picker( 'field_signature_border_color', 'SetSignatureBorderColor' ) ?>
			</li>
			<li class="border_width_setting field_setting gform_setting_left_half">
				<label for="field_signature_border_width" class="section_label">
					<?php esc_html_e( 'Border Width', 'gravityformssignature' ); ?>
					<?php gform_tooltip( 'signature_border_width' ) ?>
				</label>
				<select id="field_signature_border_width" onchange="SetSignatureBorderWidth(jQuery(this).val());">
					<option value="0"><?php esc_html_e( 'None', 'gravityformssignature' ) ?></option>
					<option value="1"><?php esc_html_e( 'Small', 'gravityformssignature' ) ?></option>
					<option value="2"><?php esc_html_e( 'Medium', 'gravityformssignature' ) ?></option>
					<option value="3"><?php esc_html_e( 'Large', 'gravityformssignature' ) ?></option>
				</select>
			</li>
			<li class="border_style_setting field_setting gform_setting_right_half">
				<label for="field_signature_border_style" class="section_label">
					<?php esc_html_e( 'Border Style', 'gravityformssignature' ); ?>
					<?php gform_tooltip( 'signature_border_style' ) ?>
				</label>
				<select id="field_signature_border_style" onchange="SetSignatureBorderStyle(jQuery(this).val());">
					<option value="dotted"><?php esc_html_e( 'Dotted', 'gravityformssignature' ) ?></option>
					<option value="dashed"><?php esc_html_e( 'Dashed', 'gravityformssignature' ) ?></option>
					<option value="groove"><?php esc_html_e( 'Groove', 'gravityformssignature' ) ?></option>
					<option value="ridge"><?php esc_html_e( 'Ridge', 'gravityformssignature' ) ?></option>
					<option value="inset"><?php esc_html_e( 'Inset', 'gravityformssignature' ) ?></option>
					<option value="outset"><?php esc_html_e( 'Outset', 'gravityformssignature' ) ?></option>
					<option value="double"><?php esc_html_e( 'Double', 'gravityformssignature' ) ?></option>
					<option value="solid"><?php esc_html_e( 'Solid', 'gravityformssignature' ) ?></option>
				</select>
			</li>

			<li class="pen_color_setting field_setting gform_setting_left_half">
				<label for="field_signature_pen_color" class="section_label">
					<?php esc_html_e( 'Pen Color', 'gravityformssignature' ); ?>
					<?php gform_tooltip( 'signature_pen_color' ) ?>
				</label>
				<?php GFFormDetail::color_picker( 'field_signature_pen_color', 'SetSignaturePenColor' ) ?>
			</li>
			<li class="pen_size_setting field_setting gform_setting_right_half">
				<label for="field_signature_pen_size" class="section_label">
					<?php esc_html_e( 'Pen Size', 'gravityformssignature' ); ?>
					<?php gform_tooltip( 'signature_pen_size' ) ?>
				</label>
				<select id="field_signature_pen_size" onchange="SetSignaturePenSize(jQuery(this).val());">
					<option value="1"><?php esc_html_e( 'Small', 'gravityformssignature' ) ?></option>
					<option value="2"><?php esc_html_e( 'Medium', 'gravityformssignature' ) ?></option>
					<option value="3"><?php esc_html_e( 'Large', 'gravityformssignature' ) ?></option>
				</select>
			</li>
			<li class="box_width_setting field_setting">
				<label for="field_signature_box_width" class="section_label">
					<?php esc_html_e( 'Field Width', 'gravityformssignature' ); ?>
					<?php gform_tooltip( 'signature_box_width' ) ?>
				</label>
				<input id="field_signature_box_width" type="text" style="width:40px"
				       onkeyup="SetSignatureBoxWidth(jQuery(this).val());"
				       onchange="SetSignatureBoxWidth(jQuery(this).val());"/> px
			</li>

		<?php
		}
	}


	// # ENTRY DETAIL PAGE ----------------------------------------------------------------------------------------------

	/**
	 * Used with the gform_admin_pre_render hook to include the functionality for the delete signature link.
	 *
	 * @param array $form The current form object.
	 *
	 * @return array
	 */
	public function edit_lead_script( $form ) {
		if ( GFCommon::is_entry_detail_edit() ) {
			?>

			<script type="text/javascript">
				function deleteSignature(leadId, fieldId) {

					if (!confirm(<?php echo json_encode( __( "Would you like to delete this file? 'Cancel' to stop. 'OK' to delete", 'gravityformssignature' ) ); ?>))
						return;

					jQuery.post(ajaxurl, {
						lead_id: leadId,
						field_id: fieldId,
						action: 'gf_delete_signature',
						gf_delete_signature: '<?php echo wp_create_nonce( 'gf_delete_signature' ) ?>'
					}, function (response) {
						var formId = <?php echo absint( $form['id'] ) ?>;
						//if (!response)
						//jQuery('#input_' + fieldId).val('');
						jQuery('#input_' + formId + '_' + fieldId + '_signature_filename').val('');
						jQuery('#input_' + formId + '_' + fieldId + '_signature_image').hide();
						jQuery('#input_' + formId + '_' + fieldId + '_Container').show();
						jQuery('#input_' + formId + '_' + fieldId + '_resetbutton').show();
					});
				}
			</script>

			<?php
		}

		return $form;
	}

	// # MERGE TAGS -----------------------------------------------------------------------------------------------------

	/**
	 * Enable use of the gform_signature_show_in_all_fields hook to prevent the signature image being included in the all_fields output.
	 *
	 * @param string $value The value of the field currently being processed.
	 * @param string $merge_tag The merge tag (i.e. all_field) or the field/input ID when processing a merge tag for an individual field.
	 * @param string $options The merge tag modifiers. e.g. "value,nohidden" would be the modifiers for {all_fields:value,nohidden}.
	 * @param GF_Field $field The field currently being processed.
	 * @param mixed $raw_field_value The fields raw value before it was processed by $field->get_value_entry_detail().
	 *
	 * @return string
	 */
	public function merge_tag_filter( $value, $merge_tag, $options, $field, $raw_field_value ) {

		if ( $merge_tag == 'all_fields' && $field->type == 'signature' ) {

			$show_in_all_fields = apply_filters( 'gform_signature_show_in_all_fields', true, $field, $options, $value );
			if ( ! $show_in_all_fields ) {
				return $raw_field_value;
			}

		}

		return $value;
	}

	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * Returns the URL for the specified signature.
	 *
	 * @param string $filename The filename for this signature.
	 *
	 * @return string
	 */
	public function get_signature_url( $filename ) {
		$path_info = pathinfo( $filename );
		$filename  = $path_info['filename'];

		return site_url() . "?page=gf_signature&signature={$filename}";
	}

	/**
	 * Display the signature on it's own page.
	 *
	 * @param WP $wp The current WordPress instance.
	 */
	public static function display_signature( $wp ) {
		$is_signature = rgget( 'page' ) == 'gf_signature';
		if ( ! $is_signature ) {
			return;
		}

		$imagename = rgget( 'signature' ) . '.png';

		$folder = RGFormsModel::get_upload_root() . 'signatures/';

		if ( ! is_file( $folder . $imagename ) ) {
			exit();
		}

		$signature_image = imagecreatefrompng( $folder . $imagename );
		$return_image    = imagecreatetruecolor( imagesx( $signature_image ), imagesy( $signature_image ) );
		
		imagealphablending( $return_image, false );
		imagesavealpha( $return_image, true );
		imagecopyresampled( $return_image, $signature_image, 0, 0, 0, 0, imagesx( $signature_image ), imagesy( $signature_image ), imagesx( $signature_image ), imagesy( $signature_image ) );

		if ( ob_get_length() > 0 ) {
			ob_clean();
		}

		header( 'Content-type: image/png' );
		imagepng( $return_image );
		imagedestroy( $return_image );
		imagedestroy( $signature_image );

		exit();
	}


	/**
	 * Maybe save the signature.
	 *
	 * @param string $input_name The input name to use when accessing the $_POST.
	 * @param string $name_prefix The text to use as the filename prefix.
	 *
	 * @return string|void
	 */
	public function save_signature( $input_name, $name_prefix = '' ) {
		require_once( 'includes/super_signature/license.php' );

		$signature_data = rgpost( $input_name );

		$image = GetSignatureImage( $signature_data );
		if ( ! $image ) {
			return '';
		}

		$folder = RGFormsModel::get_upload_root() . '/signatures/';

		//abort if folder can't be created.
		if ( ! wp_mkdir_p( $folder ) ) {
			return;
		}

		$filename = $name_prefix . uniqid( '', true ) . '.png';
		$path     = $folder . $filename;
		imagepng( $image, $path, 4 );
		imagedestroy( $image );

		return $filename;
	}

	/**
	 * Used by the gform_delete_lead hook to delete any signatures for the entry currently being deleted.
	 *
	 * @param integer $lead_id The ID of the current entry.
	 */
	public function delete_lead( $lead_id ) {
		$lead = RGFormsModel::get_lead( $lead_id );
		$form = RGFormsModel::get_form_meta( $lead['form_id'] );

		if ( ! is_array( $form['fields'] ) ) {
			return;
		}

		foreach ( $form['fields'] as $field ) {
			if ( $field->type == 'signature' ) {
				$this->delete_signature( $lead, $field->id );
			}
		}

	}

	/**
	 * Used by the gform_delete_entries hook to delete any signatures for the entries currently being deleted.
	 * 
	 * @param int $form_id The ID of the form for which the entries are being deleted.
	 */
	public function delete_entries( $form_id ) {

		$form             = RGFormsModel::get_form_meta( $form_id );
		$signature_fields = GFAPI::get_fields_by_type( $form, 'signature' );

		if ( ! empty( $signature_fields ) ) {
			global $wpdb;

			$lead_details_table_name = GFFormsModel::get_lead_details_table_name();

			foreach ( $signature_fields as $field ) {

				$input_id_min = (float) $field->id - 0.0001;
				$input_id_max = (float) $field->id + 0.0001;

				$filenames = $wpdb->get_col( $wpdb->prepare( "SELECT value FROM {$lead_details_table_name} WHERE form_id=%d AND field_number BETWEEN %s AND %s", $form_id, $input_id_min, $input_id_max ) );
				
				if ( is_array( $filenames ) ) {
					foreach ( $filenames as $filename ) {
						$this->delete_signature_file( $filename );
					}
				}

			}
		}
	}

	/**
	 * Handler for the gf_delete_signature AJAX request.
	 */
	public function ajax_delete_signature() {

		check_ajax_referer( 'gf_delete_signature', 'gf_delete_signature' );

		$lead_id  = intval( $_POST['lead_id'] );
		$field_id = intval( $_POST['field_id'] );

		if ( ! $this->delete_signature( $lead_id, $field_id ) ) {
			esc_html_e( 'There was an issue deleting this signature.', 'gravityformssignature' );
		}

		die();
	}

	/**
	 * Initiates deletion of the signature file and updates the entry to remove the filename.
	 *
	 * @param integer $lead_id The ID of the current entry.
	 * @param integer $field_id The ID of the current field.
	 *
	 * @return bool
	 */
	public function delete_signature( $lead_id, $field_id ) {
		global $wpdb;

		$lead = RGFormsModel::get_lead( $lead_id );

		$this->delete_signature_file( rgar( $lead, $field_id ) );

		return GFAPI::update_entry_field( $lead_id, $field_id, '' );
	}

	/**
	 * Deletes the signature file from the uploads directory.
	 *
	 * @param string $filename The signature filename.
	 */
	public function delete_signature_file( $filename ) {

		$folder    = RGFormsModel::get_upload_root() . '/signatures/';
		$file_path = $folder . $filename;

		if ( file_exists( $file_path ) ) {
			unlink( $file_path );
		}

	}
}
