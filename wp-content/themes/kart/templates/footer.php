






<!-- company info subscriber -->
<div class="container">
	<div class="row">
		<div class="col-md-4" id="newsletter">
			
      <h2>Subscriber</h2>
      <?php
     $widgetNL = new WYSIJA_NL_Widget(true);
echo $widgetNL->widget(array('form' => 1, 'form_type' => 'php'));


      ?>

		</div>					

     		<div class="col-md-8" id="banner_images">
			
         <img src="<?php bloginfo('template_directory'); ?>/assets/images/banner.jpg" alt="banner" >

		</div>		


	</div>


</div>






<!-- company end subscriber  -->







<footer class="content-info" id="footer_wrapper">

  <div class="container">



<div class="row" id="payment_card_images">
	<p>We Accept All Major Credit Cards </p>
	<img src="<?php bloginfo('template_directory');  ?>/assets/images/creditcard.png" />
   <p>&copy; 2016 Kartzill | online selling Goods. All Rights Reserved.</p>
</div>


  </div>



</footer>

<script>
    $('#zoom_01').elevateZoom({
    zoomType: "inner",
cursor: "crosshair",
zoomWindowFadeIn: 500,
zoomWindowFadeOut: 750
   }); 
</script>





<script>document.write('<script src="http://' + (location.host || 'localhost').split(':')[0] + ':35729/livereload.js?snipver=1"></' + 'script>')</script>




<!-- <script type="text/javascript" src="https://cdn.jsdelivr.net/elevatezoom/3.0.8/jqueryElevateZoom.js"></script> -->

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
