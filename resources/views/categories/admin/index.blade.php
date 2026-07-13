@extends('adminlte::page')

@section('title', 'Manage Categories')

@section('content_header')
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1><i class="fas fa-tags mr-2"></i>Manage Categories</h1>
            <p class="text-muted mb-0" style="font-size:0.9rem;">
                Set the <strong>slug</strong> and <strong>promo card</strong> content for each service category. These are used on the frontend navigation and service listing pages.
            </p>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        @foreach($categories as $category)
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm" style="border-radius:12px;overflow:hidden;border:none;">
                <div class="card-header d-flex align-items-center justify-content-between"
                     style="background:linear-gradient(135deg,#2a1774,#401ce6);color:white;padding:16px 20px;">
                    <div>
                        <h5 class="mb-0 font-weight-bold">{{ $category->name }}</h5>
                        <small style="opacity:0.75;">ID: {{ $category->id }}</small>
                    </div>
                    @if($category->slug)
                        <span style="background:rgba(255,255,255,0.15);padding:4px 14px;border-radius:50px;font-size:0.78rem;font-weight:600;letter-spacing:0.5px;">
                            /{{ $category->slug }}
                        </span>
                    @else
                        <span style="background:rgba(255,0,0,0.2);padding:4px 14px;border-radius:50px;font-size:0.78rem;color:#ffcccc;">
                            No slug set
                        </span>
                    @endif
                </div>

                <div class="card-body" style="padding:20px;">
                    <form class="category-update-form" data-id="{{ $category->id }}">
                        @csrf

                        {{-- Slug --}}
                        <div class="form-group mb-3">
                            <label class="font-weight-bold" style="font-size:0.85rem;color:#374151;">
                                <i class="fas fa-link mr-1 text-primary"></i> URL Slug
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="font-size:0.8rem;background:#f1f3f4;color:#6c757d;">/serviceslisting/</span>
                                </div>
                                <input type="text" class="form-control" name="slug"
                                       value="{{ $category->slug }}"
                                       placeholder="e.g. pregnancy-scans"
                                       style="font-size:0.88rem;">
                            </div>
                            <small class="text-muted">Leave blank to auto-generate from category name.</small>
                        </div>

                        <hr style="border-color:#f0f0f0;margin:16px 0;">
                        <div class="mb-2" style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#9ca3af;">
                            <i class="fas fa-ad mr-1"></i> Promo Card (shown in navigation mega menu)
                        </div>

                        {{-- Promo Title --}}
                        <div class="form-group mb-2">
                            <label class="text-muted" style="font-size:0.8rem;">Promo Title</label>
                            <input type="text" class="form-control form-control-sm" name="promo_title"
                                   value="{{ $category->promo_title }}"
                                   placeholder="e.g. Not sure which scan?">
                        </div>

                        {{-- Promo Description --}}
                        <div class="form-group mb-2">
                            <label class="text-muted" style="font-size:0.8rem;">Promo Description</label>
                            <textarea class="form-control form-control-sm" name="promo_description"
                                      rows="2" placeholder="Short description text...">{{ $category->promo_description }}</textarea>
                        </div>

                        <div class="row">
                            {{-- Promo Link Text --}}
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label class="text-muted" style="font-size:0.8rem;">Link Text</label>
                                    <input type="text" class="form-control form-control-sm" name="promo_link_text"
                                           value="{{ $category->promo_link_text }}"
                                           placeholder="e.g. Find my scan →">
                                </div>
                            </div>
                            {{-- Promo BG Type --}}
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label class="text-muted" style="font-size:0.8rem;">Card Style</label>
                                    <select class="form-control form-control-sm" name="promo_bg_type">
                                        <option value="pearl" {{ $category->promo_bg_type === 'pearl' ? 'selected' : '' }}>Pearl (Light)</option>
                                        <option value="zinc" {{ $category->promo_bg_type === 'zinc' ? 'selected' : '' }}>Zinc (Neutral)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Promo Link Href --}}
                        <div class="form-group mb-3">
                            <label class="text-muted" style="font-size:0.8rem;">Link URL</label>
                            <input type="text" class="form-control form-control-sm" name="promo_link_href"
                                   value="{{ $category->promo_link_href }}"
                                   placeholder="e.g. /serviceslisting/pregnancy-scans#scan-calculator">
                        </div>

                        <div class="update-feedback text-success mb-2" style="display:none;font-size:0.85rem;">
                            <i class="fas fa-check-circle mr-1"></i> Saved successfully!
                        </div>
                        <div class="update-error text-danger mb-2" style="display:none;font-size:0.85rem;"></div>

                        <button type="submit" class="btn btn-block font-weight-bold"
                                style="background:linear-gradient(135deg,#2a1774,#401ce6);color:white;border:none;border-radius:8px;padding:10px;">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@stop

@section('css')
<style>
    .card { transition: box-shadow 0.2s ease; }
    .card:hover { box-shadow: 0 8px 30px rgba(64,28,230,0.12) !important; }
</style>
@stop

@section('js')
<script>
$(document).ready(function () {
    $('.category-update-form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const catId = form.data('id');
        const feedback = form.find('.update-feedback');
        const errorEl = form.find('.update-error');
        const btn = form.find('button[type="submit"]');

        feedback.hide();
        errorEl.hide();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');

        $.ajax({
            url: '/admin/categories/' + catId,
            method: 'PUT',
            data: form.serialize() + '&_token={{ csrf_token() }}',
            success: function (res) {
                if (res.success) {
                    feedback.show();
                    setTimeout(function () { feedback.fadeOut(); }, 3000);
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                errorEl.text(msg).show();
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Save Changes');
            }
        });
    });
});
</script>
@stop
