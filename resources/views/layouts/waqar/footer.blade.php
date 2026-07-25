<footer class="py-2">
    <div class="container text-center">
        <div class="row">
            <div class="col-12">
                <p class="mb-0">Copyright &copy; tms.itlinked.tech</p>
            </div>
            <!-- Uncomment to add social media links
            <div class="col-12 mt-2">
                <p class="mb-0">
                    Follow us on
                    <a href="#" class="text-white">Facebook</a>,
                    <a href="#" class="text-white">Twitter</a>,
                    <a href="#" class="text-white">Instagram</a>
                </p>
            </div>
            -->
        </div>
    </div>
</footer>


<!-- jQuery -->
<script src="{{ asset('public/assets/js/jquery-3.5.1.min.js') }}"></script>
<!-- Popper.js -->
<script src="{{ asset('public/assets/js/popper.min.js') }}"></script>
<!-- Bootstrap JS -->
@include('components.confirmation-modal')
<script src="{{ asset('public/assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/confirm-modal.js') }}"></script>
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
