<?php
/**
 * Pods Frontier Template Editor.
 *
 * @package   Pods_Frontier_Template_Editor
 * @author    David Cramer <david@digilab.co.za>
 * @license   GPL-2.0+
 * @link      
 * @copyright 2014 David Cramer
 */

/**
 * Plugin class.
 * @package Pods_Frontier_Template_Editor
 * @author  David Cramer <david@digilab.co.za>
 */
class Pods_Frontier_Template_Editor {

	/**
	 * @var     string
	 */
	const VERSION = '1.00';
	/**
	 * @var      string
	 */
	protected $plugin_slug = 'pods-frontier';
	/**
	 * @var      object
	 */
	protected static $instance = null;
	/**
	 * @var      array
	 */
	protected $element_instances = array();
	/**
	 * @var      array
	 */
	protected $element_css_once = array();
	/**
	 * @var      array
	 */
	protected $elements = array();
	/**
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;
	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_stylescripts' ) );
		
		add_action('wp_footer', array( $this, 'footer_scripts' ) );

		add_action( 'init', array( $this, 'activate_metaboxes' ) );		

		// detect pod template for styles & scripts
		if(!is_admin()){
			add_action('wp', array( $this, 'detect_pod_template' ), 100 );
		}

		add_filter( 'pods_components_register', array( $this, 'register_frontier_modules' ) );


	}
	/**
	 * register frontier modules
	 */

	function register_frontier_modules($components){

		$components[]['File'] = dirname( __FILE__ ) . '/templates.php';
		$components[]['File'] = dirname( __FILE__ ) . '/forms.php';
		$components[]['File'] = dirname( __FILE__ ) . '/layouts.php';
		
		return $components;
	}	


	/**
	 * Return an instance of this class.
	 *
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 */
	public function load_plugin_textdomain() {
		// TODO: Add translations as need in /languages
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
	}
	
	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 *
	 * @return    null
	 */
	public function enqueue_admin_stylescripts() {

		$screen = get_current_screen();

		

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		if ( in_array( $screen->id, $this->plugin_screen_hook_suffix ) ) {
			$slug = array_search( $screen->id, $this->plugin_screen_hook_suffix );			
			//$configfiles = glob( $this->get_path( __FILE__ ) .'configs/'.$slug.'-*.php' );
			if(file_exists($this->get_path( __FILE__ ) .'configs/fieldgroups-'.$slug.'.php')){
				include $this->get_path( __FILE__ ) .'configs/fieldgroups-'.$slug.'.php';		
			}else{
				return;
			}

			if( !empty( $configfiles ) ) {
				// Always good to have.
				wp_enqueue_media();
				wp_enqueue_script('media-upload');

				foreach ($configfiles as $key=>$fieldfile) {
					include $fieldfile;
					if(!empty($group['scripts'])){
						foreach($group['scripts'] as $script){
							wp_enqueue_script( $this->plugin_slug . '-' . strtok($script, '.'), $this->get_url( 'assets/js/'.$script , __FILE__ ) , array('jquery') );					
						}
					}
					if(!empty($group['styles'])){
						foreach($group['styles'] as $style){
							wp_enqueue_style( $this->plugin_slug . '-' . strtok($style, '.'), $this->get_url( 'assets/css/'.$style , __FILE__ ) );
						}
					}
				}
			}	
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', $this->get_url( 'assets/css/panel.css', __FILE__ ), array(), self::VERSION );
			wp_enqueue_script( $this->plugin_slug .'-admin-scripts', $this->get_url( 'assets/js/panel.js', __FILE__ ), array(), self::VERSION );
		}

	}

	
	
	
	/**
	 * Process a field value
	 *
	 */
	public function process_value($type, $value){

		// for later- when vlaues need processing.
		return $value;	

	}

	
	/**
	 * Register metaboxes.
	 *
	 *
	 * @return    null
	 */
	public function activate_metaboxes() {
		add_action('add_meta_boxes', array($this, 'add_metaboxes'), 5, 4);
		add_action('save_post', array($this, 'save_post_metaboxes'), 1, 2);
	}

	

