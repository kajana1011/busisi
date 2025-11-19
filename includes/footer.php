</div> <!-- End Main Content -->

    <!-- Footer -->
    <footer class="mt-5 py-4 bg-light border-top">
        <div class="container text-center text-muted">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> Busisi Secondary School
            </p>
            <p class="mb-0 small text-end">
                Developed by Revocajana | <a href="https://wa.me/255769349613" target="_blank">WhatsApp Support</a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo (isset($isAdmin) && $isAdmin) ? '../assets/js/main.js' : 'assets/js/main.js'; ?>"></script>
</body>
</html>
