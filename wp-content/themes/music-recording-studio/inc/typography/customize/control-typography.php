<?php
/**
 * Typography control class.
 *
 * @since  1.0.0
 * @access public
 */

class Music_Recording_Studio_Control_Typography extends WP_Customize_Control {

	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $type = 'music-recording-studio-typography';

	/**
	 * Array 
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $l10n = array();

	/**
	 * Set up our control.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  object  $manager
	 * @param  string  $id
	 * @param  array   $args
	 * @return void
	 */
	public function __construct( $manager, $id, $args = array() ) {

		// Let the parent class do its thing.
		parent::__construct( $manager, $id, $args );

		// Make sure we have labels.
		$this->l10n = wp_parse_args(
			$this->l10n,
			array(
				'color'       => esc_html__( 'Font Color', 'music-recording-studio' ),
				'family'      => esc_html__( 'Font Family', 'music-recording-studio' ),
				'size'        => esc_html__( 'Font Size',   'music-recording-studio' ),
				'weight'      => esc_html__( 'Font Weight', 'music-recording-studio' ),
				'style'       => esc_html__( 'Font Style',  'music-recording-studio' ),
				'line_height' => esc_html__( 'Line Height', 'music-recording-studio' ),
				'letter_spacing' => esc_html__( 'Letter Spacing', 'music-recording-studio' ),
			)
		);
	}

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_script( 'music-recording-studio-ctypo-customize-controls' );
		wp_enqueue_style(  'music-recording-studio-ctypo-customize-controls' );
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function to_json() {
		parent::to_json();

		// Loop through each of the settings and set up the data for it.
		foreach ( $this->settings as $setting_key => $setting_id ) {

			$this->json[ $setting_key ] = array(
				'link'  => $this->get_link( $setting_key ),
				'value' => $this->value( $setting_key ),
				'label' => isset( $this->l10n[ $setting_key ] ) ? $this->l10n[ $setting_key ] : ''
			);

			if ( 'family' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_families();

			elseif ( 'weight' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_weight_choices();

			elseif ( 'style' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_style_choices();
		}
	}

	/**
	 * Underscore JS template to handle the control's output.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function content_template() { ?>

		<# if ( data.label ) { #>
			<span class="customize-control-title">{{ data.label }}</span>
		<# } #>

		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{{ data.description }}}</span>
		<# } #>

		<ul>

		<# if ( data.family && data.family.choices ) { #>

			<li class="typography-font-family">

				<# if ( data.family.label ) { #>
					<span class="customize-control-title">{{ data.family.label }}</span>
				<# } #>

				<select {{{ data.family.link }}}>

					<# _.each( data.family.choices, function( label, choice ) { #>
						<option value="{{ choice }}" <# if ( choice === data.family.value ) { #> selected="selected" <# } #>>{{ label }}</option>
					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.weight && data.weight.choices ) { #>

			<li class="typography-font-weight">

				<# if ( data.weight.label ) { #>
					<span class="customize-control-title">{{ data.weight.label }}</span>
				<# } #>

				<select {{{ data.weight.link }}}>

					<# _.each( data.weight.choices, function( label, choice ) { #>

						<option value="{{ choice }}" <# if ( choice === data.weight.value ) { #> selected="selected" <# } #>>{{ label }}</option>

					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.style && data.style.choices ) { #>

			<li class="typography-font-style">

				<# if ( data.style.label ) { #>
					<span class="customize-control-title">{{ data.style.label }}</span>
				<# } #>

				<select {{{ data.style.link }}}>

					<# _.each( data.style.choices, function( label, choice ) { #>

						<option value="{{ choice }}" <# if ( choice === data.style.value ) { #> selected="selected" <# } #>>{{ label }}</option>

					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.size ) { #>

			<li class="typography-font-size">

				<# if ( data.size.label ) { #>
					<span class="customize-control-title">{{ data.size.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.size.link }}} value="{{ data.size.value }}" />

			</li>
		<# } #>

		<# if ( data.line_height ) { #>

			<li class="typography-line-height">

				<# if ( data.line_height.label ) { #>
					<span class="customize-control-title">{{ data.line_height.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.line_height.link }}} value="{{ data.line_height.value }}" />

			</li>
		<# } #>

		<# if ( data.letter_spacing ) { #>

			<li class="typography-letter-spacing">

				<# if ( data.letter_spacing.label ) { #>
					<span class="customize-control-title">{{ data.letter_spacing.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.letter_spacing.link }}} value="{{ data.letter_spacing.value }}" />

			</li>
		<# } #>

		</ul>
	<?php }

	/**
	 * Returns the available fonts.  Fonts should have available weights, styles, and subsets.
	 *
	 * @todo Integrate with Google fonts.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_fonts() { return array(); }

	/**
	 * Returns the available font families.
	 *
	 * @todo Pull families from `get_fonts()`.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	function get_font_families() {

		return array(
			'' => __( 'No Fonts', 'music-recording-studio' ),
        'Abril Fatface' => __( 'Abril Fatface', 'music-recording-studio' ),
        'Acme' => __( 'Acme', 'music-recording-studio' ),
        'Anton' => __( 'Anton', 'music-recording-studio' ),
        'Architects Daughter' => __( 'Architects Daughter', 'music-recording-studio' ),
        'Arimo' => __( 'Arimo', 'music-recording-studio' ),
        'Arsenal' => __( 'Arsenal', 'music-recording-studio' ),
        'Arvo' => __( 'Arvo', 'music-recording-studio' ),
        'Alegreya' => __( 'Alegreya', 'music-recording-studio' ),
        'Alfa Slab One' => __( 'Alfa Slab One', 'music-recording-studio' ),
        'Averia Serif Libre' => __( 'Averia Serif Libre', 'music-recording-studio' ),
        'Bangers' => __( 'Bangers', 'music-recording-studio' ),
        'Boogaloo' => __( 'Boogaloo', 'music-recording-studio' ),
        'Bad Script' => __( 'Bad Script', 'music-recording-studio' ),
        'Bitter' => __( 'Bitter', 'music-recording-studio' ),
        'Bree Serif' => __( 'Bree Serif', 'music-recording-studio' ),
        'BenchNine' => __( 'BenchNine', 'music-recording-studio' ),
        'Cabin' => __( 'Cabin', 'music-recording-studio' ),
        'Cardo' => __( 'Cardo', 'music-recording-studio' ),
        'Courgette' => __( 'Courgette', 'music-recording-studio' ),
        'Cherry Swash' => __( 'Cherry Swash', 'music-recording-studio' ),
        'Cormorant Garamond' => __( 'Cormorant Garamond', 'music-recording-studio' ),
        'Crimson Text' => __( 'Crimson Text', 'music-recording-studio' ),
        'Cuprum' => __( 'Cuprum', 'music-recording-studio' ),
        'Cookie' => __( 'Cookie', 'music-recording-studio' ),
        'Chewy' => __( 'Chewy', 'music-recording-studio' ),
        'Days One' => __( 'Days One', 'music-recording-studio' ),
        'Dosis' => __( 'Dosis', 'music-recording-studio' ),
        'Droid Sans' => __( 'Droid Sans', 'music-recording-studio' ),
        'Economica' => __( 'Economica', 'music-recording-studio' ),
        'Fredoka One' => __( 'Fredoka One', 'music-recording-studio' ),
        'Fjalla One' => __( 'Fjalla One', 'music-recording-studio' ),
        'Francois One' => __( 'Francois One', 'music-recording-studio' ),
        'Frank Ruhl Libre' => __( 'Frank Ruhl Libre', 'music-recording-studio' ),
        'Gloria Hallelujah' => __( 'Gloria Hallelujah', 'music-recording-studio' ),
        'Great Vibes' => __( 'Great Vibes', 'music-recording-studio' ),
        'Handlee' => __( 'Handlee', 'music-recording-studio' ),
        'Hammersmith One' => __( 'Hammersmith One', 'music-recording-studio' ),
        'Inconsolata' => __( 'Inconsolata', 'music-recording-studio' ),
        'Indie Flower' => __( 'Indie Flower', 'music-recording-studio' ),
        'IM Fell English SC' => __( 'IM Fell English SC', 'music-recording-studio' ),
        'Julius Sans One' => __( 'Julius Sans One', 'music-recording-studio' ),
        'Josefin Slab' => __( 'Josefin Slab', 'music-recording-studio' ),
        'Josefin Sans' => __( 'Josefin Sans', 'music-recording-studio' ),
        'Kanit' => __( 'Kanit', 'music-recording-studio' ),
        'Lobster' => __( 'Lobster', 'music-recording-studio' ),
        'Lato' => __( 'Lato', 'music-recording-studio' ),
        'Lora' => __( 'Lora', 'music-recording-studio' ),
        'Libre Baskerville' => __( 'Libre Baskerville', 'music-recording-studio' ),
        'Lobster Two' => __( 'Lobster Two', 'music-recording-studio' ),
        'Merriweather' => __( 'Merriweather', 'music-recording-studio' ),
        'Monda' => __( 'Monda', 'music-recording-studio' ),
        'Montserrat' => __( 'Montserrat', 'music-recording-studio' ),
        'Muli' => __( 'Muli', 'music-recording-studio' ),
        'Marck Script' => __( 'Marck Script', 'music-recording-studio' ),
        'Noto Serif' => __( 'Noto Serif', 'music-recording-studio' ),
        'Open Sans' => __( 'Open Sans', 'music-recording-studio' ),
        'Overpass' => __( 'Overpass', 'music-recording-studio' ),
        'Overpass Mono' => __( 'Overpass Mono', 'music-recording-studio' ),
        'Oxygen' => __( 'Oxygen', 'music-recording-studio' ),
        'Orbitron' => __( 'Orbitron', 'music-recording-studio' ),
        'Patua One' => __( 'Patua One', 'music-recording-studio' ),
        'Pacifico' => __( 'Pacifico', 'music-recording-studio' ),
        'Padauk' => __( 'Padauk', 'music-recording-studio' ),
        'Playball' => __( 'Playball', 'music-recording-studio' ),
        'Playfair Display' => __( 'Playfair Display', 'music-recording-studio' ),
        'PT Sans' => __( 'PT Sans', 'music-recording-studio' ),
        'Philosopher' => __( 'Philosopher', 'music-recording-studio' ),
        'Permanent Marker' => __( 'Permanent Marker', 'music-recording-studio' ),
        'Poiret One' => __( 'Poiret One', 'music-recording-studio' ),
        'Quicksand' => __( 'Quicksand', 'music-recording-studio' ),
        'Quattrocento Sans' => __( 'Quattrocento Sans', 'music-recording-studio' ),
        'Raleway' => __( 'Raleway', 'music-recording-studio' ),
        'Rubik' => __( 'Rubik', 'music-recording-studio' ),
        'Rokkitt' => __( 'Rokkitt', 'music-recording-studio' ),
        'Russo One' => __( 'Russo One', 'music-recording-studio' ),
        'Righteous' => __( 'Righteous', 'music-recording-studio' ),
        'Slabo' => __( 'Slabo', 'music-recording-studio' ),
        'Source Sans Pro' => __( 'Source Sans Pro', 'music-recording-studio' ),
        'Shadows Into Light Two' => __( 'Shadows Into Light Two', 'music-recording-studio'),
        'Shadows Into Light' => __( 'Shadows Into Light', 'music-recording-studio' ),
        'Sacramento' => __( 'Sacramento', 'music-recording-studio' ),
        'Shrikhand' => __( 'Shrikhand', 'music-recording-studio' ),
        'Tangerine' => __( 'Tangerine', 'music-recording-studio' ),
        'Ubuntu' => __( 'Ubuntu', 'music-recording-studio' ),
        'VT323' => __( 'VT323', 'music-recording-studio' ),
        'Varela Round' => __( 'Varela Round', 'music-recording-studio' ),
        'Vampiro One' => __( 'Vampiro One', 'music-recording-studio' ),
        'Vollkorn' => __( 'Vollkorn', 'music-recording-studio' ),
        'Volkhov' => __( 'Volkhov', 'music-recording-studio' ),
        'Yanone Kaffeesatz' => __( 'Yanone Kaffeesatz', 'music-recording-studio' )
		);
	}

	/**
	 * Returns the available font weights.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_font_weight_choices() {

		return array(
			'' => esc_html__( 'No Fonts weight', 'music-recording-studio' ),
			'100' => esc_html__( 'Thin',       'music-recording-studio' ),
			'300' => esc_html__( 'Light',      'music-recording-studio' ),
			'400' => esc_html__( 'Normal',     'music-recording-studio' ),
			'500' => esc_html__( 'Medium',     'music-recording-studio' ),
			'700' => esc_html__( 'Bold',       'music-recording-studio' ),
			'900' => esc_html__( 'Ultra Bold', 'music-recording-studio' ),
		);
	}

	/**
	 * Returns the available font styles.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_font_style_choices() {

		return array(
			'' => esc_html__( 'No Fonts Style', 'music-recording-studio' ),
			'normal'  => esc_html__( 'Normal', 'music-recording-studio' ),
			'italic'  => esc_html__( 'Italic', 'music-recording-studio' ),
			'oblique' => esc_html__( 'Oblique', 'music-recording-studio' )
		);
	}
}