	/**
	 * setup meta boxes.
	 *
	 *
	 * @return    null
	 */
	function add_metaboxes($slug, $post){
		// Always good to have.
		wp_enqueue_media();
		wp_enqueue_script('media-upload');
		
		if(!in_array($post->post_type, array('_pods_template','frontier_view'))){return;}

		wp_enqueue_script( 'jquery-ui-resizable' );
		wp_enqueue_script( $this->plugin_slug . '-panel-script', $this->get_url( 'assets/js/panel.js', __FILE__ ), array( 'jquery' ), self::VERSION );
		wp_enqueue_script( $this->plugin_slug . '-cm-comp', $this->get_url( 'assets/js/codemirror-compressed.js', __FILE__ ), array( 'jquery' ), self::VERSION );
		wp_enqueue_script( $this->plugin_slug . '-cm-editor', $this->get_url( 'assets/js/editor.js', __FILE__ ), array( 'jquery' ), self::VERSION );
		wp_enqueue_script( $this->plugin_slug . '-handlebarsjs', $this->get_url( 'assets/js/handlebars.js', __FILE__ ), array( 'jquery' ), self::VERSION );
		wp_enqueue_script( $this->plugin_slug . '-baldrickjs', $this->get_url( 'assets/js/jquery.baldrick.js', __FILE__ ), array( 'jquery' ), self::VERSION );
		wp_enqueue_script( $this->plugin_slug . '-handlebars-baldrick', $this->get_url( 'assets/js/handlebars.baldrick.js', __FILE__ ), array( 'jquery' ), self::VERSION );

		wp_enqueue_style( $this->plugin_slug . '-panel-styles', $this->get_url( 'assets/css/panel.css', __FILE__ ), array(), self::VERSION );
		wp_enqueue_style( $this->plugin_slug . '-cm-css', $this->get_url( 'assets/css/codemirror.css', __FILE__ ), array(), self::VERSION );
		wp_enqueue_style( $this->plugin_slug . '-view_template-styles', $this->get_url( 'assets/css/styles-view_template.css', __FILE__ ), array(), self::VERSION );
		wp_enqueue_style( $this->plugin_slug . '-pod_reference-styles', $this->get_url( 'assets/css/styles-pod_reference.css', __FILE__ ), array(), self::VERSION );
		
		add_meta_box('view_template', 'Frontier', array($this, 'render_metaboxes_custom'), '_pods_template', 'normal', 'high', array( 'slug' => 'view_template', 'groups' => array() ) );
		add_meta_box('pod_reference', 'Pod Reference', array($this, 'render_metaboxes_custom'), '_pods_template', 'side', 'default', array( 'slug' => 'pod_reference', 'groups' => array() ) );

	}




	/**
	 * render template based meta boxes.
	 *
	 *
	 * @return    null
	 */
	function render_metaboxes_custom($post, $args){
		// include the metabox view
		echo '<input type="hidden" name="pods_frontier_template_editor_metabox" id="pods_frontier_template_editor_metabox" value="'.wp_create_nonce(plugin_basename(__FILE__)).'" />';
		echo '<input type="hidden" name="pods_frontier_template_editor_metabox_prefix[]" value="'.$args['args']['slug'].'" />';

		//get post meta to $atts $ post content
		$atts = get_post_meta($post->ID, $args['args']['slug'], true);
		$content = $post->post_content;

		if(file_exists($this->get_path( __FILE__ ) . 'includes/element-' . $args['args']['slug'] . '.php')){
			include $this->get_path( __FILE__ ) . 'includes/element-' . $args['args']['slug'] . '.php';
		}elseif(file_exists($this->get_path( __FILE__ ) . 'includes/element-' . $args['args']['slug'] . '.html')){
			include $this->get_path( __FILE__ ) . 'includes/element-' . $args['args']['slug'] . '.html';
		}
		// add script
		if(file_exists($this->get_path( __FILE__ ) . 'assets/js/scripts-' . $args['args']['slug'] . '.php')){
			echo "<script type=\"text/javascript\">\r\n";
			include $this->get_path( __FILE__ ) . 'assets/js/scripts-' . $args['args']['slug'] . '.php';
			echo "</script>\r\n";
		}elseif(file_exists($this->get_path( __FILE__ ) . 'assets/js/scripts-' . $args['args']['slug'] . '.js')){
			wp_enqueue_script( $this->plugin_slug . '-' . $args['args']['slug'] . '-script', $this->get_url( 'assets/js/scripts-' . $args['args']['slug'] . '.js', __FILE__ ), array( 'jquery' ), self::VERSION );
		}
		
	}

