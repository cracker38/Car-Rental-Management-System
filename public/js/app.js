document.addEventListener('submit', function (e) {
  var form = e.target;
  if (form.matches('[data-confirm]')) {
    if (!window.confirm(form.getAttribute('data-confirm'))) {
      e.preventDefault();
    }
  }
});

document.querySelectorAll('input[type="datetime-local"][data-min-now]').forEach(function (el) {
  if (!el.value) {
    var n = new Date();
    n.setMinutes(n.getMinutes() - n.getTimezoneOffset());
    el.min = n.toISOString().slice(0, 16);
  }
});
