@extends('main')
@section('content')
    <section class="main-content">
        <div class="container-fluid px-3 px-md-4 py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h3 mb-1">پروڈکشن ورکرز</h1>
                    <p class="text-muted mb-0">درزی، کٹنگ ماسٹر اور دوسرے کاریگروں کا کام اور اجرت—ملازم اجازتوں سے الگ۔</p>
                </div>
                <div class="d-flex flex-wrap align-items-center" style="gap:.5rem">
                    <a class="btn btn-outline-primary" href="{{ route('admin.order.total') }}"><i
                            class="fas fa-tools ml-1"></i>ورکشاپ اور پروڈکشن</a>
                    <button class="btn btn-primary" type="button" data-toggle="modal" data-target="#createWorkerModal"><i
                            class="fas fa-plus ml-1"></i>نیا ورکر</button>
                </div>
            </div>
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <form class="card card-body mb-3" method="GET">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-5 mb-md-0"><label for="q">نام یا فون</label><input id="q"
                            name="q" class="form-control" value="{{ $filters['q'] ?? '' }}"
                            placeholder="ورکر تلاش کریں"></div>
                    <div class="form-group col-md-3 mb-md-0"><label for="relationship">تعلق</label><select id="relationship"
                            name="relationship" class="form-control" style="padding-top: 0px;">
                            <option value="">تمام</option>
                            <option value="contractor" @selected(($filters['relationship'] ?? '') === 'contractor')>آزاد کاریگر</option>
                            <option value="employee" @selected(($filters['relationship'] ?? '') === 'employee')>تنخواہ دار ملازم</option>
                        </select></div>
                    <div class="form-group col-md-2 mb-md-0"><label for="status">حالت</label><select id="status"
                            name="status" class="form-control" style="padding-top: 0px;">
                            <option value="">تمام</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>فعال</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>غیر فعال</option>
                        </select></div>
                    <div class="col-md-2"><button class="btn btn-outline-primary" type="submit">فلٹر</button> <a
                            class="btn btn-light" href="{{ route('admin.production-workers.index') }}">صاف</a></div>
                </div>
            </form>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>ورکر</th>
                                <th>کام</th>
                                <th>تعلق</th>
                                <th>موجودہ واجب الادا</th>
                                <th>حالت</th>
                                <th>عمل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($workers as $worker)
                                <tr>
                                    <td><strong>{{ $worker->name }}</strong><br><small
                                            class="text-muted">{{ $worker->phone ?: 'فون درج نہیں' }}</small>
                                        @if ($worker->legacy_tailor_id)
                                            <br><span class="badge badge-info">موجودہ درزی سے منسلک</span>
                                        @endif
                                    </td>
                                    <td>
                                        @forelse($worker->skills as $skill)
                                            <span
                                            class="badge badge-light border ml-1">{{ $skill->name }}</span>@empty<span
                                                class="text-muted">مقرر نہیں</span>
                                        @endforelse
                                    </td>
                                    <td>{{ $worker->relationship_type === 'employee' ? 'تنخواہ دار ملازم' : 'آزاد کاریگر' }}
                                    </td>
                                    <td class="{{ (float) $worker->ledger_balance > 0 ? 'text-danger' : 'text-success' }}">
                                        روپے {{ number_format((float) $worker->ledger_balance, 2) }}</td>
                                    <td><span
                                            class="badge badge-{{ $worker->active ? 'success' : 'secondary' }}">{{ $worker->active ? 'فعال' : 'غیر فعال' }}</span>
                                    </td>
                                    <td><a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('admin.production-workers.show', $worker) }}">کھاتہ اور اجرت</a>
                                    </td>
                            </tr>@empty<tr>
                                    <td colspan="6" class="text-center text-muted py-5">ابھی کوئی پروڈکشن ورکر موجود
                                        نہیں۔</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($workers->hasPages())
                    <div class="card-footer">{{ $workers->links() }}</div>
                @endif
            </div>
        </div>
    </section>

    <div class="modal fade" id="createWorkerModal" tabindex="-1" role="dialog"
        aria-labelledby="createWorkerModalLabel" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header align-items-center">
                    <h5 class="modal-title font-weight-bold" id="createWorkerModalLabel"><i
                            class="fas fa-user-plus text-primary ml-2"></i>نیا پروڈکشن ورکر</h5>
                    <button type="button" class="close mr-auto ml-0" data-dismiss="modal" aria-label="بند کریں">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.production-workers.store') }}">
                    @csrf
                    <input type="hidden" name="_form_context" value="worker_create">
                    <div class="modal-body text-right">
                        <div class="alert alert-info py-2">ورکر کا ریکارڈ صارف اکاؤنٹ اور لاگ اِن اجازتوں سے الگ ہے۔ کم از کم ایک مہارت منتخب کریں۔</div>
                        @include('production-workers._form', ['worker' => null, 'showSubmit' => false])
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button><button class="btn btn-primary" type="submit"><i class="fas fa-save ml-1"></i> معلومات محفوظ کریں</button></div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if(($errors->any() && old('_form_context') === 'worker_create') || request('open') === 'create')
        <script>document.addEventListener('DOMContentLoaded', function () { $('#createWorkerModal').modal('show'); });</script>
    @endif
@endpush
