@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.biller_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.biller_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.biller_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBillerModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.biller') }}
            </a>
        @endslot
    @endcomponent

    {{-- Filter Section --}}
    <div class="row mb-3">
        <div class="col-md-12">
            {{-- Mobile Toggle Button --}}
            <button class="btn btn-outline-primary d-md-none w-100 mb-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterCollapse">
                <i class="fa-solid fa-filter me-2"></i> {{ __('file.field.show_filters') }}
            </button>

            <div class="collapse d-md-block" id="filterCollapse">
                <div class="card border-0 mb-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            {{-- Filter Icon & Title (Desktop Only) --}}
                            <div class="col-auto d-none d-md-flex align-items-center gap-2">
                                <i class="fa-solid fa-filter text-primary"></i>
                                <span class="fw-bold text-secondary">{{ __('file.field.filters') }}:</span>
                            </div>

                            <div class="col-12 col-md-auto" style="min-width: 180px;">
                                <select id="filter-status" data-dt-filter="biller-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_status') }}</option>
                                    <option value="1">{{ __('file.option.active') }}</option>
                                    <option value="0">{{ __('file.option.inactive') }}</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto"
                                    onclick="resetFilters('biller-table')">
                                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('file.button.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable Section --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        {{ $dataTable->table(['class' => 'table table-hover table-striped nowrap w-100']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="createBillerModal" tabindex="-1" aria-labelledby="createBillerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createBillerModalLabel">{{ __('file.button.create') }}
                        {{ __('file.biller') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('billers.store') }}" method="POST" id="createBillerForm"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.billers._form', [
                            'isEdit' => false,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBillerModal" tabindex="-1" aria-labelledby="editBillerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBillerModalLabel">{{ __('file.button.edit') }}
                        {{ __('file.biller') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="editBillerForm" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        @include('backend.billers._form', [
                            'isEdit' => true,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')
    <script>
        $(document).ready(function() {
            handleFormSubmit('#createBillerForm', '#createBillerModal', '#biller-table', false);
            handleFormSubmit('#editBillerForm', '#editBillerModal', '#biller-table', true);
        });

        function editBiller(id) {
            let url = "{{ route('billers.edit', ':biller') }}".replace(':biller', id);
            let $modal = $('#editBillerModal');
            let $form = $('#editBillerForm');

            // ডাটা লোড করার আগে ফর্ম রিসেট এবং এরর মেসেজ পরিষ্কার করা (বেস্ট প্র্যাকটিস)
            $form.trigger('reset');

            $.get(url, function(response) {
                if (response.success) {
                    let biller = response.biller;
                    let updateUrl = "{{ route('billers.update', ':biller') }}".replace(':biller', id);

                    // ১. ফর্ম অ্যাকশন ইউআরএল সেট করা
                    $form.attr('action', updateUrl);

                    // ২. সাধারণ ইনপুট ফিল্ডগুলো পপুলেট করা
                    $form.find('input[name="name"]').val(biller.name);
                    $form.find('input[name="company_name"]').val(biller.company_name);
                    $form.find('input[name="propiter_name"]').val(biller.propiter_name);
                    $form.find('input[name="email"]').val(biller.email);
                    $form.find('input[name="phone"]').val(biller.phone);
                    $form.find('input[name="website_url"]').val(biller.website_url);
                    $form.find('input[name="bin"]').val(biller.bin);
                    $form.find('input[name="meta"]').val(biller.meta);

                    // ৩. টেক্সট এরিয়া (Address, T&C)
                    $form.find('textarea[name="address"]').val(biller.address);
                    $form.find('textarea[name="tnc"]').val(biller.tnc);

                    // ৪. স্ট্যাটাস সুইচ (is_active) হ্যান্ডলিং
                    if (biller.is_active == 1) {
                        $form.find('#is_active').prop('checked', true);
                    } else {
                        $form.find('#is_active').prop('checked', false);
                    }

                    // ৫. লোগো প্রিভিউ আপডেট (আপনার হেল্পার অনুযায়ী URL আনা)
                    // আপনার ট্রেইটে 'logo_url' নামে অ্যাট্রিবিউট থাকলে সেটি ব্যবহার করা ভালো
                    let logoUrl = response.logo_url || "{{ url('images/preview_image.png') }}";
                    $form.find('#edit_logo_preview').attr('src', logoUrl);

                    let $certStatus = $form.find('#edit_certificate_status');
                    $certStatus.empty(); // আগের মেসেজ পরিষ্কার করা

                    if (biller.certificate) {
                        // যদি সার্টিফিকেট থাকে তবে ব্যাজ এবং ভিউ বাটন দেখাবে
                        $certStatus.html(`
                            <span class="badge badge-success-transparent text-success"><i class="fas fa-check"></i> Uploaded</span>
                            <a href="${response.certificate_url}" target="_blank" class="btn btn-xs btn-outline-primary ml-2">
                                <i class="fas fa-eye"></i> View File
                            </a>
                        `);
                    } else {
                        // না থাকলে নোটিশ দেখাবে
                        $certStatus.html('<small class="text-muted"><i class="fas fa-times"></i> No certificate uploaded.</small>');
                    }

                    // ৬. মোডাল দেখানো
                    $modal.modal('show');
                } else {
                    alert('Data not found!');
                }
            }).fail(function() {
                alert('Failed to fetch data from server.');
            });
        }
    </script>
@endpush
