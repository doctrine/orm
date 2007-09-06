// django javascript file

// Finds all fieldsets with class="collapse", collapses them, and gives each
// one a "show" link that uncollapses it. The "show" link becomes a "hide"
// link when the fieldset is visible.

function findForm(node) {
  // returns the node of the form containing the given node
  if (node.tagName.toLowerCase() != 'form') {
    return findForm(node.parentNode);
  }
  return node;
}

var CollapsedFieldsets = {
  collapse_re: /\bcollapse\b/,   // Class of fieldsets that should be dealt with.
  collapsed_re: /\bcollapsed\b/, // Class that fieldsets get when they're hidden.
  collapsed_class: 'collapsed',
  init: function() {
    var fieldsets = document.getElementsByTagName('fieldset');
    var collapsed_seen = false;
    for (var i = 0, fs; fs = fieldsets[i]; i++) {
      // Collapse this fieldset if it has the correct class, and if it
      // doesn't have any errors. (Collapsing shouldn't apply in the case
      // of error messages.)
      if (fs.className.match(CollapsedFieldsets.collapse_re) && !CollapsedFieldsets.fieldset_has_errors(fs)) {
        collapsed_seen = true;
        // Give it an additional class, used by CSS to hide it.
        fs.className += ' ' + CollapsedFieldsets.collapsed_class;
        // (<a id="fieldsetcollapser3" class="collapse-toggle" href="#">show</a>)
        var collapse_link = document.createElement('a');
        collapse_link.className = 'collapse-toggle';
        collapse_link.id = 'fieldsetcollapser' + i;
        collapse_link.onclick = new Function('CollapsedFieldsets.show('+i+'); return false;');
        collapse_link.href = '#';
        collapse_link.innerHTML = 'show';
        var h2 = fs.getElementsByTagName('h2')[0];
        h2.appendChild(document.createTextNode(' ['));
        h2.appendChild(collapse_link);
        h2.appendChild(document.createTextNode(']'));
      }
    }
    if (collapsed_seen) {
      // Expand all collapsed fieldsets when form is submitted.
      Event.observe(findForm(document.getElementsByTagName('fieldset')[0]), 'submit', function() { CollapsedFieldsets.uncollapse_all(); }, false);
    }
  },
  fieldset_has_errors: function(fs) {
    // Returns true if any fields in the fieldset have validation errors.
    var divs = fs.getElementsByTagName('div');
    for (var i=0; i<divs.length; i++) {
      if (divs[i].className.match(/\bform-error\b/)) {
        return true;
      }
    }
    return false;
  },
  show: function(fieldset_index) {
    var fs = document.getElementsByTagName('fieldset')[fieldset_index];
    // Remove the class name that causes the "display: none".
    fs.className = fs.className.replace(CollapsedFieldsets.collapsed_re, '');
    // Toggle the "show" link to a "hide" link
    var collapse_link = document.getElementById('fieldsetcollapser' + fieldset_index);
    collapse_link.onclick = new Function('CollapsedFieldsets.hide('+fieldset_index+'); return false;');
    collapse_link.innerHTML = 'hide';
  },
  hide: function(fieldset_index) {
    var fs = document.getElementsByTagName('fieldset')[fieldset_index];
    // Add the class name that causes the "display: none".
    fs.className += ' ' + CollapsedFieldsets.collapsed_class;
    // Toggle the "hide" link to a "show" link
    var collapse_link = document.getElementById('fieldsetcollapser' + fieldset_index);
        collapse_link.onclick = new Function('CollapsedFieldsets.show('+fieldset_index+'); return false;');
    collapse_link.innerHTML = 'show';
  },
  
  uncollapse_all: function() {
    var fieldsets = document.getElementsByTagName('fieldset');
    for (var i=0; i<fieldsets.length; i++) {
      if (fieldsets[i].className.match(CollapsedFieldsets.collapsed_re)) {
        CollapsedFieldsets.show(i);
      }
    }
  }
}

Event.observe(window, 'load', CollapsedFieldsets.init, false);
