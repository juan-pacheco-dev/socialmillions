<?php include 'includes/header.php'; ?>
<style>
    .legal-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 20px;
        font-family: 'Inter', sans-serif;
        line-height: 1.6;
        color: #333;
    }

    .legal-container h1 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: #111;
    }

    .legal-container h2 {
        font-size: 1.5rem;
        margin-top: 30px;
        margin-bottom: 15px;
        color: #222;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
    }

    .legal-container p {
        margin-bottom: 15px;
    }

    .alert-box {

        border-left: 4px solid #4f46e5;
        padding: 20px;
        margin: 20px 0;
        border-radius: 4px;
    }

    body {
        background: #f9fafb;
    }
</style>

<main class="legal-container">
    <h1>Aviso Legal</h1>
    <p>Última actualización:
        <?php echo date('d/m/Y'); ?>
    </p>

    <div class="alert-box">
        <p><strong>Declaración Importante:</strong> Social Millions no es una plataforma de pagos, streaming ni
            llamadas. Operamos exclusivamente como agencia de servicios digitales y consultoría.</p>
    </div>

    <h2>1. Identidad Corporativa</h2>
    <p><strong>Nombre Comercial:</strong> Social Millions Agency<br>
        <strong>Actividad:</strong> Agencia de Marketing, Gestión de Talentos y Formación Digital.<br>
        <strong>Email de Contacto:</strong> socialmillionsagency@gmail.com
    </p>

    <h2>2. Propiedad Intelectual</h2>
    <p>Todo el contenido de este sitio web (textos, logotipos, diseños, material de formación) es propiedad exclusiva de
        Social Millions Agency o de sus respectivos titulares. Está prohibida su reproducción sin autorización expresa.
    </p>

    <h2>3. Exención de Responsabilidad</h2>
    <p>Social Millions no se hace responsable del uso indebido de las estrategias o conocimientos impartidos en sus
        programas. El usuario es el único responsable de asegurar que sus actividades cumplan con la legalidad de su
        jurisdicción.</p>

    <h2>4. Ley Aplicable</h2>
    <p>Este aviso legal se rige por las leyes del país donde Social Millions tiene su sede operativa principal.
        Cualquier disputa será resuelta en los tribunales competentes de dicha jurisdicción.</p>
</main>

<?php include 'includes/footer.php'; ?>