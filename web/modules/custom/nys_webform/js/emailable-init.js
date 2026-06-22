(function (w, d, ns) {
  w['EmailableObject'] = ns;
  w[ns] = w[ns] || function () { (w[ns].q = w[ns].q || []).push(arguments); };
  var s = d.createElement('script'), fs = d.getElementsByTagName('script')[0];
  s.async = 1;
  s.src = 'https://js.emailable.com/v2/';
  fs.parentNode.insertBefore(s, fs);
})(window, document, 'emailable');

emailable('apiKey', 'live_014dfc05ff9400973f7c');

// Only allow fully deliverable addresses.
emailable('states', ['deliverable']);

// Allow free providers (Gmail, Yahoo etc) — constituents commonly use these.
emailable('free', true);

// Allow role addresses (info@, contact@ etc).
emailable('role', true);

// Allow disposable addresses — Apple Hide My Email and similar legitimate
// privacy services would otherwise be incorrectly blocked.
emailable('disposable', true);

// User-facing messages.
emailable('messages', {
  verifying:   'Please wait while we verify your email address.',
  invalid:     'It looks like you\'ve entered an invalid email address.',
  role:        'It looks like you\'ve entered a role or group email address.',
  free:        'It looks like you\'ve entered a free email address.',
  disposable:  'Please use a permanent email address, not a disposable one.',
  didYouMean:  'Did you mean [EMAIL]?',
  rateLimited: 'Too many attempts. Please try again shortly.',
});

// Temporary debug listeners — remove once Live key is confirmed working.
document.addEventListener('verified', function (e) {
  if (e.target && e.target.type === 'email') {
    console.log('[emailable] verified', e.detail);
  }
}, true);

document.addEventListener('error', function (e) {
  if (e.target && e.target.type === 'email') {
    console.log('[emailable] error', e.detail);
  }
}, true);
