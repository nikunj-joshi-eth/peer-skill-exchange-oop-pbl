'use strict';
document.querySelectorAll('.delete-form').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm(`Delete ${form.dataset.record}? This cannot be undone. Referenced records must be removed first.`)) event.preventDefault();
  });
});

// Focus server validation feedback after redirect.
const errorNotice = document.querySelector('.notice.error');
if (errorNotice) errorNotice.focus();
