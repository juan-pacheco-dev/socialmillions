# Social Millions – Sistema de Gestión para Agencias de Streamers

**Social Millions** es una plataforma web desarrollada en PHP diseñada para la gestión integral de agencias de creadores de contenido (streamers). El sistema permite administrar desde el reclutamiento y formación de talentos hasta el seguimiento de su desempeño y la gestión de pagos, todo bajo una interfaz moderna y de alta gama.

---

## 🚀 Funcionalidades Principales

### 👥 Gestión de Usuarios y Roles
- **Administrador**: Control total del sistema, gestión de usuarios, eventos y configuración global.
- **Agencia (Sub-Agencias)**: Paneles independientes para gestionar sus propios grupos de modelos.
- **Modelo/Streamer**: Acceso a capacitaciones, documentos, calendario de eventos e historial de impulsos.
- **Viewer/Cliente**: Perfiles específicos para interacción con contenido y servicios.

### 📅 Eventos y Seguimiento
- **Gestión de Eventos**: Creación y supervisión de actividades con listas de participación.
- **Sistema de Impulsos**: Registro y seguimiento cronológico de impulsos para streamers con exportación a informes detallados.
- **Bigo IDs**: Auditoría y sincronización de identificadores de pago para plataformas externas.

### 🎥 Contenido y Documentación
- **Material Premium**: Biblioteca de contenido exclusivo para formación y uso de los streamers.
- **Gestor de Documentos**: Repositorio centralizado para contratos, manuales oficiales y recursos compartidos.

### 💳 Integración
- **Exportación de Datos**: Generación de reportes en formato XLSX para auditorías y contabilidad.

---

## 🛠️ Tecnologías Utilizadas

- **Backend**: PHP 8.x (Arquitectura modular)
- **Base de Datos**: MySQL / MariaDB
- **Frontend**: HTML5, CSS3 (Diseño "Luxury" con efectos de transparencia y gradientes), JavaScript Vanilla
- **APIs de Terceros**: DLocal SDK, EPayco Integration
- **Herramientas**: Composer, Git, GitHub

---

## 📋 Requisitos del Sistema

- **PHP**: Versión 8.0 o superior
- **Servidor Web**: Apache (recomendado XAMPP/WAMP)
- **Base de Datos**: MySQL 5.7+ o MariaDB 10.4+
- **Extensiones PHP**: `mysqli`, `curl`, `json`

---

## 🔧 Instalación y Configuración

1. **Clonar el Repositorio**:
   ```bash
   git clone https://github.com/juan-pacheco-dev/socialmillions.git
   ```

2. **Preparar el Entorno**:
   - Copia la carpeta del proyecto a tu directorio de servidor local (ej. `htdocs` en XAMPP).

3. **Configurar la Base de Datos**:
   - Crea una base de datos llamada `socialmillions`.
   - Importa el archivo SQL de respaldo (ubicado usualmente en `database/socialmillions.sql` o similar).

4. **Configurar la Conexión**:
   - El sistema ya viene pre-configurado para XAMPP. Si necesitas ajustar las credenciales, edita el archivo `includes/db_servidor.php`:
   ```php
   // Configuración XAMPP
   $db_host = 'localhost';
   $db_user = 'root';
   $db_pass = '';
   $db_name = 'socialmillions';
   ```

5. **Acceso al Sistema**:
   - Inicia Apache y MySQL desde el panel de XAMPP.
   - Abre tu navegador y navega a: `http://localhost/socialmillions/index.php`

---

## 📂 Estructura del Proyecto

```text
SocialMillions/
├── admin/          # Panel de administración y gestión global
├── agency/         # Módulos para sub-agencias
├── auth/           # Sistemas de login, registro y sesiones
├── client/         # Vistas y lógica para clientes/viewers
├── config/         # Archivos de configuración de APIs
├── css/            # Estilos modernos y diseño Luxury
├── includes/       # Conexión a DB, cabeceras, pies de página y utilidades
├── js/             # Lógica frontend reactiva
├── modelos/        # Panel específico para streamers/modelos
├── streamers/      # Recursos multimedia y optimizados
└── uploads/        # Directorio de almacenamiento de archivos
```

---

## 📝 Notas Académicas

Este proyecto ha sido desarrollado como parte de un proceso de aprendizaje avanzado en desarrollo web, enfocándose en la creación de interfaces premium y la lógica de negocio compleja sin el uso de frameworks pesados para afianzar conceptos fundamentales de PHP y SQL.

---

## 👤 Autor
**Juan Esteban Ramirez Pacheco**  
*Programador y Desarrollador del Proyecto para SocialMillions.*

