<footer>
  <div class="container">
    <strong style="color:#fff">Swift<span style="color:#10b981">Swap</span></strong>
    &nbsp;·&nbsp; &copy; <span id="year"></span>
    &nbsp;·&nbsp; <a href="#">Terms</a> &nbsp;·&nbsp; <a href="#">Privacy</a>
  </div>
</footer>
<script>document.getElementById('year').textContent = new Date().getFullYear();</script>
<script type="module">
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.14.0/firebase-app.js";
  import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.14.0/firebase-analytics.js";

  const firebaseConfig = {
    apiKey: "AIzaSyAM2rgqrM0esvV9YPWqKpnJhJWhKAxqYMs",
    authDomain: "swiftswap-83394.firebaseapp.com",
    projectId: "swiftswap-83394",
    storageBucket: "swiftswap-83394.firebasestorage.app",
    messagingSenderId: "149861453455",
    appId: "1:149861453455:web:85650026880b4de71eeafa",
    measurementId: "G-KX2XJ0M8PT"
  };

  window.firebaseApp = initializeApp(firebaseConfig);
  window.firebaseAnalytics = getAnalytics(window.firebaseApp);
</script>
