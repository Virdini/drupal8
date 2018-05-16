(function () {

  'use strict';

  var i, s, m, elements = document.querySelectorAll('.mm, .vml-h');

  if (elements && elements.length > 0) {
    for (i = 0; i < elements.length; i++) {
      elements[i].addEventListener('click', function() {
        s = this.getAttribute('data-s') == 'o';
        this.setAttribute('data-s', s ? 'c' : 'o');
        if (!(m = this.parentElement.querySelector('.vm-0'))) {
          m = this.parentElement.parentElement.querySelector('ul');
        }
        if (m) {
          m.setAttribute('data-s', s ? 'c' : 'o');
        }
      });
    }
  }

})();
