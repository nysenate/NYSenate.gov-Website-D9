(function (w, d, ns) {
  w['EmailableObject'] = ns;
  w[ns] = w[ns] || function () { (w[ns].q = w[ns].q || []).push(arguments); };
  var s = d.createElement('script'), fs = d.getElementsByTagName('script')[0];
  s.async = 1;
  s.src = 'https://js.emailable.com/v2/';
  fs.parentNode.insertBefore(s, fs);
})(window, document, 'emailable');

emailable('apiKey', 'test_ce0ea4a4ca4b0c0bbe1f');
