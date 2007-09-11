<?php use_helper('Date'); ?>

<div class="content" id="blog_post">
  <h1><?php echo $blogPost->getName(); ?></h1>
  <h3>Posted <?php echo distance_of_time_in_words(strtotime($blogPost->getCreatedAt())); ?> ago.</h3>

  <p><?php echo $blogPost->getBody(); ?></p>
</div>

<?php slot('right'); ?>
  <input type="button" name="back_to_blog" value="Back to Blog" onClick="javascript: location.href = '<?php echo url_for('@blog'); ?>';" />
  
  <br/><br/>
  
  <?php echo get_component('blog', 'latest_blog_posts'); ?>
<?php end_slot(); ?>
