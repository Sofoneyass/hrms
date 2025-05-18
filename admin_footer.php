    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.createElement('button');
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            toggleBtn.className = 'btn btn-primary d-md-none position-fixed';
            toggleBtn.style.bottom = '20px';
            toggleBtn.style.right = '20px';
            toggleBtn.style.zIndex = '1000';
            toggleBtn.style.borderRadius = '50%';
            toggleBtn.style.width = '50px';
            toggleBtn.style.height = '50px';
            
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            
            document.body.appendChild(toggleBtn);
        });
    </script>
</body>
</html>