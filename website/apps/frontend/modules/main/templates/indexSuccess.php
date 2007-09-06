<div class="content" id="homepage">
  <h1>Doctrine - Open Source PHP 5 ORM</h1>

  <p><?php echo get_partial('main/about_paragraph'); ?></p>
  
  <div id="key_features">
    <h3>Key Features</h3>
    <?php echo get_partial('main/key_features_list'); ?>
  </div>
</div>

<?php slot('right'); ?>
  <div id="download_latest_release">
    <h3>Download Latest Release</h3>
    <ul>
      <li><a href="http://doctrine.pengus.net/trac/attachment/wiki/packages/Doctrine-1.0.0RC1.tgz?format=raw">Doctrine-1.0.0RC1.tgz</a></li>
    </ul>
  </div>
  
  <?php echo get_component('blog', 'latest_blog_posts'); ?>
<?php end_slot(); ?>