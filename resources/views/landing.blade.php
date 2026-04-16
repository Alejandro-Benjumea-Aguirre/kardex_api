<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kardex CO — API REST</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      color: #2c3e50;
      background: #f8fafc;
    }

    /* ── HEADER ── */
    header {
      background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
      border-bottom: 1px solid rgba(23,35,47,.1);
      padding: 1.5rem 2rem;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo {
      display: flex;
      align-items: center;
      gap: .75rem;
      font-size: 1.5rem;
      font-weight: 700;
      background: linear-gradient(135deg,#3b82f6 0%,#10b981 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      cursor: pointer;
      text-decoration: none;
    }
    .logo-icon {
      width: 36px; height: 36px;
      background: linear-gradient(135deg,#3b82f6 0%,#10b981 100%);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; color: white; font-weight: 700;
      flex-shrink: 0;
      -webkit-text-fill-color: white;
    }
    nav { display: flex; gap: 2rem; align-items: center; }
    nav a {
      text-decoration: none; color: #64748b;
      font-weight: 500; font-size: .95rem;
      transition: color .3s;
    }
    nav a:hover { color: #3b82f6; }
    /* badge version */
    .version-badge {
      background: linear-gradient(135deg,#3b82f6 0%,#10b981 100%);
      color: white; padding: .35rem 1rem;
      border-radius: 20px; font-size: .8rem; font-weight: 700;
      letter-spacing: .05em;
    }

    /* theme toggle */
    .theme-toggle {
      background: none; border: 2px solid #e2e8f0;
      border-radius: 50%; width: 38px; height: 38px;
      cursor: pointer; font-size: 1.1rem;
      display: flex; align-items: center; justify-content: center;
      transition: border-color .3s, background .3s;
    }
    .theme-toggle:hover { border-color: #3b82f6; background: #f0f9ff; }

    /* ── HERO ── */
    .hero {
      background: linear-gradient(135deg,#f0f9ff 0%,#f0fdf4 100%);
      padding: 4rem 2rem;
      display: flex; align-items: center;
      min-height: calc(100vh - 80px);
    }
    .hero-content {
      max-width: 1200px; margin: 0 auto; width: 100%;
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 4rem; align-items: center;
    }
    .hero-text h1 {
      font-size: 3rem; font-weight: 800; line-height: 1.2;
      margin-bottom: 1rem;
      background: linear-gradient(135deg,#1e293b 0%,#3b82f6 50%,#10b981 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: fadeInUp .8s ease forwards;
    }
    .hero-text p {
      font-size: 1.25rem; color: #64748b;
      margin-bottom: 2rem; line-height: 1.8;
      animation: fadeInUp .8s ease .2s forwards; opacity: 0;
    }
    /* base url box */
    .base-url-box {
      display: inline-flex; align-items: center; gap: .75rem;
      background: #1e293b; border-radius: 10px;
      padding: .75rem 1.25rem;
      animation: fadeInUp .8s ease .4s forwards; opacity: 0;
      max-width: 100%;
      overflow-x: auto;
    }
    .base-url-label {
      color: #10b981; font-size: .8rem; font-weight: 700;
      font-family: monospace; white-space: nowrap;
    }
    .base-url-value {
      color: #e2e8f0; font-family: monospace; font-size: .95rem;
      white-space: nowrap;
    }
    html.dark .base-url-box { background: #0f172a; border: 1px solid #334155; }

    /* hero visual */
    .hero-visual {
      display: flex; justify-content: center;
      animation: float 3s ease-in-out infinite;
    }
    .illustration-box {
      width: 300px; height: 300px;
      background: linear-gradient(135deg,#dbeafe 0%,#dcfce7 100%);
      border-radius: 20px;
      display: flex; align-items: flex-end; justify-content: center;
      box-shadow: 0 20px 40px rgba(59,130,246,.15);
      padding: 2rem; gap: .75rem;
    }
    .bar {
      flex: 1; border-radius: 8px;
      background: linear-gradient(135deg,#3b82f6 0%,#10b981 100%);
      animation: growBar 1.5s ease forwards; opacity: 0;
    }
    .bar:nth-child(1) { height: 40%; animation-delay: 1.1s; }
    .bar:nth-child(2) { height: 60%; animation-delay: 1.2s; }
    .bar:nth-child(3) { height: 80%; animation-delay: 1.3s; }
    .bar:nth-child(4) { height: 50%; animation-delay: 1.4s; }

    @keyframes growBar {
      from { transform: scaleY(0); transform-origin: bottom; opacity: 0; }
      to   { transform: scaleY(1); transform-origin: bottom; opacity: 1; }
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes float {
      0%,100% { transform: translateY(0); }
      50%     { transform: translateY(-20px); }
    }

    /* ── FEATURES ── */
    .features { background: white; padding: 5rem 2rem; text-align: center; }
    .features-container { max-width: 1200px; margin: 0 auto; }
    .section-title {
      font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;
      background: linear-gradient(135deg,#1e293b 0%,#3b82f6 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .section-subtitle { font-size: 1.2rem; color: #64748b; margin-bottom: 3rem; }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
    }
    .feature-card {
      background: linear-gradient(135deg,#f8fafc 0%,#f0f9ff 100%);
      padding: 2rem; border-radius: 16px;
      border: 1px solid rgba(59,130,246,.1);
      transition: all .3s; text-align: left;
    }
    .feature-card:hover {
      transform: translateY(-8px);
      border-color: #3b82f6;
      box-shadow: 0 16px 32px rgba(59,130,246,.15);
    }
    .feature-icon {
      width: 60px; height: 60px;
      background: linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.8rem; margin-bottom: 1.5rem;
    }
    .feature-card h3 { font-size: 1.3rem; margin-bottom: .75rem; color: #1e293b; }
    .feature-card p  { color: #64748b; line-height: 1.7; }

    /* ── ENDPOINTS ── */
    .endpoints-section { background: white; padding: 5rem 2rem; }
    .endpoints-container { max-width: 1200px; margin: 0 auto; }
    .endpoints-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem; margin-top: 3rem;
    }
    .endpoint-card {
      background: #f8fafc; border-radius: 12px;
      border: 1px solid #e2e8f0; padding: 1.25rem 1.5rem;
      transition: all .3s;
    }
    .endpoint-card:hover { border-color: #3b82f6; box-shadow: 0 8px 20px rgba(59,130,246,.1); }
    .endpoint-header { display: flex; align-items: center; gap: .75rem; margin-bottom: .6rem; }
    .method {
      font-size: .7rem; font-weight: 800; padding: .25rem .6rem;
      border-radius: 6px; font-family: monospace; white-space: nowrap;
    }
    .method.get    { background: #dcfce7; color: #15803d; }
    .method.post   { background: #dbeafe; color: #1d4ed8; }
    .method.put    { background: #fef9c3; color: #a16207; }
    .method.delete { background: #fee2e2; color: #b91c1c; }
    .endpoint-path {
      font-family: monospace; font-size: .9rem;
      color: #1e293b; font-weight: 600;
    }
    .endpoint-desc { font-size: .88rem; color: #64748b; line-height: 1.5; }
    html.dark .endpoints-section   { background: #0f172a; }
    html.dark .endpoint-card       { background: #1e293b; border-color: #334155; }
    html.dark .endpoint-card:hover { border-color: #3b82f6; }
    html.dark .endpoint-path       { color: #e2e8f0; }

    /* ── INFO ── */
    .info-section {
      background: linear-gradient(135deg,#3b82f6 0%,#10b981 100%);
      padding: 4rem 2rem; text-align: center; color: white;
    }
    .info-content { max-width: 800px; margin: 0 auto; }
    .info-section h2 { font-size: 2rem; font-weight: 800; margin-bottom: 1rem; }
    .info-section p  { font-size: 1.1rem; opacity: .95; line-height: 1.8; margin-bottom: 1rem; }
    .info-pills { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: center; margin-top: 1.5rem; }
    .pill {
      background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.4);
      border-radius: 20px; padding: .4rem 1rem;
      font-size: .85rem; font-weight: 600;
    }

    /* ── CONTACTO ── */
    .contact-section { background: white; padding: 5rem 2rem; text-align: center; }
    .contact-container { max-width: 600px; margin: 0 auto; }
    .contact-avatar {
      width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 1.5rem;
      background: linear-gradient(135deg,#3b82f6 0%,#10b981 100%);
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem; color: white; font-weight: 800;
    }
    .contact-name {
      font-size: 1.6rem; font-weight: 800; color: #1e293b; margin-bottom: .25rem;
    }
    .contact-role { font-size: 1rem; color: #64748b; margin-bottom: 2rem; }
    .contact-links { display: flex; justify-content: center; flex-wrap: wrap; gap: 1rem; }
    .contact-link {
      display: inline-flex; align-items: center; gap: .5rem;
      text-decoration: none; padding: .7rem 1.4rem; border-radius: 10px;
      font-weight: 600; font-size: .9rem; transition: all .3s;
      border: 2px solid transparent;
    }
    .contact-link.email {
      background: #f0f9ff; color: #2563eb; border-color: #bfdbfe;
    }
    .contact-link.email:hover { background: #dbeafe; transform: translateY(-2px); }
    .contact-link.github {
      background: #f8fafc; color: #1e293b; border-color: #e2e8f0;
    }
    .contact-link.github:hover { background: #e2e8f0; transform: translateY(-2px); }
    .contact-link.linkedin {
      background: #eff6ff; color: #0a66c2; border-color: #bfdbfe;
    }
    .contact-link.linkedin:hover { background: #dbeafe; transform: translateY(-2px); }
    .contact-link.website {
      background: #f0fdf4; color: #15803d; border-color: #bbf7d0;
    }
    .contact-link.website:hover { background: #dcfce7; transform: translateY(-2px); }
    .contact-link svg { width: 18px; height: 18px; flex-shrink: 0; }

    html.dark .contact-section  { background: #0f172a; }
    html.dark .contact-name     { color: #f1f5f9; }
    html.dark .contact-role     { color: #94a3b8; }
    html.dark .contact-link.email   { background: #1e293b; color: #60a5fa; border-color: #1d4ed8; }
    html.dark .contact-link.email:hover { background: #1e3a5f; }
    html.dark .contact-link.github  { background: #1e293b; color: #e2e8f0; border-color: #334155; }
    html.dark .contact-link.github:hover { background: #334155; }
    html.dark .contact-link.linkedin{ background: #1e293b; color: #60a5fa; border-color: #1d4ed8; }
    html.dark .contact-link.linkedin:hover { background: #1e3a5f; }
    html.dark .contact-link.website { background: #1e293b; color: #4ade80; border-color: #166534; }
    html.dark .contact-link.website:hover { background: #14532d; }

    /* ── FOOTER ── */
    footer { background: #1e293b; color: #94a3b8; padding: 3rem 2rem; }
    .footer-content {
      max-width: 1200px; margin: 0 auto 2rem;
      display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
      gap: 2rem; text-align: left;
    }
    .footer-section h4 { color: white; margin-bottom: 1rem; font-weight: 600; }
    .footer-section a {
      color: #94a3b8; text-decoration: none;
      display: block; margin-bottom: .5rem;
      transition: color .3s;
    }
    .footer-section a:hover { color: #3b82f6; }
    .footer-bottom { border-top: 1px solid #334155; padding-top: 2rem; text-align: center; font-size: .9rem; }

    /* ── DARK MODE ── */
    html.dark body       { color: #e2e8f0; background: #0f172a; }
    html.dark header     { background: #17232f; border-bottom-color: rgba(59,130,246,.15); box-shadow: 0 2px 8px rgba(0,0,0,.3); }
    html.dark nav a      { color: #94a3b8; }
    html.dark nav a:hover{ color: #60a5fa; }
    html.dark .theme-toggle { border-color: #334155; }
    html.dark .theme-toggle:hover { border-color: #3b82f6; background: #1e293b; }
    html.dark .hero      { background: linear-gradient(135deg,#0f172a 0%,#0c1a30 100%); }
    html.dark .hero-text h1 {
      background: linear-gradient(135deg,#e2e8f0 0%,#60a5fa 50%,#34d399 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    html.dark .hero-text p  { color: #94a3b8; }
    html.dark .illustration-box { background: linear-gradient(135deg,#1e3a5f 0%,#064e3b 100%); box-shadow: 0 20px 40px rgba(59,130,246,.1); }
    html.dark .features    { background: #0f172a; }
    html.dark .section-title {
      background: linear-gradient(135deg,#e2e8f0 0%,#60a5fa 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    html.dark .section-subtitle { color: #94a3b8; }
    html.dark .feature-card { background: linear-gradient(135deg,#1e293b 0%,#1a2540 100%); border-color: rgba(59,130,246,.15); }
    html.dark .feature-card:hover { border-color: #3b82f6; box-shadow: 0 16px 32px rgba(59,130,246,.1); }
    html.dark .feature-card h3 { color: #f1f5f9; }
    html.dark .feature-card p  { color: #94a3b8; }
    html.dark footer       { background: #070d1a; }

    /* ── ENDPOINT GROUPS ── */
    .endpoint-group { margin-top: 2.5rem; }
    .endpoint-group:first-child { margin-top: 0; }
    .endpoint-group-title {
      font-size: .75rem; font-weight: 800; letter-spacing: .1em;
      text-transform: uppercase; color: #3b82f6;
      margin-bottom: 1rem; padding-bottom: .5rem;
      border-bottom: 2px solid #e2e8f0;
      display: flex; align-items: center; gap: .5rem;
    }
    html.dark .endpoint-group-title { color: #60a5fa; border-bottom-color: #334155; }

    .new-badge {
      background: linear-gradient(135deg,#10b981 0%,#059669 100%);
      color: white; font-size: .65rem; font-weight: 800;
      padding: .15rem .5rem; border-radius: 20px;
      letter-spacing: .05em; text-transform: uppercase;
      flex-shrink: 0;
    }
    .endpoint-permission {
      display: inline-block; margin-top: .4rem;
      font-size: .72rem; color: #94a3b8; font-family: monospace;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
      nav a { display: none; }
      .hero-content { grid-template-columns: 1fr; gap: 2rem; }
      .hero-text h1 { font-size: 2rem; }
      .hero-text p  { font-size: 1rem; }
      .section-title  { font-size: 1.8rem; }
      .illustration-box { width: 250px; height: 250px; }
      .hero     { padding: 2rem 1rem; min-height: auto; }
      .features { padding: 3rem 1rem; }
      .info-section { padding: 2.5rem 1rem; }
      .footer-content { grid-template-columns: 1fr; text-align: center; }
      .footer-section a { display: inline; margin: 0 .5rem; }
    }
    @media (max-width: 480px) {
      header { padding: 1rem; }
      .header-content { flex-direction: column; gap: 1rem; }
      .hero-text h1  { font-size: 1.5rem; }
      .section-title { font-size: 1.5rem; }
      .info-section h2{ font-size: 1.5rem; }
      .info-section p { font-size: 1rem; }
      .features-grid { grid-template-columns: 1fr; }
      .endpoints-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- ══ HEADER ══ -->
<header>
  <div class="header-content">
    <a class="logo" href="#">
      <span class="logo-icon">K</span>
      Kardex CO
    </a>
    <nav>
      <a href="#recursos">Recursos</a>
      <a href="#endpoints">Endpoints</a>
      <a href="#autenticacion">Autenticación</a>
      <a href="#contacto">Contacto</a>
      <button class="theme-toggle" id="themeToggle" title="Cambiar tema">🌙</button>
      <span class="version-badge">v1.0</span>
    </nav>
  </div>
</header>

<!-- ══ HERO ══ -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-text">
      <h1>Kardex CO — API REST</h1>
      <p>API para la gestión de inventario, ventas, compras y flujo de caja. Construida con Laravel 11 y autenticación JWT.</p>
      <div class="base-url-box">
        <span class="base-url-label">BASE URL</span>
        <span class="base-url-value">{{ url('/api') }}</span>
      </div>
    </div>
    <div class="hero-visual">
      <div class="illustration-box">
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
      </div>
    </div>
  </div>
</section>

<!-- ══ RECURSOS ══ -->
<section class="features" id="recursos">
  <div class="features-container">
    <h2 class="section-title">Recursos disponibles</h2>
    <p class="section-subtitle">Módulos que expone la API</p>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">📦</div>
        <h3>Inventario</h3>
        <p>Gestión completa de productos y existencias. Consulta stock, registra entradas y salidas, y controla movimientos en tiempo real.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🛒</div>
        <h3>Ventas &amp; Compras</h3>
        <p>Registro de órdenes de venta y compra, gestión de proveedores, clientes y detalles de cada transacción.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💰</div>
        <h3>Flujo de Caja</h3>
        <p>Control de ingresos y egresos. Visualiza el balance financiero y genera reportes por período.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔐</div>
        <h3>Autenticación JWT</h3>
        <p>Todos los endpoints protegidos requieren un token Bearer. Obtén tu token mediante <code style="font-size:.85rem;background:#e2e8f0;padding:.1rem .35rem;border-radius:4px">POST /api/auth/login</code>.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">👤</div>
        <h3>Usuarios</h3>
        <p>Administración de cuentas de usuario: consulta de perfil, actualización de datos y gestión de roles.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🏢</div>
        <h3>Sucursales</h3>
        <p>Gestión de sucursales por empresa: creación, configuración de horarios, datos de contacto y control de estado.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h3>Reportes</h3>
        <p>Endpoints dedicados para la generación de reportes filtrados por fecha, categoría o tipo de movimiento.</p>
      </div>
    </div>
  </div>
</section>

<!-- ══ ENDPOINTS ══ -->
<section class="endpoints-section" id="endpoints">
  <div class="endpoints-container">
    <h2 class="section-title" style="text-align:center">Endpoints principales</h2>
    <p class="section-subtitle" style="text-align:center">Referencia rápida de las rutas disponibles</p>
    <!-- ── Autenticación ── -->
    <div class="endpoint-group">
      <div class="endpoint-group-title">🔐 Autenticación</div>
      <div class="endpoints-grid">

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/auth/login</span>
          </div>
          <p class="endpoint-desc">Autentica un usuario y devuelve un token JWT Bearer.</p>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/auth/register</span>
          </div>
          <p class="endpoint-desc">Registra un nuevo usuario junto con su empresa.</p>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/auth/refresh</span>
          </div>
          <p class="endpoint-desc">Genera un nuevo token JWT a partir de uno próximo a expirar.</p>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/auth/me</span>
          </div>
          <p class="endpoint-desc">Devuelve la información del usuario autenticado actualmente.</p>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/auth/forgot-password</span>
          </div>
          <p class="endpoint-desc">Envía un correo para restablecer la contraseña.</p>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/auth/logout</span>
          </div>
          <p class="endpoint-desc">Invalida el token JWT activo del usuario autenticado.</p>
        </div>

      </div>
    </div>

    <!-- ── Usuarios ── -->
    <div class="endpoint-group">
      <div class="endpoint-group-title">👤 Usuarios</div>
      <div class="endpoints-grid">

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/users</span>
          </div>
          <p class="endpoint-desc">Lista usuarios paginados con filtros de búsqueda y estado.</p>
          <span class="endpoint-permission">🔑 users:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/users</span>
          </div>
          <p class="endpoint-desc">Crea un nuevo usuario asignándole un rol y sucursal.</p>
          <span class="endpoint-permission">🔑 users:create</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method put">PUT</span>
            <span class="endpoint-path">/api/v1/users/{id}</span>
          </div>
          <p class="endpoint-desc">Actualiza los datos de un usuario existente.</p>
          <span class="endpoint-permission">🔑 users:update</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method delete">DELETE</span>
            <span class="endpoint-path">/api/v1/users/{id}</span>
          </div>
          <p class="endpoint-desc">Desactiva un usuario (soft delete).</p>
          <span class="endpoint-permission">🔑 users:delete</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/users/{id}/roles</span>
          </div>
          <p class="endpoint-desc">Asigna un rol a un usuario en una sucursal específica.</p>
          <span class="endpoint-permission">🔑 users:assign-roles</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method put">PUT</span>
            <span class="endpoint-path">/api/v1/users/{id}/password</span>
          </div>
          <p class="endpoint-desc">Cambia la contraseña del usuario autenticado.</p>
        </div>

      </div>
    </div>

    <!-- ── Roles y Permisos ── -->
    <div class="endpoint-group">
      <div class="endpoint-group-title">🛡️ Roles &amp; Permisos</div>
      <div class="endpoints-grid">

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/roles</span>
          </div>
          <p class="endpoint-desc">Lista todos los roles disponibles de la empresa.</p>
          <span class="endpoint-permission">🔑 roles:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/roles</span>
          </div>
          <p class="endpoint-desc">Crea un nuevo rol personalizado con permisos asignados.</p>
          <span class="endpoint-permission">🔑 roles:create</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method put">PUT</span>
            <span class="endpoint-path">/api/v1/roles/{id}/permissions</span>
          </div>
          <p class="endpoint-desc">Sincroniza (reemplaza) los permisos de un rol.</p>
          <span class="endpoint-permission">🔑 roles:update</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/permissions</span>
          </div>
          <p class="endpoint-desc">Lista todos los permisos del sistema.</p>
          <span class="endpoint-permission">🔑 roles:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/permissions/by-module</span>
          </div>
          <p class="endpoint-desc">Permisos agrupados por módulo (products, sales, users…).</p>
          <span class="endpoint-permission">🔑 roles:read</span>
        </div>

      </div>
    </div>

    <!-- ── Sucursales ── -->
    <div class="endpoint-group">
      <div class="endpoint-group-title">🏢 Sucursales <span class="new-badge">NUEVO</span></div>
      <div class="endpoints-grid">

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/branch</span>
          </div>
          <p class="endpoint-desc">Lista sucursales paginadas con filtros de búsqueda y estado.</p>
          <span class="endpoint-permission">🔑 branch:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/branch</span>
          </div>
          <p class="endpoint-desc">Crea una sucursal con dirección, contacto, horarios y configuración.</p>
          <span class="endpoint-permission">🔑 branch:create</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/branch/{id}</span>
          </div>
          <p class="endpoint-desc">Devuelve el detalle completo de una sucursal.</p>
          <span class="endpoint-permission">🔑 branch:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method put">PUT</span>
            <span class="endpoint-path">/api/v1/branch/{id}</span>
          </div>
          <p class="endpoint-desc">Actualiza parcialmente los datos de una sucursal.</p>
          <span class="endpoint-permission">🔑 branch:update</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method delete">DELETE</span>
            <span class="endpoint-path">/api/v1/branch/{id}</span>
          </div>
          <p class="endpoint-desc">Desactiva una sucursal (soft delete).</p>
          <span class="endpoint-permission">🔑 branch:delete</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/branch/{id}/activate</span>
          </div>
          <p class="endpoint-desc">Reactiva una sucursal previamente desactivada.</p>
          <span class="endpoint-permission">🔑 branch:update</span>
        </div>

      </div>
    </div>

    <!-- ── Categorías ── -->
    <div class="endpoint-group">
      <div class="endpoint-group-title">🗂️ Categorías</div>
      <div class="endpoints-grid">

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/category</span>
          </div>
          <p class="endpoint-desc">Lista categorías con paginación y filtros de búsqueda.</p>
          <span class="endpoint-permission">🔑 category:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/category</span>
          </div>
          <p class="endpoint-desc">Crea una nueva categoría o subcategoría.</p>
          <span class="endpoint-permission">🔑 category:create</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method put">PUT</span>
            <span class="endpoint-path">/api/v1/category/{id}</span>
          </div>
          <p class="endpoint-desc">Actualiza el nombre, descripción o imagen de la categoría.</p>
          <span class="endpoint-permission">🔑 category:update</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method delete">DELETE</span>
            <span class="endpoint-path">/api/v1/category/{id}</span>
          </div>
          <p class="endpoint-desc">Desactiva una categoría (soft delete).</p>
          <span class="endpoint-permission">🔑 category:delete</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/category/{id}/subcategories</span>
          </div>
          <p class="endpoint-desc">Lista las subcategorías directas de una categoría padre.</p>
          <span class="endpoint-permission">🔑 category:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/category/{id}/activate</span>
          </div>
          <p class="endpoint-desc">Reactiva una categoría previamente desactivada.</p>
          <span class="endpoint-permission">🔑 category:update</span>
        </div>

      </div>
    </div>

    <!-- ── Productos ── -->
    <div class="endpoint-group">
      <div class="endpoint-group-title">📦 Productos</div>
      <div class="endpoints-grid">

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/products</span>
          </div>
          <p class="endpoint-desc">Lista productos paginados con filtros por categoría, búsqueda y estado.</p>
          <span class="endpoint-permission">🔑 products:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/products</span>
          </div>
          <p class="endpoint-desc">Crea un producto con precios, impuestos, tipo y atributos.</p>
          <span class="endpoint-permission">🔑 products:create</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/products/{id}</span>
          </div>
          <p class="endpoint-desc">Devuelve el detalle completo de un producto.</p>
          <span class="endpoint-permission">🔑 products:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method put">PUT</span>
            <span class="endpoint-path">/api/v1/products/{id}</span>
          </div>
          <p class="endpoint-desc">Actualiza parcialmente los datos de un producto.</p>
          <span class="endpoint-permission">🔑 products:update</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method delete">DELETE</span>
            <span class="endpoint-path">/api/v1/products/{id}</span>
          </div>
          <p class="endpoint-desc">Desactiva un producto (soft delete).</p>
          <span class="endpoint-permission">🔑 products:delete</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/products/{categoryId}/products</span>
          </div>
          <p class="endpoint-desc">Lista todos los productos que pertenecen a una categoría.</p>
          <span class="endpoint-permission">🔑 products:read</span>
        </div>

      </div>
    </div>

    <!-- ── Variantes de Producto ── -->
    <div class="endpoint-group">
      <div class="endpoint-group-title">🎨 Variantes de Producto <span class="new-badge">NUEVO</span></div>
      <div class="endpoints-grid">

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/products/{id}/variant</span>
          </div>
          <p class="endpoint-desc">Lista todas las variantes de un producto (tallas, colores, etc.).</p>
          <span class="endpoint-permission">🔑 products:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/products/{id}/variant</span>
          </div>
          <p class="endpoint-desc">Crea una nueva variante con su propio SKU y precio.</p>
          <span class="endpoint-permission">🔑 products:create</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/products/{id}/variant/{variantId}</span>
          </div>
          <p class="endpoint-desc">Devuelve el detalle de una variante con sus barcodes.</p>
          <span class="endpoint-permission">🔑 products:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method put">PUT</span>
            <span class="endpoint-path">/api/v1/products/{id}/variant/{variantId}</span>
          </div>
          <p class="endpoint-desc">Actualiza nombre, precio o atributos de una variante.</p>
          <span class="endpoint-permission">🔑 products:update</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method delete">DELETE</span>
            <span class="endpoint-path">/api/v1/products/{id}/variant/{variantId}</span>
          </div>
          <p class="endpoint-desc">Desactiva una variante del producto.</p>
          <span class="endpoint-permission">🔑 products:delete</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/products/{id}/variant/{variantId}/activate</span>
          </div>
          <p class="endpoint-desc">Reactiva una variante previamente desactivada.</p>
          <span class="endpoint-permission">🔑 products:update</span>
        </div>

      </div>
    </div>

    <!-- ── Códigos de Barras ── -->
    <div class="endpoint-group">
      <div class="endpoint-group-title">📊 Códigos de Barras <span class="new-badge">NUEVO</span></div>
      <div class="endpoints-grid">

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/products/{id}/barcode</span>
          </div>
          <p class="endpoint-desc">Lista todos los barcodes asociados a las variantes del producto.</p>
          <span class="endpoint-permission">🔑 products:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/products/{id}/barcode</span>
          </div>
          <p class="endpoint-desc">Asigna un código de barras (EAN13, QR, UPC…) a una variante.</p>
          <span class="endpoint-permission">🔑 products:create</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/products/{id}/barcode/{barcodeId}</span>
          </div>
          <p class="endpoint-desc">Devuelve el detalle de un barcode con su variante asociada.</p>
          <span class="endpoint-permission">🔑 products:read</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method put">PUT</span>
            <span class="endpoint-path">/api/v1/products/{id}/barcode/{barcodeId}</span>
          </div>
          <p class="endpoint-desc">Actualiza el código, tipo o estado primario del barcode.</p>
          <span class="endpoint-permission">🔑 products:update</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method delete">DELETE</span>
            <span class="endpoint-path">/api/v1/products/{id}/barcode/{barcodeId}</span>
          </div>
          <p class="endpoint-desc">Elimina permanentemente un código de barras.</p>
          <span class="endpoint-permission">🔑 products:delete</span>
        </div>

        <div class="endpoint-card">
          <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/products/barcode/scan/{code}</span>
          </div>
          <p class="endpoint-desc">Escanea un código y retorna la variante y producto. Ideal para POS.</p>
          <span class="endpoint-permission">🔑 products:read</span>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- ══ AUTENTICACIÓN ══ -->
<section class="info-section" id="autenticacion">
  <div class="info-content">
    <h2>Autenticación</h2>
    <p>La API utiliza <strong>JSON Web Tokens (JWT)</strong> para proteger los recursos. Incluye el token en cada solicitud mediante el header <code style="background:rgba(0,0,0,.2);padding:.15rem .5rem;border-radius:4px;font-family:monospace">Authorization</code>.</p>
    <p style="font-family:monospace;font-size:.95rem;background:rgba(0,0,0,.2);padding:.75rem 1.25rem;border-radius:8px;margin-top:1rem">
      Authorization: Bearer &lt;tu-token&gt;
    </p>
    <div class="info-pills">
      <span class="pill">Laravel 11</span>
      <span class="pill">PHP 8.2</span>
      <span class="pill">JWT Auth</span>
      <span class="pill">REST JSON</span>
      <span class="pill">Repository Pattern</span>
    </div>
  </div>
</section>

<!-- ══ CONTACTO ══ -->
<section class="contact-section" id="contacto">
  <div class="contact-container">
    <div class="contact-avatar">A</div>
    <h2 class="contact-name">Alejandro Benjumea Aguirre</h2>
    <p class="contact-role">Desarrollador Backend · Laravel &amp; PHP</p>
    <div class="contact-links">

      <a class="contact-link email" href="mailto:alejo120792120792@hotmail.com">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="4" width="20" height="16" rx="2"/>
          <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
        </svg>
        alejo120792120792@hotmail.com
      </a>

      <a class="contact-link github" href="https://github.com/Alejandro-Benjumea-Aguirre" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844a9.59 9.59 0 0 1 2.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0 0 22 12.017C22 6.484 17.522 2 12 2z"/>
        </svg>
        GitHub
      </a>

      <a class="contact-link linkedin" href="https://www.linkedin.com/in/alejandrobenjumea/" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="currentColor">
          <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
        </svg>
        LinkedIn
      </a>

      <a class="contact-link website" href="https://alejodev.cloud" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
        </svg>
        alejodev.cloud
      </a>

    </div>
  </div>
</section>

<!-- ══ FOOTER ══ -->
<footer id="footer">
  <div class="footer-content">
    <div class="footer-section">
      <h4>API</h4>
      <a href="#recursos">Recursos</a>
      <a href="#endpoints">Endpoints</a>
      <a href="#autenticacion">Autenticación</a>
    </div>
    <div class="footer-section">
      <h4>Stack</h4>
      <a href="#">Laravel 11</a>
      <a href="#">PHP 8.2</a>
      <a href="#">JWT Auth</a>
    </div>
    <div class="footer-section">
      <h4>Contacto</h4>
      <a href="mailto:alejo120792120792@hotmail.com">Email</a>
      <a href="https://github.com/Alejandro-Benjumea-Aguirre" target="_blank" rel="noopener">GitHub</a>
      <a href="https://www.linkedin.com/in/alejandrobenjumea/" target="_blank" rel="noopener">LinkedIn</a>
      <a href="https://alejodev.cloud" target="_blank" rel="noopener">Sitio Web</a>
    </div>
    <div class="footer-section">
      <h4>Legal</h4>
      <a href="#">Privacidad</a>
      <a href="#">Términos de uso</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; {{ date('Y') }} Kardex CO. Todos los derechos reservados.</p>
  </div>
</footer>

<script>
  // ── Dark mode toggle ──
  const toggle = document.getElementById('themeToggle');
  const html   = document.documentElement;

  // Restore preference
  if (localStorage.getItem('theme') === 'dark') {
    html.classList.add('dark');
    toggle.textContent = '☀️';
  }

  toggle.addEventListener('click', () => {
    const isDark = html.classList.toggle('dark');
    toggle.textContent  = isDark ? '☀️' : '🌙';
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
  });
</script>
</body>
</html>
