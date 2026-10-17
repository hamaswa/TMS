<script>
    function xpMoney(value) {
        return 'Rs. ' + (Number(value) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Client-side search: hides table rows whose [data-xp-text] cells don't match.
    jQuery(function ($) {
        $('[data-xp-search]').on('input', function () {
            var term = this.value.trim().toLowerCase();
            $($(this).data('xp-search')).find('[data-xp-row]').each(function () {
                var text = $(this).find('[data-xp-text]').text().toLowerCase();
                $(this).toggle(!term || text.indexOf(term) !== -1);
            });
        });
    });
</script>
