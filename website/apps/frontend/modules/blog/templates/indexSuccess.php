<div class="content" id="blog">
  <h1>Doctrine Blog</h2>
  
  <?php foreach($latestBlogPosts AS $blogPost): ?>
    <div class="blog_post">
      <h2><?php echo link_to($blogPost->getName(), '@blog_post?slug='.$blogPost->getSlug()); ?></h2>
      
      <p><?php echo $blogPost->getBody(); ?></p>
    </div>
  <?php endforeach; ?>
</div>

<?php slot('right'); ?>
  <?php echo get_partial('latest_blog_posts', array('latestBlogPosts' => $latestBlogPosts)); ?>
<?php end_slot(); ?>