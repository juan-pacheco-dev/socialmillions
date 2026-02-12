<?php
// payment_success.php
include '../includes/header.php';
?>

<!-- Enlace al CSS Moderno con Cache Busting -->
<link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">

<main class="payment-test-container">
    <div class="payment-card reveal active" style="border-top-color: #4ade80;">
        <span class="api-status status-online">Transacción Exitosa</span>
        <h2>Pago <span class="gradient-text">Confirmado</span></h2>
        <p>¡Felicidades! Se ha procesado la transacción de prueba correctamente en el entorno Sandbox.</p>

        <a href="test_payments.php" class="btn btn-outline" style="width: 100%; text-decoration: none;">
            Volver a las Pruebas
        </a>
    </div>
</main>

<?php include '../includes/footer.php'; ?>