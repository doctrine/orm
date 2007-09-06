<div id="latest_blog_posts">
  <h3>Latest Blog Posts</h3>
  <ul>
    <?php foreach($latestBlogPosts AS $blogPost): ?>
      <li><?php echo link_to($blogPost->getName(), '@blog_post?slug='.$blogPost->getSlug()); ?></li>
    <?php endforeach; ?>
  </ul>
</div>