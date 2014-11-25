<?php
/**
 * Simple Shots.
 *
 * @package   Simple_Shots
 * @author    Constantine Kiriaze <hello@kiriaze.com>
 * @license   GPL-2.0+
 * @link      http://getsimple.io
 * @copyright 2013 Simple
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package Simple_Shots
 * @author  Constantine Kiriaze <hello@kiriaze.com>
 */

class Simple_Shots {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * @TODO - Rename "plugin-name" to the name your your plugin
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'simple-shots';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	public function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		add_shortcode('shots', array( &$this, 'shortcode') );
		add_action('wp_enqueue_scripts', array( &$this, 'enqueue_scripts') );

		// widget
		$this->widget = new Simple_Shots_Widget();
		add_action( 'widgets_init', array( &$this, 'simple_dribbble_feed' ) );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 *@return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
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
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );

	}

	/**
	 * Curl
	 *
	 * @since    1.0.0
	 */
	public function get_curl($url) {
	    if( function_exists('curl_init') ) {
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL,$url);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_HEADER, 0);
	        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
	        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0); 
	        $output = curl_exec($ch);
	        echo curl_error($ch);
	        curl_close($ch);
	        return $output;
	    }else{
	        return file_get_contents($url);
	    }

	}

	public function shortcode( $atts ) {
		extract( shortcode_atts( array(
			'player' 	=> '',
			'shots' 	=> 5
		), $atts ) );

		return $this->do_simple_shots( esc_attr($player), esc_attr($shots) );
	}

	public function do_simple_shots( $player, $shots ) {
		
		$url = 'http://api.dribbble.com/players/' . $player . '/shots/?per_page=' . $shots . '';

		$cache = dirname(__FILE__) . '/cache.json';

		if ( file_exists($cache) && filemtime($cache) > time() - 60*60 ) {
		    // If a cache file exists, and it is newer than 1 hour, use it
		    $images = json_decode( file_get_contents($cache), true ); //Decode as an json array
		} else{
		    // Make an API request and create the cache file
		    // For example, gets the 32 most popular images on Instagram
		    $response = $this->get_curl($url); //change request path to pull different photos

		    $images = array();

		    if( $response ) {
		    	
		    	$data = json_decode($response)->shots;

		        // Decode the response and build an array
		        foreach( $data as $item ) {

		            $title 		= $item->title;
		            $url 		= $item->url;
		            $src 		= $item->image_url;
		            $height 	= $item->height;
		            $width 		= $item->width;
		            $likes 		= $item->likes_count;
		            $views 		= $item->views_count;
		            $comments 	= $item->comments_count;

					$images[] = array(
						'title' 	=> htmlspecialchars($title),
						'url' 		=> htmlspecialchars($url),
						'src' 		=> htmlspecialchars($src),
						'height' 	=> htmlspecialchars($height),
						'width' 	=> htmlspecialchars($width),
						'likes' 	=> htmlspecialchars($likes),
						'views' 	=> htmlspecialchars($views),
						'comments' 	=> htmlspecialchars($comments),
					);

		        }
		        file_put_contents( $cache, json_encode($images) ); // Save as json
		    }
		}

		// sp($images);

		$output = '<ul class="simple-shots">';
			
		foreach( $images as $image ) {

			$output .= '<li class="simple-shot">';
			$output .= '<a href="' . $image['url'] . '" target="blank">';
			$output .= '<img height="' . $image['height'] . '" width="' . $image['width'] . '" src="' . $image['src'] . '" alt="'.$image['title'].'" />';
			$output .= '</a>';
			$output .= '</li>';
			
		}

		$output .= '</ul>';

		return $output;

	}

	public function enqueue_scripts() {
		// wp_enqueue_style( 'simple-shots-admin', plugins_url( 'assets/css/simple-shots-admin.css', __FILE__ ) );
		// wp_enqueue_style( 'simple-shots', plugins_url( 'assets/css/simple-shots.css', __FILE__ ) );
	}

	// REGISTER WIDGET
	public function simple_dribbble_feed() {
		register_widget( 'Simple_Shots_Widget' );
	}

}

// WIDGET CLASS
class Simple_Shots_Widget extends WP_Widget {

	/*--------------------------------------------------------------------*/
	/*	WIDGET SETUP
	/*--------------------------------------------------------------------*/
	public function __construct() {
		parent::__construct(
	 		'simple_shots', // BASE ID
			'Dribbble Shots (Simple)', // NAME
			array( 'description' => __( 'A widget that displays your Dribbble shots', 'simple' ), )
		);
	}
	

	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/

	/**
	 * Outputs the content of the widget.
	 *
	 * @args			The array of form elements
	 * @instance		The current instance of the widget
	 */
	 
	function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );
	    // WIDGET VARIABLES
		extract( $args );

		$title 		= apply_filters( 'widget_title', $instance['title'] );
		$account 	= $instance['account'];
		$shots 		= $instance['shots'];

		echo $before_widget;
			
		if ( !empty( $title ) ) echo $before_title . $title . $after_title;
		
		$plugin = Simple_Shots::get_instance();
		echo $plugin->do_simple_shots( esc_attr($account), esc_attr($shots) );
		
		echo $after_widget;
		
	} // END WIDGET

	/**
	 * Processes the widget's options to be saved.
	 *
	 * @new_instance	The previous instance of values before the update.
	 * @old_instance	The new instance of values to be generated via the update.
	 */
	 
	function update( $new_instance, $old_instance ) {
		
		// STRIP TAGS TO REMOVE HTML - IMPORTANT FOR TEXT IMPUTS
		$instance 				= $old_instance;
		$instance['title'] 		= strip_tags($new_instance['title']);
		$instance['account'] 	= trim($new_instance['account']);
		$instance['shots'] 		= trim($new_instance['shots']);
		$instance['cache'] 		= trim($new_instance['cache']);
		
		return $instance;
		
	}

	/**
	 * GENERATES THE ADMIN FORM FOR THE WIDGET
	 * @instance
	 */
	 
	function form( $instance ) {

		// WIDGET DEFAULTS
		$defaults = array(
			'title' 	=> 'Dribbble Widget',
			'account' 	=> 'constantine',
			'shots' 	=> 4,
			'cache'		=> 15
		);

		$instance 	= wp_parse_args( (array) $instance, $defaults );
		$title 		= $instance['title'];
		$account 	= $instance['account'];
		$shots 		= $instance['shots'];
		$cache 		= $instance['cache'];

		?>
		
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'simple'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('account'); ?>"><?php _e('<a href="http://www.dribbble.com/constantine">Dribbble</a> account:', 'simple'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('account'); ?>" name="<?php echo $this->get_field_name('account'); ?>" type="text" value="<?php echo $account; ?>" />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('shots'); ?>"><?php _e('Number of Shots:', 'simple'); ?></label>
			<select name="<?php echo $this->get_field_name('shots'); ?>">
				<?php for( $i = 1; $i <= 12; $i++ ) { ?>
					<option value="<?php echo $i; ?>" <?php selected( $i, $shots ); ?>><?php echo $i; ?></option>
				<?php } ?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('cache'); ?>"><?php _e('Cache:', 'simple'); ?> (Coming Soon!)</label>
			<input class="widefat" id="<?php echo $this->get_field_id('cache'); ?>" name="<?php echo $this->get_field_name('cache'); ?>" type="text" value="<?php echo $cache; ?>" />
		</p>
		
	<?php
		
	} // END FORM

} // END CLASS