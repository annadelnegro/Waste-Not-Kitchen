document.addEventListener('DOMContentLoaded', () => {
  // Confirm sign out
  const signout = document.querySelector('.signout-btn');
  if (signout) {
    signout.addEventListener('click', (e) => {
      if (!confirm('Are you sure you want to sign out?')) {
        e.preventDefault();
      }
    });
  }
  // edit-field actions handled by profile-edit.js
});
