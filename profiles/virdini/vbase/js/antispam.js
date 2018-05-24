(function ($, w) {

  'use strict';

  var el = $('input[name=vbase_antispam]'),
      forms = el.closest('form'),
      buttons = forms.find('input[type=submit]'),
      s, f;

  buttons.prop('disabled', true);

  w.vBaseAntiSpamSubmit = function(token) {
    el.val(token);
    s = true;
    f.submit();
  };

   $.getScript('https://www.google.com/recaptcha/api.js?hl='+ $('html').attr('lang'), function() {
    buttons.prop('disabled', false);
  });

  forms.submit(function() {
    if (s) {
      return true;
    }
    f = $(this);
    buttons.prop('disabled', true);
    grecaptcha.execute();
    return false;
  });

})(jQuery, window);
