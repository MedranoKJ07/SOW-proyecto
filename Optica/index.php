<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio | Óptica SOW</title>

  <style>
    :root {
      --y-0: 63%;
      --x-0: 93%;
      --s-start-0: 8.392121895570533%;
      --s-end-0: 38.584065253664996%;
      --c-0: hsla(217.59493670886076, 100%, 30%, 1);
      --s-start-1: 8.392121895570533%;
      --s-end-1: 22.10878124098502%;
      --c-1: hsla(0, 0%, 0%, 1);
      --y-1: -7%;
      --x-1: 33%;
      --s-start-2: 8.392121895570533%;
      --s-end-2: 22.558651527792346%;
      --x-2: 84%;
      --c-2: hsla(0, 0%, 0%, 1);
      --y-2: 7%;
      --x-3: 14%;
      --c-3: hsla(0, 0%, 0%, 1);
      --y-3: 5%;
      --s-start-3: 8.392121895570533%;
      --s-end-3: 22.558651527792346%;
      --s-start-4: 8.392121895570533%;
      --s-end-4: 22.558651527792346%;
      --x-4: 7%;
      --y-4: 96%;
      --c-4: hsla(0, 0%, 0%, 1);
      --x-5: 93%;
      --c-5: hsla(0, 0%, 0%, 1);
      --y-5: 90%;
      --s-start-5: 8.392121895570533%;
      --s-end-5: 22.558651527792346%;
      --y-6: 61%;
      --x-6: 3%;
      --c-6: hsla(279.7894736842105, 67%, 27%, 1);
      --s-start-6: 8.392121895570533%;
      --s-end-6: 39.67138181429644%;
      --x-7: 94%;
      --y-7: 59%;
      --s-start-7: 8.392121895570533%;
      --s-end-7: 49.58090142552271%;
      --c-7: hsla(279.7894736842105, 67%, 27%, 1);
      --y-8: 63%;
      --s-start-8: 8.392121895570533%;
      --s-end-8: 34.815367581495366%;
      --c-8: hsla(217.68844221105527, 100%, 39%, 1);
      --x-8: 48%;
      --s-start-9: 8.392121895570533%;
      --s-end-9: 31.77166380372925%;
      --c-9: hsla(217.59493670886076, 100%, 30%, 1);
      --y-9: 78%;
      --x-9: 96%;
    }

    @keyframes hero-gradient-animation {
      0% { --x-0: 93%; --y-0: 63%; }
      50% { --x-0: 50%; --y-0: 50%; }
      100% { --x-0: 10%; --y-0: 70%; }
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body.animated-bg {
      height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: hsla(262, 82%, 3%, 1);
      background-image: 
        radial-gradient(circle at var(--x-0) var(--y-0), var(--c-0) var(--s-start-0), transparent var(--s-end-0)),
        radial-gradient(circle at var(--x-1) var(--y-1), var(--c-1) var(--s-start-1), transparent var(--s-end-1)),
        radial-gradient(circle at var(--x-2) var(--y-2), var(--c-2) var(--s-start-2), transparent var(--s-end-2)),
        radial-gradient(circle at var(--x-3) var(--y-3), var(--c-3) var(--s-start-3), transparent var(--s-end-3)),
        radial-gradient(circle at var(--x-4) var(--y-4), var(--c-4) var(--s-start-4), transparent var(--s-end-4)),
        radial-gradient(circle at var(--x-5) var(--y-5), var(--c-5) var(--s-start-5), transparent var(--s-end-5)),
        radial-gradient(circle at var(--x-6) var(--y-6), var(--c-6) var(--s-start-6), transparent var(--s-end-6)),
        radial-gradient(circle at var(--x-7) var(--y-7), var(--c-7) var(--s-start-7), transparent var(--s-end-7)),
        radial-gradient(circle at var(--x-8) var(--y-8), var(--c-8) var(--s-start-8), transparent var(--s-end-8)),
        radial-gradient(circle at var(--x-9) var(--y-9), var(--c-9) var(--s-start-9), transparent var(--s-end-9));
      background-blend-mode: normal;
      color: white;
      text-align: center;
      padding-top: 60px;
      animation: hero-gradient-animation 15s ease-in-out infinite alternate-reverse;
    }

    .time {
      color: white;
      margin-bottom: 40px;
    }

    .clock {
      font-size: 60px;
      font-weight: bold;
    }

    .date {
      font-size: 20px;
      margin-top: 5px;
    }

    .logo {
      max-width: 250px;
      height: auto;
      transition: transform 0.3s;
      cursor: pointer;
      margin: 30px 0;
    }

    .logo:hover {
      transform: scale(1.05);
    }

    @keyframes circle-out-hesitate {
      0% { clip-path: circle(125%); }
      40% { clip-path: circle(40%); }
      100% { clip-path: circle(0%); }
    }

    [transition-style="out:circle:hesitate"] {
      animation: circle-out-hesitate 2.5s cubic-bezier(.25, 1, .30, 1) both;
    }
    .logo-wrapper {
  width: 250px;
  height: 250px;
  border-radius: 50%;
  background-color: white;
  padding: 5px; /* grosor del borde blanco */
  display: flex;
  justify-content: center;
  align-items: center;
  box-sizing: border-box;
}
.logo-wrapper:hover {
  transform: scale(1.05);
}

.logo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
  transition: transform 0.3s;
  cursor: pointer;
}

  </style>
</head>
<body class="animated-bg">

  <!-- Hora y fecha -->
  <div class="time">
    <div class="clock" id="clock">--:--</div>
    <div class="date" id="date">Cargando fecha...</div>
  </div>


  <!-- Logo con fondo blanco circular -->
<!-- Logo con borde blanco perfectamente ajustado -->
<div class="logo-wrapper">
  <img src="imagen/logo_WEST.png" alt="Logo Óptica SOW" class="logo" id="logo">
</div>



  <script>
    // Reloj en tiempo real
    function updateClock() {
      const now = new Date();
      const hour = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      document.getElementById('clock').textContent = `${hour}:${minutes}`;

      const days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
      const months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
      const day = days[now.getDay()];
      const date = now.getDate();
      const month = months[now.getMonth()];
      const year = now.getFullYear();

      document.getElementById('date').textContent = `${day}, ${date} de ${month} de ${year}`;
    }

    setInterval(updateClock, 1000);
    updateClock();

    // Logo animación + redirección
    document.getElementById("logo").addEventListener("click", function (e) {
      e.preventDefault();
      this.setAttribute("transition-style", "out:circle:hesitate");
      setTimeout(() => {
        window.location.href = "login.php";
      }, 2500);
    });
  </script>

</body>
</html>
