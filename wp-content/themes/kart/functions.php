<?php
/**
 * Sage includes
 *
 * The $sage_includes array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 *
 * Please note that missing files will produce a fatal error.
 *
 * @link https://github.com/roots/sage/pull/1042
 */
$sage_includes = [
  'lib/assets.php',    // Scripts and stylesheets
  'lib/extras.php',    // Custom functions
  'lib/setup.php',     // Theme setup
  'lib/titles.php',    // Page titles
  'lib/wrapper.php',   // Theme wrapper class
  'lib/customizer.php' // Theme customizer
  
  ];


// Ensure cart contents update when products are added to the cart via AJAX (place the following in functions.php)
add_filter( 'woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment' );
function woocommerce_header_add_to_cart_fragment( $fragments ) {
  ob_start();
  ?>
  <a class="cart-contents" href="<?php echo WC()->cart->get_cart_url(); ?>" title="<?php _e( 'View your shopping cart' ); ?>"><?php echo sprintf (_n( '%d item', '%d items', WC()->cart->get_cart_contents_count() ), WC()->cart->get_cart_contents_count() ); ?> </a> 
  <?php
  
  $fragments['a.cart-contents'] = ob_get_clean();
  
  return $fragments;
}

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

remove_action('woocommerce_before_shop_loop','woocommerce_result_count',20);
remove_action('woocommerce_before_shop_loop','woocommerce_catalog_ordering',30);


add_action('woocommerce_before_main_content','sage_wrapper_start',10);
add_action('woocommerce_after_main_content','sage_wrapper_end',10);
add_action('woocommerce_sidebar','sage_get_sidebar',10);




 
 function sage_wrapper_start() {
    echo "<div class='container'>";

 }
function sage_wrapper_end() {
    echo "<div>";
}

// Function create a new thumnails
// 

 

 remove_action( 'woocommerce_after_shop_loop_item','woocommerce_template_loop_add_to_cart',10 );

 add_action('woocommerce_after_shop_loop_item','sage_template_add_to_cart',10);

// front product cart button   

 
if ( ! function_exists( 'sage_template_add_to_cart' ) ) {


  function sage_template_add_to_cart( $args = array() ) {
    global $product;

    if ( $product ) {
      $defaults = array(
        'quantity' => 1,

        'class'    => implode( ' ', array_filter( array(
            'btn btn-warning',
            'product_type_' . $product->product_type,
            $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
            $product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : ''
        ) ) )
      );

      $args = apply_filters( 'woocommerce_loop_add_to_cart_args', wp_parse_args( $args, $defaults ), $product );

      wc_get_template( 'loop/add-to-cart.php', $args );
    }
  }
}









///


remove_action( 'woocommerce_after_shop_loop_item_title','woocommerce_template_loop_rating',5 );


//add_action('woocommerce_after_shop_loop_item_title','woocommerce_template_single_excerpt', 5);


  add_filter( 'woocommerce_template_single_excerpt', 'woo_custom_excerpt_length', 10 );
 
    function woo_custom_excerpt_length ( $length ) {
        $length = 3;
        return $length;
    } 

// Function create a sidebar   






// Function product anchor tag  //
remove_action( 'woocommerce_before_shop_loop_item','woocommerce_template_loop_product_link_open',10 );



 

remove_action( 'woocommerce_shop_loop_item_title','woocommerce_template_loop_product_title',10 );

add_action('woocommerce_shop_loop_item_title','sage_template_product_title',10);

if (  ! function_exists( 'sage_template_product_title' ) ) {

  /**
   * Show the product title in the product loop. By default this is an H3.
   */
  function sage_template_product_title() {

    echo '<a href="' . get_the_permalink() . '">';
    echo '<h5>' . get_the_title() . '</h5>';
    echo '</a>';

    
  }
}

remove_action('woocommerce_after_shop_loop_item','woocommerce_template_loop_product_link_close',5);


// complete product open tag 

// woocommerce customer login information details  //





// woocommerce end customer login information //

// start breadcrum     //

remove_action('woocommerce_before_main_content','woocommerce_breadcrumb',20);

add_action('woocommerce_before_main_content','sage_breadcrumb',20);

if ( ! function_exists( 'sage_breadcrumb' ) ) {

  /**
   * Output the WooCommerce Breadcrumb.
   *
   * @param array $args
   */
  function sage_breadcrumb( $args = array() ) {
    $args = wp_parse_args( $args, apply_filters( 'woocommerce_breadcrumb_defaults', array(
      'delimiter'   => '&nbsp;<em>>></em>&nbsp;',
      'wrap_before' => '<i class"fa fa-shopping-cart"></i><ol class="breadcrumb" ' . ( is_single() ? 'itemprop="breadcrumb"' : '' ) . '>',
      'wrap_after'  => '</ol>',
      'before'      => '',
      'icon'        =>'',
      'after'       => '',
      'home'        => _x( 'Home', 'breadcrumb', 'woocommerce' )
    ) ) );

    $breadcrumbs = new WC_Breadcrumb();

    if ( $args['home'] ) {
      $breadcrumbs->add_crumb( $args['home'], apply_filters( 'woocommerce_breadcrumb_home_url', home_url() ) );
    }

    $args['breadcrumb'] = $breadcrumbs->generate();

    wc_get_template( 'global/breadcrumb.php', $args );
  }
}








// end breadcrumb ---- //

// start product thumbnail setting -- //


remove_action('woocommerce_before_shop_loop_item_title','woocommerce_template_loop_product_thumbnail',10);
add_action('woocommerce_before_shop_loop_item_title','sage_product_thumbnail',10);

if ( ! function_exists( 'sage_product_thumbnail') ) {

 function sage_product_thumbnail(){

   echo '<a href="' . get_the_permalink() . '">';
echo woocommerce_get_product_thumbnail();
echo '</a>';

 }




}


//  start account form //



// Hook in
// Hook in
// add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
// // Our hooked in function - $fields is passed via the filter!
// function custom_override_checkout_fields( $fields ) {

//      $fields['billing']['billing_first_name']['class'] = array('form-control');
    
//      return $fields;
   
// }






// end account form edit -- //




// end product thumbnail setting -- //

// start product image filter
// define the woocommerce_single_product_image_html callback 
function filter_woocommerce_single_product_image_html( $sprintf, $post_id ) { 
    // make filter magic happen here... 
    return $sprintf; 
}; 
         
// add the filter 
add_filter( 'woocommerce_single_product_image_html', 'filter_woocommerce_single_product_image_html', 10, 2 );












foreach ($sage_includes as $file) {
  if (!$filepath = locate_template($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'sage'), $file), E_USER_ERROR);
  }

  require_once $filepath;
}
unset($file, $filepath);
