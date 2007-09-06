<div class="content" id="about">
  <h1>About Doctrine</h1>
  
  <div id="what_is">
    <h2>What is Doctrine?</h2>
    
    <?php echo get_partial('main/about_paragraph'); ?>
  </div>
  
  <div id="who">
    <h2>Who is behind Doctrine?</h2>
  </div>
  
  <div id="get_involved">
    <h2>Want to get involved?</h2>
  </div>
</div>

<?php slot('right'); ?>
  <h3>Key Features</h3>
  <?php echo get_partial('main/key_features_list'); ?>
<?php end_slot(); ?>