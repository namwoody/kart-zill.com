<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post, $woocommerce, $product;

?>

<div id="images" class="col-md-6">
	<div>
	<?php
		if ( has_post_thumbnail() ) {
			$image_caption = get_post( get_post_thumbnail_id() )->post_excerpt;
			$image_link    = wp_get_attachment_url( get_post_thumbnail_id() );
			$image         = get_the_post_thumbnail( $post->ID, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ), 
				array('title'	=> get_the_title( get_post_thumbnail_id()
                     
				   )
			) );

		
   

       echo apply_filters( 'woocommerce_single_product_image_html', 
     	sprintf( '%s', $image), $post->ID );

		} else {

			echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<img src="%s" alt="%s" id="zoom_1" />', wc_placeholder_img_src(), __( 'Placeholder', 'woocommerce' ) ), $post->ID );

		}
	?>
	</div>

<?php do_action( 'woocommerce_product_thumbnails' ); ?>	
</div>

<script>
$(Document).ready()


</script>


