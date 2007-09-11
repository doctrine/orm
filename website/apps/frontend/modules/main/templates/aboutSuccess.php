<div class="content" id="about">
  <h1>About Doctrine</h1>
  
  <a name="what"></a>
  <div id="what_is">
    <h2>What is Doctrine?</h2>
    
    <?php echo get_partial('main/about_paragraph'); ?>
  </div>
  
  <a name="who"></a>
  <div id="who">
    <h2>Who is behind Doctrine?</h2>
    
    <p>Here is a list of the people mainly responsible for Doctrine. Many other people not on this list contribute, they can be found 
    <?php echo link_to('here', 'http://phpdoctrine.net/trac/wiki/developers'); ?>.</p>
    
    <ul>
      <li><strong>Konsta Vesterinen(zYne-)</strong> - Konsta is the project founder and lead developer of Doctrine.</li>
      <li><strong>Roman S. Borschel(romanb)</strong> - Assists in the development of Doctrine through suggestions and small code contributions.</li>
      <li><strong>Ian P. Christian(pookey)</strong> - Hosts trac and SVN. Helps with testing and occasionally writes a line of code.</li>
      <li><strong>Janne Vanhala(jepso)</strong> - The lead developer of the official Doctrine subproject Sensei. Creator of the documentation tool Doctrine uses.</li>
      <li><strong><?php echo mail_to('jonwage@gmail.com', 'Jonathan H. Wage'); ?></strong>(jwage) - Website, Documentation, Packaging Releases, Bug Fixes, etc</li>
    </ul>
  </div>
  
  <a name="involved"></a>
  <div id="get_involved">
    <h2>Want to get involved?</h2>
    
    You can contribute to the Doctrine development in many different ways. First start by <?php echo link_to('registering for trac', '@trac_register'); ?> 
    access. Once you done that you can begin submitting tickets for issues/bugs you discover while working with Doctrine. If you wish to contribute directly to the code, 
    you must request svn commit access in IRC from either pookey or <?php echo mail_to('jonwage@gmail.com', 'jwage'); ?>.
  </div>
</div>

<?php slot('right'); ?>
  
  <h3>Quickjump</h3>
  <ul>
    <li><?php echo link_to('What?', '@about#what'); ?></li>
    <li><?php echo link_to('Who?', '@about#who'); ?></li>
    <li><?php echo link_to('Get Involved!', '@about#involved'); ?></li>
  </ul>
  
  <br/>
  
  <h3>Key Features</h3>
  <?php echo get_partial('main/key_features_list'); ?>
<?php end_slot(); ?>