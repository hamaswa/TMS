<style>
/* Shared customer create/edit tailoring layout. */
.customer-tailoring-shell { width: 100%; max-width: 1420px; margin-inline: auto; min-width: 0; }
.customer-tailoring-layout { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: stretch; gap: 16px; min-width: 0; }
.customer-tailoring-layout > * { min-width: 0; }
.customer-tailoring-layout .measurement-grid,
.customer-tailoring-layout .preference-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 14px; align-content: start; min-width: 0; }
.customer-tailoring-layout .measurement-grid > *,
.customer-tailoring-layout .preference-grid > * { min-width: 0; width: 100%; margin: 0; }
.customer-tailoring-layout .form-control { width: 100%; min-width: 0; max-width: 100%; }
.customer-tailoring-layout .input-group { min-width: 0; flex-wrap: nowrap; }
.customer-tailoring-layout .input-group > .form-control { min-width: 0; }
.customer-tailoring-layout.template-one-column { grid-template-columns: minmax(0, 1fr); }
@media (max-width: 1100px) {
    .customer-tailoring-layout:not(.template-one-column) { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 580px) {
    .customer-tailoring-layout .measurement-grid,
    .customer-tailoring-layout .preference-grid { grid-template-columns: minmax(0, 1fr); }
}
</style>
