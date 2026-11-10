{{-- Super admin: pop up with a chime when shops send a new plan or upgrade request (on login and while the page is open). --}}
@auth
    @if(Auth::user()->hasRole('administrative'))
        @php($newPlanRequests = \App\Models\SubscriptionPlanRequest::takeUnannouncedFor(Auth::user())->map->alertData()->values())
        <div class="modal fade" id="plan-request-alert" tabindex="-1" role="alertdialog" aria-labelledby="plan-request-alert-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0" style="border-radius:1rem;overflow:hidden">
                    <div class="modal-header text-white border-0" style="background:linear-gradient(120deg,#1546b8,#3b82f6)">
                        <h5 class="modal-title" id="plan-request-alert-title"><i class="fas fa-bell" aria-hidden="true"></i> <span data-alert-title>New plan request</span></h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body" style="max-height:60vh;overflow:auto">
                        <ul class="list-unstyled mb-0" data-alert-list></ul>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Later</button>
                        <a class="btn btn-primary" href="{{ route('administrator.subscriptions.index') }}">Open subscriptions</a>
                    </div>
                </div>
            </div>
        </div>
        @push('scripts')
        <script>
            (function () {
                var AudioCtx = window.AudioContext || window.webkitAudioContext;

                function play(ctx) {
                    [[660, 0], [880, 0.16], [1175, 0.32]].forEach(function (note) {
                        var osc = ctx.createOscillator();
                        var gain = ctx.createGain();
                        var start = ctx.currentTime + note[1];
                        osc.type = 'sine';
                        osc.frequency.value = note[0];
                        gain.gain.setValueAtTime(0.0001, start);
                        gain.gain.exponentialRampToValueAtTime(0.25, start + 0.02);
                        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.45);
                        osc.connect(gain).connect(ctx.destination);
                        osc.start(start);
                        osc.stop(start + 0.5);
                    });
                    setTimeout(function () { ctx.close(); }, 1400);
                }

                // Browsers may block sound until the page is clicked once; then it plays on the first tap or key.
                function chime() {
                    if (!AudioCtx) { return; }
                    var ctx = new AudioCtx();
                    var played = false;
                    var playOnce = function () {
                        if (played || ctx.state !== 'running') { return; }
                        played = true;
                        document.removeEventListener('pointerdown', unlock);
                        document.removeEventListener('keydown', unlock);
                        play(ctx);
                    };
                    var unlock = function () { ctx.resume().then(playOnce); };
                    document.addEventListener('pointerdown', unlock);
                    document.addEventListener('keydown', unlock);
                    ctx.resume().then(playOnce);
                    playOnce();
                }

                function item(request) {
                    var li = $('<li class="border rounded p-3 mb-2">');
                    var head = $('<div class="d-flex justify-content-between align-items-start" style="gap:.5rem">').appendTo(li);
                    var who = $('<div>').appendTo(head);
                    $('<strong>').text(request.shop).appendTo(who);
                    $('<div class="small text-muted">').text(request.plan + ' · Rs ' + request.amount).appendTo(who);
                    $('<span class="badge">').addClass(request.upgrade ? 'badge-success' : 'badge-primary')
                        .text(request.upgrade ? 'Upgrade' : 'New subscription').appendTo(head);
                    if (request.reference) {
                        $('<div class="small mt-1">').text((request.method ? request.method + ' · ' : '') + 'TID: ').append($('<code>').text(request.reference)).appendTo(li);
                    }
                    if (request.reason) {
                        $('<div class="small mt-1">').text('Reason: ' + request.reason).appendTo(li);
                    }
                    if (request.url) {
                        $('<a class="btn btn-sm btn-outline-primary mt-2">').attr('href', request.url).text('Review request').appendTo(li);
                    }
                    return li;
                }

                function announce(requests) {
                    if (!requests || !requests.length) { return; }
                    var modal = $('#plan-request-alert');
                    var list = modal.find('[data-alert-list]');
                    if (!modal.hasClass('show')) { list.empty(); }
                    requests.forEach(function (request) { list.append(item(request)); });
                    var count = list.children().length;
                    var upgrades = list.find('.badge-success').length;
                    modal.find('[data-alert-title]').text(count === 1
                        ? (upgrades ? 'New upgrade request' : 'New subscription request')
                        : count + ' new plan requests');
                    modal.modal('show');
                    chime();
                }

                $(function () {
                    announce(@json($newPlanRequests));
                    // Check for new requests while the page stays open.
                    setInterval(function () {
                        if (document.hidden) { return; }
                        $.getJSON(@json(route('administrator.plan-requests.unannounced')))
                            .done(function (data) { announce(data.requests); });
                    }, 30000);
                });
            })();
        </script>
        @endpush
    @endif
@endauth
