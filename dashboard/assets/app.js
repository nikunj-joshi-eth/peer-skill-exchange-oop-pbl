'use strict';
document.querySelectorAll('.delete-form').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm('Delete this record? Referenced records must be removed first.')) event.preventDefault();
  });
});
