/**
 * ============================================================
 * SISTEMA DE PROTECCIÓN ANTI-CAPTURA Y ANTI-GRABACIÓN
 * ============================================================
 * 
 * Protección avanzada para contenido adulto.
 * Implemento múltiples capas de seguridad para disuadir
 * capturas de pantalla y grabaciones.
 * 
 * CAPAS IMPLEMENTADAS:
 * 1. Bloqueo de teclas (PrintScreen, DevTools, etc.)
 * 2. Bloqueo de clic derecho
 * 3. Detección de visibilidad y blur
 * 4. Overlay negro/blur automático
 * 5. Pausar videos ante eventos sospechosos
 * 6. Log de intentos al backend
 * 
 * @author Sistema de Protección
 */

const ProtectionSystem = (function () {
    'use strict';

    // ========== CONFIGURACIÓN ==========
    const CONFIG = {
        // Tiempo de blur en móviles (ms) después de volver visible
        mobileBlurDuration: 500,
        // Intervalo para cambiar posición de watermark (ms)
        watermarkInterval: 10000,
        // URL del endpoint de logs
        logEndpoint: (window.location.pathname.includes('/model/') || window.location.pathname.includes('/client/')) ? '../api/log_protection_event.php' : 'api/log_protection_event.php',
        // Activar logs en consola (solo desarrollo)
        debug: false
    };

    // ========== ESTADO ==========
    let isProtectionActive = false;
    let lastVisibilityState = 'visible';
    let watermarkIntervalId = null;

    // ========== INICIALIZACIÓN ==========

    /**
     * Inicializo todo el sistema de protección
     */
    function init() {
        log('Inicializando sistema de protección...');

        // Creo el overlay de protección
        createProtectionOverlay();

        // Activo todas las capas de protección
        initKeyboardProtection();
        initMouseProtection();
        initVisibilityProtection();
        initResizeProtection();
        initFullscreenProtection();
        initVideoProtection();
        initImageProtection();
        initDevToolsDetection();
        initWatermark();

        log('Sistema de protección activado');
    }

    // ========== CAPA 1: OVERLAY DE PROTECCIÓN ==========

    /**
     * Creo el overlay negro/borroso que se activa ante eventos sospechosos
     */
    function createProtectionOverlay() {
        // Verifico si ya existe
        if (document.getElementById('protection-overlay')) return;

        const overlay = document.createElement('div');
        overlay.id = 'protection-overlay';
        overlay.innerHTML = `
            <div class="protection-message">
                <div class="protection-icon">🛡️</div>
                <div class="protection-text">Contenido Protegido</div>
                <div class="protection-subtext">Toca para continuar</div>
            </div>
        `;
        document.body.appendChild(overlay);

        // Al tocar el overlay, desactivo la protección
        overlay.addEventListener('click', deactivateProtection);
        overlay.addEventListener('touchstart', deactivateProtection);
    }

    /**
     * Activo el overlay de protección
     * @param {string} reason - Razón de la activación
     */
    function activateProtection(reason = 'unknown') {
        if (isProtectionActive) return;

        isProtectionActive = true;
        const overlay = document.getElementById('protection-overlay');
        if (overlay) {
            overlay.classList.add('active');
        }

        // Aplico desenfoque al contenido
        document.body.classList.add('content-protected');

        // Pauso todos los videos
        pauseAllVideos();

        // Registro el evento
        logProtectionEvent(reason);

        log('Protección activada:', reason);
    }

    /**
     * Desactivo el overlay de protección
     */
    function deactivateProtection() {
        isProtectionActive = false;
        const overlay = document.getElementById('protection-overlay');
        if (overlay) {
            overlay.classList.remove('active');
        }

        // Quito el desenfoque
        document.body.classList.remove('content-protected');

        log('Protección desactivada');
    }

    // ========== CAPA 2: PROTECCIÓN DE TECLADO ==========

    /**
     * Bloqueo teclas peligrosas
     */
    function initKeyboardProtection() {
        document.addEventListener('keydown', function (e) {
            // PrintScreen
            if (e.key === 'PrintScreen') {
                e.preventDefault();
                activateProtection('printscreen');
                return false;
            }

            // Ctrl + teclas
            if (e.ctrlKey || e.metaKey) {
                // Ctrl+S (Guardar)
                if (e.key === 's' || e.key === 'S') {
                    e.preventDefault();
                    activateProtection('ctrl_s');
                    return false;
                }
                // Ctrl+U (Ver código fuente)
                if (e.key === 'u' || e.key === 'U') {
                    e.preventDefault();
                    activateProtection('ctrl_u');
                    return false;
                }
                // Ctrl+P (Imprimir)
                if (e.key === 'p' || e.key === 'P') {
                    e.preventDefault();
                    activateProtection('ctrl_p');
                    return false;
                }
                // Ctrl+Shift+I (DevTools)
                if (e.shiftKey && (e.key === 'i' || e.key === 'I')) {
                    e.preventDefault();
                    activateProtection('devtools_shortcut');
                    return false;
                }
                // Ctrl+Shift+C (Inspector)
                if (e.shiftKey && (e.key === 'c' || e.key === 'C')) {
                    e.preventDefault();
                    activateProtection('inspector_shortcut');
                    return false;
                }
                // Ctrl+Shift+J (Consola)
                if (e.shiftKey && (e.key === 'j' || e.key === 'J')) {
                    e.preventDefault();
                    activateProtection('console_shortcut');
                    return false;
                }
            }

            // F12 (DevTools)
            if (e.key === 'F12') {
                e.preventDefault();
                activateProtection('f12');
                return false;
            }
        }, true);

        // También detectar keyup de PrintScreen
        document.addEventListener('keyup', function (e) {
            if (e.key === 'PrintScreen') {
                activateProtection('printscreen_release');
            }
        });
    }

    // ========== CAPA 3: PROTECCIÓN DE MOUSE ==========

    /**
     * Bloqueo clic derecho y otras acciones del mouse
     */
    function initMouseProtection() {
        // Bloquear clic derecho
        document.addEventListener('contextmenu', function (e) {
            // Permito solo en inputs y textareas
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return true;
            }
            e.preventDefault();
            logProtectionEvent('right_click');
            return false;
        });

        // Bloqueo arrastrar imágenes
        document.addEventListener('dragstart', function (e) {
            if (e.target.tagName === 'IMG' || e.target.tagName === 'VIDEO') {
                e.preventDefault();
                return false;
            }
        });

        // Bloqueo selección en imágenes y videos
        document.addEventListener('selectstart', function (e) {
            if (e.target.closest('.protected-content, .content-item, .secure-video-wrapper')) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ========== CAPA 4: DETECCIÓN DE VISIBILIDAD ==========

    /**
     * Detecto cuando la página pierde visibilidad (cambio de app, captura, etc.)
     */
    function initVisibilityProtection() {
        // Evento principal de visibilidad
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                // Página oculta - activo protección
                activateProtection('visibility_hidden');
                lastVisibilityState = 'hidden';
            } else {
                // Página visible de nuevo
                if (lastVisibilityState === 'hidden') {
                    // Mantengo la protección activa hasta que el usuario interactúe
                    // Esto es importante para móviles post-captura
                    setTimeout(function () {
                        // La protección se desactiva con un toque
                    }, CONFIG.mobileBlurDuration);
                }
                lastVisibilityState = 'visible';
            }
        });

        // Evento de blur de ventana (PC)
        window.addEventListener('blur', function () {
            activateProtection('window_blur');
        });

        // Evento de foco (no desactivar automáticamente)
        window.addEventListener('focus', function () {
            // No desactivar automáticamente, esperar interacción del usuario
            // para móviles donde la captura ocurre en segundo plano
        });

        // Detección específica para iOS/Safari
        window.addEventListener('pagehide', function () {
            activateProtection('pagehide');
        });
    }

    // ========== CAPA 5: DETECCIÓN DE RESIZE ==========

    /**
     * Detecto cambios de tamaño sospechosos (indicador de DevTools)
     */
    function initResizeProtection() {
        let lastWidth = window.outerWidth;
        let lastHeight = window.outerHeight;

        // Verifico si el cambio de tamaño es sospechoso
        function checkResize() {
            const widthDiff = Math.abs(window.outerWidth - window.innerWidth);
            const heightDiff = Math.abs(window.outerHeight - window.innerHeight);

            // Si hay gran diferencia, probablemente DevTools está abierto
            if (widthDiff > 160 || heightDiff > 160) {
                activateProtection('devtools_resize');
            }
        }

        window.addEventListener('resize', function () {
            checkResize();
        });

        // Verifico periódicamente
        setInterval(checkResize, 2000);
    }

    // ========== CAPA 6: FULLSCREEN PROTECTION ==========

    /**
     * Protección específica para pantalla completa
     */
    function initFullscreenProtection() {
        document.addEventListener('fullscreenchange', function () {
            if (!document.fullscreenElement) {
                // Saliendo de pantalla completa - posible captura
                // No activo protección aquí para no molestar
                logProtectionEvent('fullscreen_exit');
            }
        });

        // Webkit
        document.addEventListener('webkitfullscreenchange', function () {
            if (!document.webkitFullscreenElement) {
                logProtectionEvent('fullscreen_exit_webkit');
            }
        });
    }

    // ========== CAPA 7: PROTECCIÓN DE VIDEOS ==========

    /**
     * Aplico protecciones a todos los videos
     */
    function initVideoProtection() {
        // Aplico a videos existentes
        applyVideoProtection();

        // Observo nuevos videos agregados al DOM
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.tagName === 'VIDEO') {
                        protectVideo(node);
                    } else if (node.querySelectorAll) {
                        node.querySelectorAll('video').forEach(protectVideo);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    /**
     * Aplico protección a todos los videos existentes
     */
    function applyVideoProtection() {
        document.querySelectorAll('video').forEach(protectVideo);
    }

    /**
     * Protejo un video individual
     * @param {HTMLVideoElement} video
     */
    function protectVideo(video) {
        // Atributos de protección
        video.setAttribute('controlsList', 'nodownload noremoteplayback');
        video.setAttribute('disablePictureInPicture', 'true');
        video.setAttribute('preload', 'metadata');

        // Prevengo clic derecho en video
        video.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            return false;
        });

        // Prevengo arrastrar
        video.addEventListener('dragstart', function (e) {
            e.preventDefault();
            return false;
        });
    }

    /**
     * Pauso todos los videos de la página
     */
    function pauseAllVideos() {
        document.querySelectorAll('video').forEach(function (video) {
            if (!video.paused) {
                video.pause();
            }
        });
    }

    // ========== CAPA 8: PROTECCIÓN DE IMÁGENES ==========

    /**
     * Aplico protecciones a todas las imágenes
     */
    function initImageProtection() {
        // Aplico a imágenes existentes
        document.querySelectorAll('img').forEach(protectImage);

        // Observo nuevas imágenes
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.tagName === 'IMG') {
                        protectImage(node);
                    } else if (node.querySelectorAll) {
                        node.querySelectorAll('img').forEach(protectImage);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    /**
     * Protejo una imagen individual
     * @param {HTMLImageElement} img
     */
    function protectImage(img) {
        // Prevenir arrastrar
        img.draggable = false;

        // Prevenir clic derecho
        img.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            return false;
        });

        // Clase para estilos de protección
        img.classList.add('protected-image');
    }

    // ========== CAPA 9: DETECCIÓN DE DEVTOOLS ==========

    /**
     * Intento detectar si DevTools está abierto
     */
    function initDevToolsDetection() {
        // Permitir desactivar temporalmente para depuración si hay un flag en sessionStorage
        if (sessionStorage.getItem('debug_mode') === 'true') {
            log('Modo depuración detectado - Detección de DevTools desactivada');
            return;
        }

        const element = new Image();
        Object.defineProperty(element, 'id', {
            get: function () {
                activateProtection('devtools_detected');
            }
        });

        // Verifico periódicamente
        setInterval(function () {
            if (sessionStorage.getItem('debug_mode') === 'true') return;
            console.log('%c', element);
            console.clear();
        }, 1000);

        // Método alternativo: debugger timing
        setInterval(function () {
            if (sessionStorage.getItem('debug_mode') === 'true') return;
            const start = performance.now();
            debugger;
            const end = performance.now();
            if (end - start > 100) {
                activateProtection('debugger_detected');
            }
        }, 5000);
    }

    // ========== CAPA 10: WATERMARK DINÁMICO ==========

    /**
     * Inicializo el sistema de watermark dinámico
     */
    function initWatermark() {
        // Crear contenedor de watermark
        createWatermarkContainer();

        // Actualizo la posición periódicamente
        updateWatermarkPosition();
        watermarkIntervalId = setInterval(updateWatermarkPosition, CONFIG.watermarkInterval);
    }

    /**
     * Creo el contenedor de watermark
     */
    function createWatermarkContainer() {
        if (document.getElementById('dynamic-watermark')) return;

        const userId = window.PROTECTION_USER_ID || 'USER';
        const now = new Date();
        const dateStr = now.toLocaleDateString('es-ES');

        const watermark = document.createElement('div');
        watermark.id = 'dynamic-watermark';
        watermark.className = 'dynamic-watermark';
        watermark.innerHTML = `
            <span class="wm-text">${userId} | ${dateStr}</span>
        `;
        document.body.appendChild(watermark);
    }

    /**
     * Actualizo la posición del watermark
     */
    function updateWatermarkPosition() {
        const watermark = document.getElementById('dynamic-watermark');
        if (!watermark) return;

        // Posiciones aleatorias
        const positions = [
            { top: '10%', left: '10%' },
            { top: '10%', right: '10%', left: 'auto' },
            { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' },
            { bottom: '10%', left: '10%', top: 'auto' },
            { bottom: '10%', right: '10%', top: 'auto', left: 'auto' }
        ];

        const pos = positions[Math.floor(Math.random() * positions.length)];

        // Reseteo la posición
        watermark.style.top = '';
        watermark.style.bottom = '';
        watermark.style.left = '';
        watermark.style.right = '';
        watermark.style.transform = '';

        // Aplicar nueva posición
        Object.assign(watermark.style, pos);
    }

    // ========== LOGGING ==========

    /**
     * Log interno (solo en modo debug)
     */
    function log(...args) {
        if (CONFIG.debug) {
            console.log('[ProtectionSystem]', ...args);
        }
    }

    /**
     * Envío evento de protección al backend
     * @param {string} eventType
     */
    function logProtectionEvent(eventType) {
        try {
            const data = new FormData();
            data.append('event_type', eventType);
            data.append('timestamp', Date.now());
            data.append('url', window.location.pathname);
            data.append('user_agent', navigator.userAgent);

            fetch(CONFIG.logEndpoint, {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            }).catch(function () {
                // Silenciar errores de red
            });
        } catch (e) {
            // Silenciar errores
        }
    }

    // ========== API PÚBLICA ==========
    return {
        init: init,
        activate: activateProtection,
        deactivate: deactivateProtection,
        pauseVideos: pauseAllVideos
    };

})();

// ========== AUTO-INICIALIZACIÓN ==========
document.addEventListener('DOMContentLoaded', function () {
    ProtectionSystem.init();
});

// Exportar para uso global
window.ProtectionSystem = ProtectionSystem;
