<style>
    #tailorOrdersModal { direction: rtl; text-align: right; }
    #tailorOrdersModal .modal-dialog { max-width: min(1100px, calc(100% - 24px)); }
    #tailorOrdersModal .modal-content { overflow: hidden; border: 0; border-radius: 16px; box-shadow: 0 22px 65px rgba(15,38,70,.22); }
    #tailorOrdersModal .modal-header { align-items: center; padding: 18px 22px; border-bottom: 1px solid #e1e9f3; }
    #tailorOrdersModal .modal-title { margin: 0; color: #102a50; font-size: 1.15rem; font-weight: 800; }
    #tailorOrdersModal .modal-title small { display: block; margin-top: 4px; color: #75849a; font-size: .78rem; font-weight: 500; }
    #tailorOrdersModal .close { margin: -1rem auto -1rem -1rem; }
    #tailorOrdersModal .modal-body { max-height: 68vh; padding: 0; overflow: auto; }
    #tailorOrdersModal .modal-footer { padding: 13px 18px; border-top: 1px solid #e1e9f3; }
    #tailorOrdersModal .to-filtered-table { min-width: 900px; }
    #tailorOrdersModal .to-filtered-table { table-layout: fixed; }
    #tailorOrdersModal .to-filtered-table th { padding: 13px 14px; color: #53647e; background: #f4f7fb; border-color: #e1e9f3; font-size: .8rem; text-align: center; white-space: nowrap; }
    #tailorOrdersModal .to-filtered-table td { padding: 14px; color: #253955; border-color: #e7edf5; vertical-align: middle; text-align: center; }
    #tailorOrdersModal .to-filtered-table tfoot th { color: #102a50; background: #edf5ff; font-weight: 800; }
</style>

<div class="modal fade" id="tailorOrdersModal" tabindex="-1" role="dialog" aria-labelledby="tailorOrdersModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tailorOrdersModalTitle"><i class="fas fa-calendar-check text-primary ml-2"></i>منتخب مدت کے آرڈرز<small id="tailorFilteredPeriod"></small></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں">&times;</button>
            </div>
            <div class="modal-body" id="modalContent"></div>
            <div class="modal-footer"><button type="button" class="to-btn" data-dismiss="modal"><i class="fas fa-times"></i> بند کریں</button></div>
        </div>
    </div>
</div>
