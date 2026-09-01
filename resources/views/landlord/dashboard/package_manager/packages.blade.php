@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.package_management') }} - SheraziPOS Landlord
@endsection

@push('css')
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{ __('file.title.package_management') }}</h4>
            <p class="mb-0 text-muted">{{ __('file.title.package_management_desc') }}</p>
        </div>
        <div>
            <a href="{{ route('landlord.packages.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>
                {{ __('file.button.create') }} {{ __('file.package') }}</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <table class="table nowrap responsive display" id="packages_table">
                        <thead>
                            <tr>
                                <th>{{ __('file.table.no') }}.</th>
                                <th>{{ __('file.package') }}</th>
                                <th>{{ __('file.table.description') }}</th>
                                <th>{{ __('file.table.price') }}</th>
                                <th>{{ __('file.table.status') }}</th>
                                <th class="text-end">{{ __('file.table.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($packages as $package)
                                <tr style="{{$package->is_active ? '' : 'opacity: 0.6'}}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <img src="{{ !empty($package->image) ? asset('storage/'.$package->image) : asset('images/preview_image.png') }}"
                                             alt="{{ $package->name }}"
                                             class="img-thumbnail me-2" style="max-height: 50px;">
                                        {{ $package->name }}
                                    </td>
                                    <td>{{ Str::limit($package->description, 50, '...') }}</td>
                                    <td>
                                        @foreach ($package->pricing as $pricing)
                                            <span>{{ $pricing->type }} : {{ $pricing->price }}</span>
                                            @if (!$loop->last)
                                                <br>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td>
                                        <form action="{{ route('landlord.packages.updateStatus', $package->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="toggle_status_{{ $package->id }}" {{ $package->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                                                <label class="form-check-label" for="toggle_status_{{ $package->id }}">
                                                    {{ $package->is_active ? __('file.table.active') : __('file.table.inactive') }}
                                                </label>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end">
                                            @if ($package->is_active)
                                                <a href="{{ route('landlord.packages.edit', $package->id) }}"
                                                    class="btn btn-primary me-2"><i class="fa-solid fa-pen me-1"></i></a>
                                            @endif
                                            <form action="{{ route('landlord.packages.destroy', $package->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger delete_btn"><i
                                                        class="fa-solid fa-trash me-1"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">
                                        {{ __('file.table.no_data_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


@endsection

@section('modals')
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            $('.delete_btn').on('click', function(e) {
                e.preventDefault();
                let form = $(this).closest('form');
                let name = $(this).data('name');

                console.log(form);

                // Optional - শুধু ইউজারকে দেখানোর জন্য
                $('#deleteConfirmModal .modal-body p').text(
                    name ? `Are you sure you want to delete "${name}"?` :
                    'Are you sure you want to delete this?'
                );

                $('#deleteConfirmModal').modal('show');

                $('#deleteConfirm').off('click').on('click', function() {
                    $(this).prop('disabled', true).text("{{ __('file.button.deleting') }}...");
                    form.submit();
                });
            });
        })
    </script>
@endpush
