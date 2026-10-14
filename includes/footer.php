    </div><!-- /.flex-grow-1 (dibuka di sidebar.php) -->
</div><!-- /.d-flex (dibuka di header.php) -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function () {
    document.querySelector('.sidebar')?.classList.toggle('show');
});
document.addEventListener('click', function (e) {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.getElementById('sidebarToggle');
    if (!sidebar || !toggle) return;
    if (sidebar.classList.contains('show') && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('show');
    }
});
</script>
</body>
</html>
