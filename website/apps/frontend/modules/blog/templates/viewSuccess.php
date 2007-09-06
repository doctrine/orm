<h1><?php echo $blogPost->getName(); ?></h1>

<p><?php echo $blogPost->getBody(); ?></p>

<?php slot('right'); ?>
  <input type="button" name="back_to_blog" value="Back to Blog" onClick="javascript: location.href = '<?php echo url_for('@blog'); ?>';" />
  
  <br/><br/>
  
  <?php echo get_component('blog', 'latest_blog_posts'); ?>
<?php end_slot(); ?>
