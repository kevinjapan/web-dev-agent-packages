<?php
/*
Plugin Name: Web Dev Agent - Packages
Plugin URI: 
Description: Display Web Agency Pricing Packages
Version: 1.0.0
Author: edk
Author URI: evolutiondesuka.com
*/

// ensure application access only
if( !defined('ABSPATH') ) {
   exit;
}


class WedDevAgentPackages {

	public function __construct() {

      // create custom post type 'wda_package'
      add_action( 'init', array($this,'create_package_post_type' ));

      // assets
      add_action('wp_enqueue_scripts',array($this,'enqueue_assets'));

      // 'edit post' page
		add_action('add_meta_boxes', array( $this,'add_package_meta_box')); 
		add_action('save_post',array($this,'save_custom_meta'));

      // front-end UI
      add_shortcode('packages',array($this,'shortcode_html'));
   }


   //
   // create custom post type 'wda_package'
   //
   public function create_package_post_type() {

      $labels = array(
         'name' => __('WDA Packages','web-dev-agent'),
         'singular_name' =>  __('WDA Package','web-dev-agent'),
         'menu_name' => 'Packages',
      );
      $args = array(
         'labels' => $labels,
         'description' => 'Package Custom Post Type',
         'supports' => array('title','editor','thumbnail'),
         'hierarchical' => true,
         'taxonomies' => array('category'),
         'public' => true,
         'show_ui' => true,
         'show_in_menu' => true,
         'show_in_nav_menus' => true,
         // 'show_in_rest' => true, // in the REST API. Set this to true for the post type to be available in the block editor.
         'has_archive' => true,
         'rewrite' => array( 'slug' => 'package' ),  // custom slug
         'exclude_from_search' => true,
         'publicly_queryable' => true,    // false will exclude archive- and single- templates
         'menu_icon' => 'dashicons-media-text',
      );
      register_post_type('wda_package',$args);
   }


   //
   // assets
   //
   public function enqueue_assets() 
   {
      // wp_enqueue_style(
      //    'wda_outline',
      //    plugin_dir_url( __FILE__ ) . 'css/outline.css',
      //    array(),
      //    1,
      //    'all'
      // );  
      // wp_enqueue_style(
      //    'wda_outline_layouts',
      //    plugin_dir_url( __FILE__ ) . 'css/outline-layouts.css',
      //    array(),
      //    1,
      //    'all'
      // );  
      // wp_enqueue_style(
      //    'wda_outline_custom_props',
      //    plugin_dir_url( __FILE__ ) . 'css/outline-custom-props.css',
      //    array(),
      //    1,
      //    'all'
      // );  
      // wp_enqueue_style(
      //    'wda_outline_utilities',
      //    plugin_dir_url( __FILE__ ) . 'css/outline-utilities.css',
      //    array(),
      //    1,
      //    'all'
      // ); 
      // wp_enqueue_script(
      //    'web-dev-agent',
      //    plugin_dir_url( __FILE__ ) . 'js/web-dev-agent.js',
      //    array('jquery'),
      //    1,
      //    true
      // );
   }
   

   //
   // 'edit post' page
   //
	public function add_package_meta_box( $post_type ) {

		// Limit meta box to certain post types
		$post_types = array( 'wda_package' );

		if ( in_array( $post_type, $post_types ) ) {

			add_meta_box(
				'wda_package',
				__( 'Tagline', 'textdomain' ),
				array( $this, 'render_package_meta_box' ),
				$post_types,
				'advanced',
				'high'
			);
		}
	}

   //
   // so many WP tutorials use very similar names for keys and variables across their examples -
   // eg below, they might use '_features' for the meta key / $features for variables / name="features[]" in form elements
   // - this tripped me up a lot, not being able to easily and clearly distinguish as I scan the code
   // so, good practice will be to explicitly include the purpose the identifier refers to in it's name, as well as the data 
   // - eg a meta key id as '_features_meta_key' where 'features'=data, 'meta_key'=purpose/what it is.
   //

   // future : we want this list configurable by site owner
   private function get_features_list() {
      return array(
         'custom design',
         'wireframes',
         'responsive',
         '5 pages',
         '10 pages',
         'workshop',
         'seo',
         'email',
         'hosting support',
         'training',
      );
   }

   public function render_package_meta_box($post) {

		wp_nonce_field('wda_packages_meta_box','wda_packages_meta_nonce');

      $all_features = $this->get_features_list();
      $saved_features = (get_post_meta($post->ID,'_features_meta_key',true)) ? get_post_meta($post->ID,'_features_meta_key',true) : array();

      // if (isset($_POST)) die(print_r($custom));     // debug
      ?>
      <h3><?php _e('Features','wda-dev-agent_packages');?></h3>
      <ul>
         <?php
            foreach ($all_features as $feature) {
               ?>
               <li><input type="checkbox" name="features_array_fields[]" value="<?php echo $feature;?>" 
                  <?php echo(in_array($feature,$saved_features) ? ' checked ' : ''); ?>  /><?php echo $feature;?>
               </li>
               <?php
            }
         ?>
      </ul>
      <?php
   }

	public function save_custom_meta($post_id) {

      // if (isset($_POST)) die(print_r($_POST));     // debug

      // verify nonce
		if (!isset( $_POST['wda_packages_meta_nonce'])) return $post_id;
		$nonce = $_POST['wda_packages_meta_nonce'];
		if (!wp_verify_nonce($nonce,'wda_packages_meta_box')) return $post_id;

		// autosave, our form has not been submitted
		// if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

		// check the user is authorised
      if (!current_user_can('edit_page',$post_id)) return $post_id;

      // access features_array_fields[] from the form in $_POST
      if(isset($_POST['features_array_fields'])) {

         // sanitize user input
         $features = (array)$_POST['features_array_fields'];
         $features = array_map('sanitize_text_field', $features);

         // save array data
         update_post_meta($post_id,'_features_meta_key',$features);
      }
      else {
         delete_post_meta($post_id,'_features_meta_key');
      }
	}


   //
   // front-end UI - shortcode
   //
   public function shortcode_html() {

      ob_start(); // buffer output

      $args = array(
         'post_type' => 'wda_package',
         'posts_per_page' => 10,
      );
      $loop = new WP_Query($args);

      ?>
      <section class="feature_tiles bg_white">
         <h3>Packages</h3>
         <ul>
         <?php

         while ( $loop->have_posts() ) {
            $loop->the_post();
            $features = (array) get_post_meta(get_the_ID(),'_features_meta_key',true);
               ?>
               <li>
                  <?php
                  if(has_post_thumbnail()):?>
                     <img src="<?php the_post_thumbnail_url('medium'); ?>"/>
                  <?php endif;
                  ?>
                  <h3><?php echo get_the_title();?></h3>
                  <div class="feature_tile_content">
                     <p><?php echo get_the_excerpt();?></p>
                     <ul class="list_style_bullets">
                        <?php
                           foreach ($features as $feature) {
                              ?><li><?php echo $feature;?></li><?php
                           }
                        ?>
                     </ul>
                  </div>
                  <button><a href="<?php echo get_permalink(get_the_ID()); ?>">Details</a></button>
               </li>
            <?php
         }
         ?>
         </ul>
      </section>
      <?php

      $buffered_data = ob_get_clean();    // return buffered output
      return $buffered_data;
   }
}


new WedDevAgentPackages;