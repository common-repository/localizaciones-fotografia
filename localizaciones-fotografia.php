<?php
/**
* Plugin Name: Localizaciones Fotografía
* Plugin URI: http://www.subexpuesta.com/plugin
* Description: Mapa con localizaciones de fotografía nocturna alojadas en www.subexpuesta.com
* Version: 0.1
* Author: Enrique Aparicio
* Author URI: http://www.enriqueaparicio.me
*/
require_once 'class.MapBuilder.php'; 

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );





if ( ! class_exists( 'Subexpuesta' ) ) {

	class Subexpuesta
	{
		/**
		 * Tag identifier used by file includes and selector attributes.
		 * @var string
		 */
		protected $tag = 'subexpuesta';

		/**
		 * User friendly name used to identify the plugin.
		 * @var string
		 */
		protected $name = 'Subexpuesta';

		/**
		 * Current version of the plugin.
		 * @var string
		 */
		protected $version = '0.1';

		/**
		 * List of options to determine plugin behaviour.
		 * @var array
		 */
		protected $options = array();

		/**
		 * List of settings displayed on the admin settings page.
		 * @var array
		 */
		protected $settings = array(
			'usuario' => array(
				'description' => 'Usuario de www.subexpuesta.com que quieras obtener sus localizaciones (Tal cual está en la web, mantener mayúsculas y espacios).'
			),
			'zoom' => array(
				'description' => 'Zoom inicial de tu mapa. Rango de 1 a 15',
				'validator' => 'numeric',
				'placeholder' => 5
			)
		);

		public function cargarMapa($altura, $anchura){
		// Create MapBuilder object. 
		$map = new MapBuilder(); 

		// Set map's center position by latitude and longitude coordinates. 
		$map->setCenter(40.399995, -4.087896); 

		// Set the default map type. 
		$map->setMapTypeId(MapBuilder::MAP_TYPE_ID_ROADMAP); 

		// Set width and height of the map. 
		$map->setSize($anchura, $altura); 

		// Set default zoom level. 
		if(empty($this->options['zoom']))
		{
			$map->setZoom(5); 	
		}
		else
		{
			$map->setZoom($this->options['zoom']); 		
		}
		

		// Make zoom control compact. 
		$map->setZoomControlStyle(MapBuilder::ZOOM_CONTROL_STYLE_SMALL); 

		if(empty($this->options['usuario']))
		{
			$response = wp_remote_get( 'http://www.subexpuesta.com/api/localizaciones/');	
		}
		else
		{
			$response = wp_remote_get( 'http://www.subexpuesta.com/api/localizaciones/autor/'.rawurlencode($this->options['usuario']));		
		}
		

			if( is_array($response) ) {
			  $header = $response['headers']; // array of http header lines
			  $body = $response['body']; // use the content
			  $localizacionesHTML = "";
			  $obj = json_decode($body);


			  for($i=0; $i< sizeof($obj);$i++){
			  		//$localizacionesHTML += $obj[$i]->titulo." <br>";
			  		//echo $obj[$i]->titulo." <br>";
			  		$map->addMarker($obj[$i]->latitud, $obj[$i]->longitud, array( 
				        'title' => $obj[$i]->titulo,
				        'icon' => 'http://res.cloudinary.com/djhqderty/image/upload/v1430472337/icono-mapa_xnqyqd.png', 
				        //'html' => '<strong>' . $obj[$i]->titulo. '</strong><div><img src="http://res.cloudinary.com/djhqderty/image/upload/c_thumb,h_80,w_80/v1438939574/'.$obj[$i]->cloudinaryId.'" /></div>', 
				        'html' => '<div>'.
		      //'<p><strong id="firstHeading" class="firstHeading">'.$obj[$i]->titulo.'</strong> ['.date("d-m-Y", strtotime($obj[$i]->fechaToma)).']</p>'.
		      '<p><strong id="firstHeading" class="firstHeading">'.$obj[$i]->titulo.'</strong></p>'.
		      '<div>'.
		      '<div style="float:left;"><p><a href="http://res.cloudinary.com/djhqderty/image/upload/v1438939574/'.$obj[$i]->cloudinaryId.'" target="_blank"><img align="left" style="margin-right: 15px;" src="http://res.cloudinary.com/djhqderty/image/upload/c_thumb,h_80,w_160/v1438939574/'.$obj[$i]->cloudinaryId.'" /></a></div>'.
		      str_replace("\n","</br>",$obj[$i]->acceso).'</p>'.
		      '<div style="float:left; margin-top:15px;"><p style="margin: 0px;"><strong>Latitud:</strong> '.$obj[$i]->latitud.' <strong>Longitud:</strong> '.$obj[$i]->longitud.'</p>'.
		      '<p><strong>Peligrosidad de la zona:</strong> '.$obj[$i]->peligrosidad.'/10   <strong>Contaminación lumnínica:</strong> '.$obj[$i]->contaminacionLuminica.'/10</p>'.
		      '<a href="http://res.cloudinary.com/djhqderty/image/upload/v1438939574/'.$obj[$i]->cloudinaryId.'" target="_blank">Ver en grande</a></div>'.
		      '</div>'.
		      '</div>',
				        'infoCloseOthers' => true 
		    		));
			  }
				
			}

		// Display the map. 
		$map->show(); 
		
		}

		public function pruebaParametros($altura, $anchura){
			echo "<p>pruebaParametros</p>";
			echo "<p>height:".$altura." </p>";
			echo "<p>width:".$anchura." </p>";
			echo "<p>pruebaParametros Option: ".$this->options['delay']."</p>";
		}

		/**
		 * Initiate the plugin by setting the default values and assigning any
		 * required actions and filters.
		 *
		 * @access public
		 */
		public function __construct()
		{
			if ( $options = get_option( $this->tag ) ) {
				$this->options = $options;
			}
			add_shortcode( $this->tag, array( &$this, 'shortcode' ) );
			if ( is_admin() ) {
				add_action( 'admin_init', array( &$this, 'settings' ) );
			}
		}

		
		/**
		 * Allow the teletype shortcode to be used.
		 *
		 * @access public
		 * @param array $atts
		 * @param string $content
		 * @return string
		 */
		public function shortcode( $atts, $content = null )
		{
			extract( shortcode_atts( array(
				'height' => false,
				'width' => false
			), $atts ) );
	 // Enqueue the required styles and scripts...
			$this->_enqueue();
	 // Add custom styles...
			$styles = array();
			if ( is_numeric( $height ) ) {
				$styles[] = esc_attr( 'height: ' . $height . 'px;' );
			}
	 // Build the list of class names...
			
	 // Output the terminal...
			ob_start();
			
			if(empty($width))
			{
				$width = 800;
			}
			if(empty($height))
			{
				$height = 400;
			}

			$this->cargarMapa($height, $width);
			//$this->pruebaParametros($height, $width);
			return ob_get_clean();
		}

		/**
		 * Add the setting fields to the Reading settings page.
		 *
		 * @access public
		 */
		public function settings()
		{
			$section = 'reading';
			add_settings_section(
				$this->tag . '_settings_section',
				$this->name,
				function () {
					echo '<p>Con cualquier incidente o problema técnico envie un correo a subexpuestaweb@gmail.com, gracias!</p>';
				},
				$section
			);
			foreach ( $this->settings AS $id => $options ) {
				$options['id'] = $id;
				add_settings_field(
					$this->tag . '_' . $id . '_settings',
					$id,
					array( &$this, 'settings_field' ),
					$section,
					$this->tag . '_settings_section',
					$options
				);
			}
			register_setting(
				$section,
				$this->tag,
				array( &$this, 'settings_validate' )
			);
		}

		/**
		 * Append a settings field to the the fields section.
		 *
		 * @access public
		 * @param array $args
		 */
		public function settings_field( array $options = array() )
		{
			$atts = array(
				'id' => $this->tag . '_' . $options['id'],
				'name' => $this->tag . '[' . $options['id'] . ']',
				'type' => ( isset( $options['type'] ) ? $options['type'] : 'text' ),				
				'value' => ( array_key_exists( 'default', $options ) ? $options['default'] : null )
			);
			if ( isset( $this->options[$options['id']] ) ) {
				$atts['value'] = $this->options[$options['id']];
			}
			if ( isset( $options['placeholder'] ) ) {
				$atts['placeholder'] = $options['placeholder'];
			}
			if ( isset( $options['type'] ) && $options['type'] == 'checkbox' ) {
				if ( $atts['value'] ) {
					$atts['checked'] = 'checked';
				}
				$atts['value'] = true;
			}
			array_walk( $atts, function( &$item, $key ) {
				$item = esc_attr( $key ) . '="' . esc_attr( $item ) . '"';
			} );
			?>
			<label>
				<input <?php echo implode( ' ', $atts ); ?> />
				<?php if ( array_key_exists( 'description', $options ) ) : ?>
				<?php esc_html_e( $options['description'] ); ?>
				<?php endif; ?>
			</label>
			<?php
		}

		/**
		 * Validate the settings saved.
		 *
		 * @access public
		 * @param array $input
		 * @return array
		 */
		public function settings_validate( $input )
		{
			$errors = array();
			foreach ( $input AS $key => $value ) {
				if ( $value == '' ) {
					unset( $input[$key] );
					continue;
				}
				$validator = false;
				if ( isset( $this->settings[$key]['validator'] ) ) {
					$validator = $this->settings[$key]['validator'];
				}
				switch ( $validator ) {
					case 'numeric':
						if ( is_numeric( $value ) ) {
							$input[$key] = intval( $value );
						} else {
							$errors[] = $key . ' must be a numeric value.';
							unset( $input[$key] );
						}
					break;
					default:
						 $input[$key] = strip_tags( $value );
					break;
				}
			}
			if ( count( $errors ) > 0 ) {
				add_settings_error(
					$this->tag,
					$this->tag,
					implode( '<br />', $errors ),
					'error'
				);
			}
			return $input;
		}

		/**
		 * Enqueue the required scripts and styles, only if they have not
		 * previously been queued.
		 *
		 * @access public
		 */
		protected function _enqueue()
		{
	 // Define the URL path to the plugin...
			$plugin_path = plugin_dir_url( __FILE__ );
	 // Enqueue the styles in they are not already...
			if ( !wp_style_is( $this->tag, 'enqueued' ) ) {
				wp_enqueue_style(
					$this->tag,
					$plugin_path . 'localizaciones-fotografia.css',
					array(),
					$this->version
				);
			}
	 // Enqueue the scripts if not already...
			if ( !wp_script_is( $this->tag, 'enqueued' ) ) {
				wp_enqueue_script(
					'google-maps',
					'https://maps.googleapis.com/maps/api/js?libraries=geometry,visualization'
				);
	 // Make the options available to JavaScript...
	 			$options = array_merge( array(
					'selector' => '.' . $this->tag
				), $this->options );
				wp_localize_script( $this->tag, $this->tag, $options );
				wp_enqueue_script( $this->tag );
			}
		}
	}
	new Subexpuesta;
}
