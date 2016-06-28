<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GF_Field_Signature extends GF_Field {

	public $type = 'signature';

	// # FORM EDITOR & FIELD MARKUP -------------------------------------------------------------------------------------

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Signature', 'gravityformssignature' );
	}

	/**
	 * Assign the Signature button to the Advanced Fields group.
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title()
		);
	}

	/**
	 * Return the settings which should be available on the field in the form editor.
	 *
	 * @return array
	 */
	function get_form_editor_field_settings() {
		return array(
			'pen_size_setting',
			'border_width_setting',
			'border_style_setting',
			'box_width_setting',
			'pen_color_setting',
			'border_color_setting',
			'background_color_setting',
			'conditional_logic_field_setting',
			'error_message_setting',
			'label_setting',
			'admin_label_setting',
			'rules_setting',
			'visibility_setting',
			'description_setting',
			'css_class_setting'
		);
	}

	/**
	 * Returns the scripts to be included for this field type in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_inline_script_on_page_render() {

		// set the default field label
		$script = sprintf( "function SetDefaultValues_signature(field) {field.label = '%s';}", $this->get_form_editor_field_title() ) . PHP_EOL;

		// initialize the fields custom settings
		$script .= "jQuery(document).bind('gform_load_field_settings', function (event, field, form) {" .

		           "var backColor = field.backgroundColor == undefined ? '' : field.backgroundColor;" .
		           "jQuery('#field_signature_background_color').val(backColor);" .
		           "SetColorPickerColor('field_signature_background_color', backColor);" .

		           "var borderColor = field.borderColor == undefined ? '' : field.borderColor;" .
		           "jQuery('#field_signature_border_color').val(borderColor);" .
		           "SetColorPickerColor('field_signature_border_color', borderColor);" .

		           "var penColor = field.penColor == undefined ? '' : field.penColor;" .
		           "jQuery('#field_signature_pen_color').val(penColor);" .
		           "SetColorPickerColor('field_signature_pen_color', penColor);" .

		           "var boxWidth = field.boxWidth == undefined || field.boxWidth.trim().length == 0 ? '300' : field.boxWidth;" .
		           "jQuery('#field_signature_box_width').val(boxWidth);" .

		           "var borderStyle = field.borderStyle == undefined ? '' : field.borderStyle.toLowerCase();" .
		           "jQuery('#field_signature_border_style').val(borderStyle);" .

		           "var borderWidth = field.borderWidth == undefined ? '' : field.borderWidth;" .
		           "jQuery('#field_signature_border_width').val(borderWidth);" .

		           "var penSize = field.penSize == undefined ? '' : field.penSize;" .
		           "jQuery('#field_signature_pen_size').val(penSize);" .

		           "});" . PHP_EOL;

		// initialize the input mask for the width setting
		$script .= "jQuery(document).ready(function () {jQuery('#field_signature_box_width').mask('?9999', {placeholder: ' '});});" . PHP_EOL;

		// saving the backgroundColor property and updating the UI to match
		$script .= "function SetSignatureBackColor(color) {SetFieldProperty('backgroundColor', color);jQuery('.field_selected .gf_signature_container').css('background-color', color);}" . PHP_EOL;

		// saving the borderColor property and updating the UI to match
		$script .= "function SetSignatureBorderColor(color) {SetFieldProperty('borderColor', color);jQuery('.field_selected .gf_signature_container').css('border-color', color);}" . PHP_EOL;

		// saving the penColor property
		$script .= "function SetSignaturePenColor(color) {SetFieldProperty('penColor', color);}" . PHP_EOL;

		// saving the boxWidth property
		$script .= "function SetSignatureBoxWidth(size) {SetFieldProperty('boxWidth', size);}" . PHP_EOL;

		// saving the borderStyle property and updating the UI to match
		$script .= "function SetSignatureBorderStyle(style) {SetFieldProperty('borderStyle', style);jQuery('.field_selected .gf_signature_container').css('border-style', style);}" . PHP_EOL;

		// saving the borderWidth property and updating the UI to match
		$script .= "function SetSignatureBorderWidth(size) {SetFieldProperty('borderWidth', size);jQuery('.field_selected .gf_signature_container').css('border-width', size + 'px');}" . PHP_EOL;

		// saving the penSize property
		$script .= "function SetSignaturePenSize(size) {SetFieldProperty('penSize', size);}";

		return $script;
	}

	/**
	 * Returns the field inner markup.
	 *
	 * @param array $form The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin        = $is_entry_detail || $is_form_editor;

		$form_id  = absint( $form['id'] );
		$id       = $this->id;
		$field_id = $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$init_options = $this->get_supersignature_init_options( $field_id, $form );

		$bgcolor     = rgar( $init_options, 'BackColor', '#FFFFFF' );
		$bordercolor = rgar( $init_options, 'BorderColor', '#DDDDDD' );
		$boxheight   = rgar( $init_options, 'SignHeight', '180' );
		$boxwidth    = rgar( $init_options, 'SignWidth', '300' );
		$borderstyle = rgar( $init_options, 'BorderStyle', 'Dashed' );
		$borderwidth = rgar( $init_options, 'BorderWidth', '2px' );

		if ( $is_form_editor ) {
			//box width is hardcoded in the admin
			$input = '<style>' .
			         '.top_label .gf_signature_container {width: 460px;} ' .
			         '.left_label .gf_signature_container, .right_label .gf_signature_container {width: 300px;} ' .
			         '</style>' .
			         "<div style='display:-moz-inline-stack; display: inline-block; zoom: 1; *display: inline;'><div class='gf_signature_container' style='height:180px; border: {$borderwidth} {$borderstyle} {$bordercolor}; background-color:{$bgcolor};'></div></div>";

		} else {

			$input = '';

			$signature_filename = ! empty( $value ) ? $value : rgpost( "{$field_id}_signature_filename" );

			if ( ! empty( $signature_filename ) ) {

				$signature_url = gf_signature()->get_signature_url( $signature_filename );

				$input .= sprintf( "<div id='%s_signature_image'><img src='%s' width='%spx'/><div>", $field_id, $signature_url, $boxwidth );

				if ( $is_entry_detail && $value ) {

					// include the download link
					$input .= sprintf( "<a href='%s' target='_blank' title='%s'><img src='%s/images/download.png' alt='%s'/></a>", $signature_url, esc_attr__( 'Download file', 'gravityformssignature' ), GFCommon::get_base_url(), esc_attr__( 'Download file', 'gravityformssignature' ) );

					// include the delete link
					$input .= sprintf( "<a href='javascript:void(0);' title='%s' onclick='deleteSignature(%d, %d);'><img src='%s/images/delete.png' alt='%s' style='margin-left:8px;'/></a>", esc_attr__( 'Delete file', 'gravityformssignature' ), rgar( $entry, 'id' ), $id, GFCommon::get_base_url(), esc_attr__( 'Delete file', 'gravityformssignature' ) );

				} else {

					$input .= "<a href='#' onclick='jQuery(\"#{$field_id}_signature_image\").hide(); jQuery(\"#{$field_id}_Container\").show(); jQuery(\"#{$field_id}_resetbutton\").show(); return false;'>" . __( 'sign again', 'gravityformssignature' ) . '</a>';

				}

				$input .= sprintf( "</div></div><input type='hidden' value='%s' name='%s_signature_filename' id='%s_signature_filename'/>", $signature_filename, $field_id, $field_id  );

				$input .= "<style type='text/css'>#{$field_id}_resetbutton {display:none}</style>";

			}

			$display = ! empty( $signature_filename ) ? 'display:none;' : '';

			$container_style = rgar( $form, 'labelPlacement' ) == 'top_label' ? '' : "style='display:-moz-inline-stack; display: inline-block; zoom: 1; *display: inline;'";

			$input .= "<div {$container_style}><div id='{$field_id}_Container' style='height:{$boxheight}px; width:{$boxwidth}px; {$display}' >" .
			          "<input type='hidden' class='gform_hidden' name='{$field_id}_valid' id='{$field_id}_valid' />";

			$use_canvas = true;

			global $is_IE;
			if ( $is_IE ) {
				$ua = $_SERVER['HTTP_USER_AGENT'];

				if ( ! empty( $ua ) && preg_match( '/\bMSIE (\d)/', $ua, $matches ) && (int) $matches[1] <= 8 ) {
					$input .= "<div id='{$field_id}' style='width:{$boxwidth}px; height:{$boxheight}px; border:{$borderstyle} {$borderwidth} {$bordercolor}; background-color:{$bgcolor};'></div>";
					$use_canvas = false;
				}
			}

			if ( $use_canvas ) {
				$input .= "<canvas id='{$field_id}' width='{$boxwidth}' height='{$boxheight}'></canvas>";
			}

			$input .= '</div></div>';

			if ( $this->is_entry_detail_edit() ) {
				$input .= "<script type='text/javascript'>jQuery(document).ready(function() {" . $this->get_form_inline_script_on_page_render( $form ) . '});</script>';
			}

		}

		return $input;
	}

	/**
	 * Return the SuperSignature initialization scripts.
	 *
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_form_inline_script_on_page_render( $form ) {

		$unique_id    = $form['id'] == 0 ? "input_{$this->id}" : 'input_' . $form['id'] . "_{$this->id}";
		$init_options = $this->get_supersignature_init_options( $unique_id, $form );

		$script = "window.obj{$unique_id} = new SuperSignature( " . json_encode( $init_options ) . " );" .
		           "obj{$unique_id}.Init();" .
		           "jQuery('#gform_{$form['id']}').submit(function(){" .
		           "    jQuery('#{$unique_id}_valid').val(obj{$unique_id}.IsValid() || jQuery('#{$unique_id}_signature_filename').val() ? '1' : '');" .
		           "});";

		$script = "if( typeof SuperSignature != 'undefined' && jQuery('#{$unique_id}').length ) { $script }";

		return $script;
	}


	// # SUBMISSION -----------------------------------------------------------------------------------------------------

	/**
	 * Used to determine the required validation result.
	 *
	 * @param int $form_id The ID of the form currently being processed.
	 *
	 * @return bool
	 */
	public function is_value_submission_empty( $form_id ) {
		$input_prefix = "input_{$form_id}_{$this->id}";
		$is_invalid   = rgempty( "{$input_prefix}_signature_filename" ) && rgempty( "{$input_prefix}_valid" );

		if ( $is_invalid && empty( $this->errorMessage ) ) {
			$this->errorMessage = __( 'Please enter your signature.', 'gravityformssignature' );
		}

		return $is_invalid;
	}

	/**
	 * Save the signature on submission; includes form validation or when an incomplete submission is being saved.
	 *
	 * @param array $field_values The dynamic population parameter names with their corresponding values to be populated.
	 * @param bool|true $get_from_post_global_var Whether to get the value from the $_POST array as opposed to $field_values.
	 *
	 * @return string
	 */
	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		return $this->maybe_save_signature();
	}


	// # ENTRY RELATED --------------------------------------------------------------------------------------------------

	/**
	 * Get the signature filename for saving to the Entry Object.
	 *
	 * @param array|string $value The value to be saved.
	 * @param array $form The Form Object currently being processed.
	 * @param string $input_name The input name used when accessing the $_POST.
	 * @param int $lead_id The ID of the Entry currently being processed.
	 * @param array $lead The Entry Object currently being processed.
	 *
	 * @return array|string
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {

		return $this->maybe_save_signature();
	}

	/**
	 * Format the entry value for when the field/input merge tag is processed. Not called for the {all_fields} merge tag.
	 *
	 * @param string|array $value The field value. Depending on the location the merge tag is being used the following functions may have already been applied to the value: esc_html, nl2br, and urlencode.
	 * @param string $input_id The field or input ID from the merge tag currently being processed.
	 * @param array $entry The Entry Object currently being processed.
	 * @param array $form The Form Object currently being processed.
	 * @param string $modifier The merge tag modifier. e.g. value
	 * @param string|array $raw_value The raw field value from before any formatting was applied to $value.
	 * @param bool $url_encode Indicates if the urlencode function may have been applied to the $value.
	 * @param bool $esc_html Indicates if the esc_html function may have been applied to the $value.
	 * @param string $format The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param bool $nl2br Indicates if the nl2br function may have been applied to the $value.
	 *
	 * @return string
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {
		if ( ! empty( $value ) ) {
			$signature_url = gf_signature()->get_signature_url( $value );

			return $url_encode ? urlencode( $signature_url ) : $signature_url;
		}

		return $value;
	}

	/**
	 * Format the entry value for display on the entries list page.
	 *
	 * @param string|array $value The field value.
	 * @param array $entry The Entry Object currently being processed.
	 * @param string $field_id The field or input ID currently being processed.
	 * @param array $columns The properties for the columns being displayed on the entry list page.
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		if ( ! empty( $value ) ) {
			$signature_url = gf_signature()->get_signature_url( $value );
			$thumb         = GFCommon::get_base_url() . '/images/doctypes/icon_image.gif';
			$value         = sprintf( "<a href='%s' target='_blank' title='%s'><img src='%s'/></a>", $signature_url, esc_attr__( 'Click to view', 'gravityformssignature' ), $thumb );
		}

		return $value;
	}

	/**
	 * Format the entry value for display on the entry detail page and for the {all_fields} merge tag.
	 *
	 * @param string|array $value The field value.
	 * @param string $currency The entry currency code.
	 * @param bool|false $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string $format The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string $media The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( ! empty( $value ) ) {
			$signature_url = gf_signature()->get_signature_url( $value );

			if ( $format == 'html' ) {
				$value = sprintf( "<a href='%s' target='_blank' title='%s'><img src='%s' width='100' /></a>", $signature_url, esc_attr__( 'Click to view', 'gravityformssignature' ), $signature_url );
			} else {
				$value = $signature_url;
			}

		}

		return $value;
	}

	/**
	 * Format the entry value before it is used in entry exports and by framework add-ons using GFAddOn::get_field_value().
	 *
	 * @param array $entry The entry currently being processed.
	 * @param string $input_id The field or input ID.
	 * @param bool|false $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param bool|false $is_csv Is the value going to be used in the .csv entries export?
	 *
	 * @return string
	 */
	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		if ( empty( $input_id ) ) {
			$input_id = $this->id;
		}

		$value = rgar( $entry, $input_id );

		return ! empty( $value ) ? gf_signature()->get_signature_url( $value ) : '';
	}


	// # HELPERS --------------------------------------------------------------------------------------------------------

	/**
	 * Save the signature if it hasn't already been saved. Delete the old signature if they used the sign again link.
	 *
	 * @return string The filename.
	 */
	public function maybe_save_signature() {
		$form_id = $this->formId;
		$id      = $this->id;

		$input_name   = "input_{$id}";
		$input_prefix = "input_{$form_id}_{$id}";
		$input_data   = "{$input_prefix}_data";

		$signature_data = rgpost( $input_data );
		$filename       = ! rgempty( $input_name ) ? rgpost( $input_name ) : rgpost( "{$input_prefix}_signature_filename" );

		if ( ! empty( $filename ) && ! empty( $signature_data ) ) {
			gf_signature()->delete_signature_file( $filename );
			$filename = false;
		}

		if ( empty( $filename ) && ! empty( $signature_data ) ) {
			$filename = gf_signature()->save_signature( $input_data );
			$_POST["{$input_prefix}_signature_filename"] = $filename;
			unset( $_POST[ $input_data ] );
		}

		return $filename;
	}

	/**
	 * Retrieve the options to be used when initializing SuperSignature for this field.
	 *
	 * @param string $field_id The field canvas/div id attribute.
	 * @param array $form The current form object.
	 *
	 * @return array
	 */
	public function get_supersignature_init_options( $field_id, $form ) {
		$init_options = array(
			'forceMouseEvent'=> true,
			'IeModalFix'     => true,
			'SignObject'     => $field_id,
			'BackColor'      => empty( $this->backgroundColor ) ? '#FFFFFF' : $this->backgroundColor,
			'PenSize'        => rgblank( $this->penSize ) ? '2' : $this->penSize,
			'PenColor'       => empty( $this->penColor ) ? '#000000' : $this->penColor,
			'SignWidth'      => rgblank( $this->boxWidth ) ? '300' : $this->boxWidth,
			'SignHeight'     => '180',
			'BorderStyle'    => empty( $this->borderStyle ) ? 'Dashed' : $this->borderStyle,
			'BorderWidth'    => rgblank( $this->borderWidth ) ? '2px' : $this->borderWidth . 'px',
			'BorderColor'    => empty( $this->borderColor ) ? '#DDDDDD' : $this->borderColor,
			'RequiredPoints' => '15',
			'ClearImage'     => gf_signature()->get_base_url() . '/includes/super_signature/refresh.png',
			'PenCursor'      => gf_signature()->get_base_url() . '/includes/super_signature/pen.cur',
			'Visible'        => true,
			'ErrorMessage'   => '',
			'StartMessage'   => '',
			'SuccessMessage' => '',
		);

		/**
		 * Allow the SuperSignature initialization options to be customized.
		 *
		 * @param array $init_options The initialization options.
		 * @param GF_Field_Signature $field The current field object.
		 * @param array $form The current form object.
		 *
		 * @since 3.0.2
		 */
		return apply_filters( 'gform_signature_init_options', $init_options, $this, $form );
	}

}

GF_Fields::register( new GF_Field_Signature() );