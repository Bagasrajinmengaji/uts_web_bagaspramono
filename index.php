<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUSAGRID - Cloud GPU Service</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <!-- NAVBAR -->
    <div class="navbar">

        <div class="logo">
            NUSAGRID
        </div>

        <div class="nav-menu">
            <a href="">Beranda</a>
            <a href="">Layanan</a>
            <a href="">tentang</a>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn">
                Register
            </a>
        </div>

    </div>

    <!-- HERO -->
    <section class="hero">

        <div class="hero-text">

            <h1>
                Cloud GPU NUSAGRID <br>
                Untuk Performa <span>Tanpa Batas</span>
            </h1>

            <p>
                NUSAGRID menyediakan layanan Cloud GPU
                berbasis NVIDIA untuk kebutuhan
                AI Training, Deep Learning,
                Rendering, Video Editing,
                dan Machine Learning.
            </p>

            <a href="register.php" class="btn">
                Mulai Sekarang!
            </a>

            <a href="#layanan" class="btn btn-dark">
                Lihat Layanan Yang Tersedia
            </a>

        </div>

        <div class="hero-image">
            <img src="ssets/img/RTX4090.jpg" alt="">
        </div>

    </section>

    <!-- GPU SERVICES -->
    <section id="layanan">

        <h2 class="section-title">
            GPU Yang Tersedia
        </h2>

        <div class="gpu-grid">

            <!-- CARD -->
            <div class="gpu-card">

                <img src="assets/img/RTX4090.jpg"> 

                <h3>NVIDIA RTX 4090</h3>

                <p>
                    Cocok untuk rendering,
                    AI training, dan video editing.
                </p>

                <div class="price">
                    Rp 30.000 / jam
                </div>

            </div>

            <!-- CARD -->
            <div class="gpu-card">

                <img src="https://images.nvidia.com/aem-dam/Solutions/data-center/a100/nvidia-a100-og-image.jpg">

                <h3>NVIDIA RTX 3050</h3>

                <p>
                    GPU terbaik untuk Deep Learning
                    dan Machine Learning.
                </p>

                <div class="price">
                    Rp 45.000 / jam
                </div>

            </div>

            <!-- CARD -->
            <div class="gpu-card">

                <img src="https://www.nvidia.com/content/dam/en-zz/Solutions/Data-Center/h100/images/h100-og.jpg">

                <h3>NVIDIA H100</h3>

                <p>
                    Performa tinggi untuk AI Model
                    dan komputasi berat.
                </p>

                <div class="price">
                    Rp 60.000 / jam
                </div>

            </div>

            <!-- CARD -->
            <div class="gpu-card">

                <img src="https://www.nvidia.com/content/dam/en-zz/Solutions/design-visualization/rtx-6000-ada/rtx-6000-ada-og-image.jpg">

                <h3>RTX 6000 ADA</h3>

                <p>
                    Cocok untuk desain 3D,
                    animasi, dan rendering profesional.
                </p>

                <div class="price">
                    Rp 50.000 / jam
                </div>

            </div>

        </div>

    </section>

    <!-- FOOTER -->
    <div class="footer">
        © 2026 NUSAGRID - Cloud GPU Service
    </div>

</div>

<!-- Code injected by live-server -->
<script>
	// <![CDATA[  <-- For SVG support
	if ('WebSocket' in window) {
		(function () {
			function refreshCSS() {
				var sheets = [].slice.call(document.getElementsByTagName("link"));
				var head = document.getElementsByTagName("head")[0];
				for (var i = 0; i < sheets.length; ++i) {
					var elem = sheets[i];
					var parent = elem.parentElement || head;
					parent.removeChild(elem);
					var rel = elem.rel;
					if (elem.href && typeof rel != "string" || rel.length == 0 || rel.toLowerCase() == "stylesheet") {
						var url = elem.href.replace(/(&|\?)_cacheOverride=\d+/, '');
						elem.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_cacheOverride=' + (new Date().valueOf());
					}
					parent.appendChild(elem);
				}
			}
			var protocol = window.location.protocol === 'http:' ? 'ws://' : 'wss://';
			var address = protocol + window.location.host + window.location.pathname + '/ws';
			var socket = new WebSocket(address);
			socket.onmessage = function (msg) {
				if (msg.data == 'reload') window.location.reload();
				else if (msg.data == 'refreshcss') refreshCSS();
			};
			if (sessionStorage && !sessionStorage.getItem('IsThisFirstTime_Log_From_LiveServer')) {
				console.log('Live reload enabled.');
				sessionStorage.setItem('IsThisFirstTime_Log_From_LiveServer', true);
			}
		})();
	}
	else {
		console.error('Upgrade your browser. This Browser is NOT supported WebSocket for Live-Reloading.');
	}
	// ]]>
</script>
</body>
</html>