	/**
	 * setup meta boxes.
	 *
	 *
	 * @return    null
	 */
	public function get_post_meta($id, $key = null, $single = false){
		
		if(!empty($key)){

			if(file_exists($this->get_path( __FILE__ ) .'configs/fieldgroups-pods_frontier_template_editor.php')){
				include $this->get_path( __FILE__ ) .'configs/fieldgroups-pods_frontier_template_editor.php';		
			}else{
				return;
			}

			$field_type = 'text';
			foreach( $configfiles as $config=>$file ){
				include $file;
				if(isset($group['fields'][$key]['type'])){
					$field_type = $group['fields'][$key]['type'];
					break;
				}
			}
			$key = 'pods_frontier_template_editor_' . $key;
		}
		if( false === $single){
			$metas = get_post_meta( $id, $key );
			foreach ($metas as $key => &$value) {
				$value = $this->process_value( $field_type, $value );
			}
			return $metas;
		}
		return $this->process_value( $field_type, get_post_meta( $id, $key, $single ) );

	}


	/**
	 * save metabox data
	 *
	 *
	 */
	function save_post_metaboxes($pid, $post){

		if(!isset($_POST['pods_frontier_template_editor_metabox']) || !isset($_POST['pods_frontier_template_editor_metabox_prefix'])){return;}


		if(!wp_verify_nonce($_POST['pods_frontier_template_editor_metabox'], plugin_basename(__FILE__))){
			return $post->ID;
		}
		if(!current_user_can( 'edit_post', $post->ID)){
			return $post->ID;
		}
		if($post->post_type == 'revision' ){return;}
		
		foreach( $_POST['pods_frontier_template_editor_metabox_prefix'] as $prefix ){
			if(!isset($_POST[$prefix])){continue;}

			delete_post_meta($post->ID, $prefix);
			add_post_meta($post->ID, $prefix, $_POST[$prefix]);

		}
	}	
	/**
	 * create and register an instance ID
	 *
	 */
	public function element_instance_id($id, $process){

		$this->element_instances[$id][$process][] = true;
		$count = count($this->element_instances[$id][$process]);
		if($count > 1){
			return $id.($count-1);
		}
		return $id;
	}

	/**
	 * Render the element
	 *
	 */
	public function render_element($atts, $content, $slug, $head = false) {
		
		$raw_atts = $atts;
		

		if(!empty($head)){
			$instanceID = $this->element_instance_id('pods_frontier_template_editor'.$slug, 'header');
		}else{
			$instanceID = $this->element_instance_id('pods_frontier_template_editor'.$slug, 'footer');
		}

		if(file_exists($this->get_path( __FILE__ ) .'configs/fieldgroups-'.$slug.'.php')){
			include $this->get_path( __FILE__ ) .'configs/fieldgroups-'.$slug.'.php';		
		
			$defaults = array();
			foreach($configfiles as $file){

				include $file;
				foreach($group['fields'] as $variable=>$conf){
					if(!empty($group['multiple'])){
						$value = array($this->process_value($conf['type'],$conf['default']));
					}else{
						$value = $this->process_value($conf['type'],$conf['default']);
					}
					if(!empty($group['multiple'])){
						if(isset($atts[$variable.'_1'])){
							$index = 1;
							$value=array();
							while(isset($atts[$variable.'_'.$index])){
								$value[] = $this->process_value($conf['type'],$atts[$variable.'_'.$index]);
								$index++;
							}
						}elseif (isset($atts[$variable])) {
							if(is_array($atts[$variable])){
								foreach($atts[$variable] as &$varval){
									$varval = $this->process_value($conf['type'],$varval);
								}
								$value = $atts[$variable];
							}else{
								$value[] = $this->process_value($conf['type'],$atts[$variable]);
							}
						}
					}else{
						if(isset($atts[$variable])){
							$value = $this->process_value($conf['type'],$atts[$variable]);
						}
					}
					
					if(!empty($group['multiple']) && !empty($value)){
						foreach($value as $key=>$val){
							$groups[$group['master']][$key][$variable] = $val;
						}
					}
					$defaults[$variable] = $value;
				}
			}

			$atts = $defaults;
		}
		// pull in the assets
		$assets = array();
		if(file_exists($this->get_path( __FILE__ ) . 'assets/assets-'.$slug.'.php')){
			include $this->get_path( __FILE__ ) . 'assets/assets-'.$slug.'.php';
		}

		if(!empty($head)){

			// process headers - CSS
			if(file_exists($this->get_path( __FILE__ ) . 'assets/css/styles-'.$slug.'.php')){
				echo "<style type=\"text/css\">\r\n";
				include $this->get_path( __FILE__ ) . 'assets/css/styles-'.$slug.'.php';
				echo "</style>\r\n";
			}else if( file_exists($this->get_path( __FILE__ ) . 'assets/css/styles-'.$slug.'.css')){
				wp_enqueue_style( $this->plugin_slug . '-'.$slug.'-styles', $this->get_url( 'assets/css/styles-'.$slug.'.css', __FILE__ ), array(), self::VERSION );
			}
			// process headers - JS
			if(file_exists($this->get_path( __FILE__ ) . 'assets/js/scripts-'.$slug.'.php')){
				ob_start();
				include $this->get_path( __FILE__ ) . 'assets/js/scripts-'.$slug.'.php';				
				$this->element_footer_scripts[] = ob_get_clean();
			}else if( file_exists($this->get_path( __FILE__ ) . 'assets/js/scripts-'.$slug.'.js')){
				wp_enqueue_script( $this->plugin_slug . '-'.$slug.'-script', $this->get_url( 'assets/js/scripts-'.$slug.'.js', __FILE__ ), array( 'jquery' ), self::VERSION , true );
			}
			return;
		}
		ob_start();
		if(file_exists($this->get_path( __FILE__ ) . 'includes/element-'.$slug.'.php')){
			include $this->get_path( __FILE__ ) . 'includes/element-'.$slug.'.php';
		}else if( file_exists($this->get_path( __FILE__ ) . 'includes/element-'.$slug.'.html')){
			include $this->get_path( __FILE__ ) . 'includes/element-'.$slug.'.html';
		}
		$out = ob_get_clean();
		
		return do_shortcode($out);
	}


