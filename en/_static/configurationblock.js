$(document).ready(function(){
  $('div.configuration-block [class^=highlight-]').hide();
  $('div.configuration-block [class^=highlight-]').width($('div.configuration-block').width());

  $('div.configuration-block').addClass('jsactive');
  $('div.configuration-block').addClass('clearfix');

  $('div.configuration-block').each(function (){
      var el = $('[class^=highlight-]:first', $(this));
      el.show();
      el.parents('ul').height(el.height() + 40);
  });

  // Global
  $('div.configuration-block li').each(function(){
    var str = $(':first', $(this)).html();
    $(':first ', $(this)).html('');
    $(':first ', $(this)).append('<a href="#">' + str + '</a>')
    $(':first', $(this)).bind('click', function(){
      $('[class^=highlight-]', $(this).parents('ul')).hide();
      $('li', $(this).parents('ul')).removeClass('selected');
      $(this).parent().addClass('selected');

      var block = $('[class^=highlight-]', $(this).parent('li'));
      block.show();
      block.parents('ul').height(block.height() + 40);
      return false;
    });
  });

  $('div.configuration-block').each(function (){
      $('li:first', $(this)).addClass('selected');
  });
});
