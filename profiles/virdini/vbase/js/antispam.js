(function ($, w) {

  'use strict';

  var el = $('input[name=vbase_antispam]'),
      forms = el.closest('form'),
      buttons = forms.find('input[type=submit]'),
      s, f;

  buttons.prop('disabled', true);

  forms.submit(function() {
    if (s) {
      return true;
    }
    f = $(this);
    buttons.prop('disabled', true);
    grecaptcha.execute();
    return false;
  });

  w.vBaseAntiSpamLoad = function() {
    buttons.prop('disabled', false);
  };

  w.vBaseAntiSpamSubmit = function(token) {
    el.val(token);
    s = true;
    f.submit();
  };


})(jQuery, window);
