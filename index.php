<?php
include 'includes/header.php';
?>

<!-- Vinculo el CSS moderno con limpieza de caché -->
<link rel="stylesheet" href="css/index.css?v=<?php echo time(); ?>">

<main>
    <!-- SECCIÓN HERO MODERNA -->
    <section class="hero">
        <div class="hero-content">
            <span class="hero-badge">Social Millions Agency</span>
            <h1>Agencia de Creadores Digitales & <br><span class="gradient-text">Entretenimiento Online</span></h1>
            <p class="subtitle">Gestión, formación y acompañamiento estratégico para talentos en plataformas de live
                streaming.</p>
            <div class="cta-group">
                <a href="auth/register.php" class="btn btn-primary">Aplicar como Creador</a>
                <a href="#que-es" class="btn btn-outline">Conocer Más</a>
            </div>
        </div>
    </section>

    <!-- CARRUSEL DE STREAMERS -->
    <section class="streamers-carousel">
        <div class="carousel-track">
            <!-- Primera vuelta de imágenes -->
            <div class="streamer-item"><img src="streamers/optimized/hombre1.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/mujer1.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/hombre2.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/mujer2.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/hombre3.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/mujer3.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/hombre4.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/mujer4.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>

            <!-- Duplicado para el efecto infinito (Debe ser idéntico al de arriba) -->
            <div class="streamer-item"><img src="streamers/optimized/hombre1.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/mujer1.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/hombre2.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/mujer2.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/hombre3.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/mujer3.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/hombre4.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
            <div class="streamer-item"><img src="streamers/optimized/mujer4.jpg" alt="Streamer Social Millions"
                    loading="lazy"></div>
        </div>
    </section>

    <!-- SECCIÓN QUÉ ES -->
    <section id="que-es" class="section-feature">
        <div class="glass-container">
            <h2 class="section-title">¿Qué es <span class="gradient-text">Social Millions</span>?</h2>
            <p class="feature-text">
                Social Millions es una <strong>agencia digital especializada</strong> en la gestión, formación y
                acompañamiento de creadores de contenido para plataformas de transmisión en vivo como
                Bigo Live, TikTok Live y otras aplicaciones de entretenimiento digital.
            </p>
            <p class="feature-text">
                Ofrecemos servicios de asesoría, capacitación, gestión de cuentas y acceso a oportunidades
                dentro del ecosistema de live streaming y social entertainment. Acompañamos a talentos digitales
                en su proceso de crecimiento, profesionalización y cumplimiento de normas dentro de plataformas
                internacionales.
            </p>
        </div>
    </section>

    <!-- SECCIÓN DE SERVICIOS -->
    <section id="servicios" class="services">
        <div class="container">
            <div class="section-header reveal">
                <h2 class="section-title">Nuestros <span class="gradient-text">Servicios</span></h2>
                <p class="section-desc">Soluciones integrales para el crecimiento profesional</p>
            </div>

            <div class="cards-grid">
                <!-- Card 1 -->
                <div class="card reveal">
                    <span class="card-icon">📈</span>
                    <h3>Gestión de Perfiles</h3>
                    <p>Gestión y acompañamiento de creadores digitales. Optimizamos tu presencia y estrategia de
                        contenido.</p>
                </div>
                <!-- Card 2 -->
                <div class="card reveal">
                    <span class="card-icon">🎓</span>
                    <h3>Formación Online</h3>
                    <p>Programas de capacitación para transmisiones en vivo. Aprende a conectar con tu audiencia de
                        manera profesional.</p>
                </div>
                <!-- Card 3 -->
                <div class="card reveal">
                    <span class="card-icon">🌐</span>
                    <h3>Acceso a Plataformas</h3>
                    <p>Acceso a programas de monetización en plataformas aliadas y aplicaciones de entretenimiento.</p>
                </div>
                <!-- Card 4 -->
                <div class="card reveal">
                    <span class="card-icon">🤝</span>
                    <h3>Soporte Personalizado</h3>
                    <p>Acompañamiento operativo y soporte técnico constante para resolver dudas y problemas.</p>
                </div>
                <!-- Card 5 -->
                <div class="card reveal">
                    <span class="card-icon">💎</span>
                    <h3>Membresía Digital</h3>
                    <p>Servicios digitales de membresía, auditorías de perfil y asesoría 1 a 1.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN CÓMO FUNCIONA -->
    <section id="como-funciona" class="how-it-works">
        <div class="container">
            <div class="section-header reveal">
                <h2 class="section-title">¿Cómo funciona <span class="gradient-text">Social Millions</span>?</h2>
            </div>

            <div class="steps-container">
                <div class="step reveal">
                    <div class="step-num">01</div>
                    <h3>Registro</h3>
                    <p>El usuario se registra y solicita información sobre nuestros programas.
                    </p>
                </div>
                <div class="step reveal">
                    <div class="step-num">02</div>
                    <h3>Formación</h3>
                    <p>Accede a programas de formación y acompañamiento digital especializado.
                    </p>
                </div>
                <div class="step reveal">
                    <div class="step-num">03</div>
                    <h3>Aplicación</h3>
                    <p>Recibe orientación para aplicar a plataformas externas de live streaming.
                    </p>
                </div>
                <div class="step reveal">
                    <div class="step-num">04</div>
                    <h3>Gestión</h3>
                    <p>El desempeño e ingresos dependen exclusivamente de las políticas de
                        dichas plataformas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN DE REQUISITOS -->
    <section class="requirements">
        <div class="glass-container glass-dark">
            <h2 class="req-title">Requisitos y Cumplimiento</h2>
            <div class="req-grid">
                <div class="req-item">
                    <span class="check-icon">✓</span>
                    <span>Solo para mayores de 18 años</span>
                </div>
                <div class="req-item">
                    <span class="check-icon">✓</span>
                    <span>Los creadores deben cumplir estrictamente las políticas de las
                        plataformas externas.</span>
                </div>
                <div class="req-item">
                    <span class="check-icon">✓</span>
                    <span>Social Millions no aloja ni distribuye contenido audiovisual.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA FINAL -->
    <section class="cta-banner reveal">
        <div class="container">
            <h2 style="margin-bottom: 1.5rem;">Potencia tu Carrera Digital</h2>
            <p class="subtitle" style="margin-bottom: 2rem;">Únete a la agencia de gestión líder.</p>
            <a href="auth/register.php" class="btn btn-primary btn-large">Postularme
                Ahora</a>
        </div>
    </section>
</main>

<script>
    const observerOptions = { threshold: 0.15 };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal').forEach(el => {
        observer.observe(el);
    });
</script>

<?php
include 'includes/footer.php';
?>