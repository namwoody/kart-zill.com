

<div class="top-bar">
  <div class="row">
   <div class="col-md-8">
      
   

   </div>      
 

    <div class="col-md-4 pull-right">
      <ul id="top-menu">
   <?php  wp_list_pages('title_li=&exclude=105,103');?>
   <ul>
    </div>
      
   
  </div>


</div>
<header class="banner">
  <div class="container">
   <div class="row">
  <div class="col-md-4" id="logo">
    <a class="brand" href="<?= esc_url(home_url('/')); ?>">

<img src="<?php bloginfo('template_directory'); ?>/assets/images/logo.png" width="210px" alt="Kartzill Logo" />     
     

    </a>
</div><!-- end col-md-4 -->


   <div class="col-md-8 text-right" id="top-navigation">




  
  <a class="cart-contents" href="<?php echo WC()->cart->get_cart_url(); ?>" title="<?php _e( 'View your shopping cart' ); ?>">
  <?php echo sprintf (_n( '%d item', '%d items', WC()->cart->get_cart_contents_count() ), WC()->cart->get_cart_contents_count() ); ?> </a>
    
<a href="<?php echo WC()->cart->get_cart_url(); ?>">
<i class="fa fa-shopping-cart"></i> 
       View Cart</a>
   
 


       </li>
      

   </div>

  
 

</div>

  </div>
</header>


<div class="nav-bar">
  <div class="container" id="nav-bar">
    <div class="col-md-8">



</div>

<div class="col-md-4" id="search_form">

<?php get_product_search_form() ;?>
</div>






  </div>

</div>















