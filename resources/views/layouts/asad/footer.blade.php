<footer class="text-white py-2">
    <div class="container text-center" style="font-size: 24px;">
        <p class="mb-0">Copyright &copy; tms.itlinked.tech </p>
        {{-- | Follow us on <a href="#"
                class="text-white">Facebook</a>, <a href="#" class="text-white">Twitter</a>, <a href="#"
                class="text-white">Instagram</a> --}}
    </div>
</footer>
<script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<!-- Bootstrap JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
<!-- Include jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<!-- Include Bootstrap's JavaScript -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();

            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    document.addEventListener('scroll', function() {
        var scrollPosition = window.scrollY;

        if (scrollPosition > 100) { // Adjust this value as needed
            document.getElementById('scrollToTop').style.display = 'block';
        } else {
            document.getElementById('scrollToTop').style.display = 'none';
        }
    });

    document.getElementById('scrollToTop').addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
</script>
</body>

</html>
