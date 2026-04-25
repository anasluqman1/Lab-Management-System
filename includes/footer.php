</div>
</main>
</div>

<script src="js/lang.js"></script>
<script src="js/app.js"></script>

<script>
    /* ── Anti-Inspect Protection ── */
    (function () {
        // Disable right-click context menu
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            return false;
        });

        // Block DevTools keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            // F12
            if (e.key === 'F12') { e.preventDefault(); return false; }
            // Ctrl+Shift+I / Ctrl+Shift+J / Ctrl+Shift+C
            if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C', 'i', 'j', 'c'].includes(e.key)) {
                e.preventDefault(); return false;
            }
            // Ctrl+U (view source)
            if (e.ctrlKey && ['U', 'u'].includes(e.key)) {
                e.preventDefault(); return false;
            }
            // Ctrl+S (save page)
            if (e.ctrlKey && ['S', 's'].includes(e.key)) {
                e.preventDefault(); return false;
            }
        });

        // DevTools open detection via timing trick
        (function detectDevTools() {
            const threshold = 160;
            function check() {
                const start = performance.now();
                debugger;
                if (performance.now() - start > threshold) {
                    document.body.innerHTML = '';
                    window.location.href = 'login.php';
                }
            }
            setInterval(check, 1000);
        })();
    })();
</script>

</body>

</html>