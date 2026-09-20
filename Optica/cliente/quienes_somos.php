<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Óptica West | Quiénes Somos</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: url('imgclient.jpeg') no-repeat center center/cover;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .overlay {
      background-color: rgba(0, 0, 0, 0.55);
      position: fixed;
      inset: 0;
      z-index: -1;
    }

    header {
      text-align: center;
      color: #fff;
      padding: 50px 20px 20px;
    }

    header h1 {
      font-weight: 700;
      font-size: 2.5rem;
    }

    header p {
      font-size: 1.1rem;
      color: #dbe8ff;
    }

    .card-glass {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 20px;
      color: #fff;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .btn-modern {
      background-color: #00b4d8;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-weight: 600;
      transition: 0.3s;
    }

    .btn-modern:hover {
      background-color: #0096c7;
      transform: scale(1.05);
    }

   .contact-box{
  background: rgba(10, 18, 30, 0.70);  /* más oscuro */
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 18px;
  padding: 28px;
  margin-top: 35px;
  box-shadow: 0 10px 35px rgba(0,0,0,.45);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

.contact-title{
  font-weight: 700;
  color: #ffffff;
  text-align: center;
  margin-bottom: 18px;
  letter-spacing: .3px;
}

.contact-grid{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

@media (max-width: 768px){
  .contact-grid{ grid-template-columns: 1fr; }
}

.contact-item{
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 14px;
  padding: 14px 14px;
  color: #fff;
}

.contact-item .label{
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  color: #eaf3ff;
  margin-bottom: 6px;
}

.contact-item .value{
  color: rgba(255,255,255,.92);
  margin: 0;
  word-break: break-word;
}

.contact-item a{
  color: #7dd3fc;            /* celeste */
  font-weight: 700;
  text-decoration: none;
}

.contact-item a:hover{
  text-decoration: underline;
}

.contact-actions{
  margin-top: 16px;
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
}

    /* ====== Footer ====== */
    footer {
      background-color: rgba(0, 0, 0, 0.8);
      color: #f1f1f1;
      text-align: center;
      padding: 15px 10px;
      font-size: 0.9rem;
    }

    @media (max-width: 768px) {
      header h1 { font-size: 2rem; }
      .card-glass { padding: 20px; }
    }
  </style>
</head>
<body>

  <!-- Capa oscura sobre la imagen -->
  <div class="overlay"></div>

  <header>
    <h1>Óptica West</h1>
    <p>Tu visión, nuestro compromiso</p>
  </header>

  <main class="container my-5">
    <div class="card card-glass p-5 mx-auto" style="max-width: 900px;">
      <h2 class="text-center mb-4">Quiénes Somos</h2>
      <p>En <strong>Óptica West</strong>, ubicada en Ciudad Sandino, zona 11, brindamos servicios de salud visual con calidad y calidez humana. 
      Contamos con un equipo profesional experimentado y tecnología moderna para cuidar de tu vista.</p>

      <h3 class="mt-4">Misión</h3>
      <p>Brindar atención visual integral mediante servicios clínicos, ópticos y de laboratorio, priorizando el bienestar del paciente y la excelencia en cada atención.</p>

      <h3 class="mt-4">Visión</h3>
      <p>Ser una clínica óptica de referencia en la región, reconocida por su calidad, innovación y compromiso con la comunidad, mejorando la salud visual de cada familia nicaragüense.</p>
      
       

      <div class="text-center mt-4">
        <a href="index.php" class="btn btn-modern">Agendar una cita</a>
      </div>
    </div>
  </main>
       <!-- ===== CONTÁCTANOS ===== -->
    <div class="contact-box">
  <h3 class="contact-title">Contáctanos</h3>

  <div class="contact-grid">

    <div class="contact-item">
      <div class="label">📍 Dirección</div>
      <p class="value">Managua,Ciudad Sandino, Zona 11</p>
    </div>

    <div class="contact-item">
      <div class="label">📞 Teléfono</div>
      <p class="value">
        <a href="tel:+50589038434">+505 8903-8434</a><br>
        <a href="tel:+50587519763">+505 8751-9763</a>
      </p>
    </div>

    <div class="contact-item">
      <div class="label">💬 WhatsApp</div>
      <p class="value">
        <a target="_blank"
          href="https://wa.me/50587519763?text=Hola%20Óptica%20West,%20quiero%20información">
          Escríbenos por WhatsApp
        </a>
      </p>
    </div>

    <div class="contact-item">
      <div class="label">✉️ Correo</div>
      <p class="value">
        <a href="mailto:opticawest@gmail.com">opticawest@gmail.com</a>
      </p>
    </div>

  </div>

  <div class="contact-actions">
   
    <a class="btn btn-modern" target="_blank"
       href="https://maps.app.goo.gl/oGofKiYbBCQLm1U2A">
      Ver en Maps
    </a>
  </div>
</div>


  <footer>
    &copy; 2026 Óptica West | Todos los derechos reservados
  </footer>

</body>
</html>
