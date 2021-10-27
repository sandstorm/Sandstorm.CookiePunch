document.addEventListener("DOMContentLoaded", function (event) {
  const openCookiePunchLink = document.querySelector(
    "a[href='#open_cookie_punch_modal']"
  );
  if (openCookiePunchLink) {
    openCookiePunchLink.addEventListener("click", function (event) {
      event.preventDefault();
      window.klaro.show();
    });
  }
});
