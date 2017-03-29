(function () {

  'use strict';

  var select = document.getElementById('edit-logo'),
      img = document.getElementById('developers-settings-logo'),
      url = img.src.substring(0, img.src.lastIndexOf('/') + 1);

  show_logo(select);

  select.addEventListener('change', function() {
    show_logo(this);
  });

  function show_logo(el) {
    img.src = url + el.value;
  }

})();
