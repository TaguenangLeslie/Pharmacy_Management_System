<?php
/**
 * Footer Template
 */
?>
            <?php if (is_logged_in() && (!isset($hide_sidebar) || !$hide_sidebar)): ?>
            </main>
            <?php else: ?>
            </main>
            <?php endif; ?>
            <footer class="mt-auto py-3 text-center text-muted small border-top">
                &copy; <?php echo COPYRIGHT_YEAR; ?> <?php echo COPYRIGHT_HOLDER; ?>. All rights reserved.
            </footer>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    
    <?php echo $extra_js ?? ''; ?>
</body>
</html>