	/**
	 * Render any footer scripts
	 *
	 */
	public function footer_scripts() {

		if(!empty($this->element_footer_scripts)){
			echo "<script type=\"text/javascript\">\r\n";
				foreach($this->element_footer_scripts as $script){
					echo $script."\r\n";
				}
			echo "</script>\r\n";
		}
	}

	

	/***
	 * Get the current URL
	 *
	 */
	static function get_url($src = null, $path = null) {
		if(!empty($path)){
			return plugins_url( $src, $path);
		}
		return trailingslashit( plugins_url( $path , __FILE__ ) );
	}

	/***
	 * Get the current URL
	 *
	 */
	static function get_path($src = null) {
		return plugin_dir_path( $src );

	}

	/***
	 * detect a pod template then render the styles & scripts if any.
	 *
	 */

	public function detect_pod_template(){
		
		global $wp_query, $frontier_styles, $frontier_scripts;

		$regex = frontier_get_regex(array('pods'));

		// find used shortcodes within posts
		foreach ($wp_query->posts as $key => &$post) {
			preg_match_all('/' . $regex . '/s', $post->post_content, $shortcodes);

			if(!empty($shortcodes[3])){
				foreach($shortcodes[3] as $foundkey=>$args){

					$atts = shortcode_parse_atts($shortcodes[3][$foundkey]);
					if(isset($atts['template'])){
						$template = pods()->api->load_template( array('name' => $atts['template']) );
						if( !empty( $template ) ){
							// got a template - check for styles & scripts
							$meta = get_post_meta($template['id'], 'view_template', true);
							
							if(!empty($meta['css'])){
								
								$frontier_styles .= $meta['css'];
								
								add_action( 'wp_head', function(){
									global $frontier_styles;
									if(!empty($frontier_styles)){
										echo "<style type=\"text/css\">\r\n";
											echo $frontier_styles;
										echo "</style>\r\n";
									}
								}, 100 );
							}

							if(!empty($meta['js'])){
								
								$frontier_scripts .= $meta['js'];
								
								add_action( 'wp_footer', function(){
									global $frontier_scripts;
									if(!empty($frontier_scripts)){
										echo "<script type=\"text/javascript\">\r\n";
											echo $frontier_scripts;
										echo "</script>\r\n";
									}
								}, 100 );
							}
						}
					}
				}
			}
		}
	}
	
}
