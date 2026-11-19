@extends('adminlte::page')

@section('title', 'Add Page SEO')

@section('content_header')
    <div class="row align-items-center mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark font-weight-bold">
                <i class="fas fa-plus-circle text-success mr-2"></i>Add Page SEO
            </h1>
            <small class="text-muted">Configure SEO meta tags, social preview, and indexing for a specific page.</small>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('page_seos.admin.index') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Page SEO List
            </a>
        </div>
    </div>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h5><i class="icon fas fa-ban mr-1"></i> Please correct the errors below:</h5>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form action="{{ route('page_seos.admin.store') }}" method="POST" enctype="multipart/form-data" id="pageSeoForm">
        @csrf
        <div class="row">
            <!-- Left Column: Main Page & Meta Information -->
            <div class="col-lg-8">
                <!-- Section 1: Page Details -->
                <div class="card card-outline card-primary shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold">
                            <i class="fas fa-file-alt text-primary mr-2"></i>Page Details & Content
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="page_name" class="font-weight-bold">
                                        Page Name / Identifier <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('page_name') is-invalid @enderror" 
                                           id="page_name" name="page_name" value="{{ old('page_name') }}" 
                                           placeholder="e.g. Home, About Us, Services, or /services/blood-test" required>
                                    <small class="form-text text-muted">
                                        Unique identifier or route for this page.
                                    </small>
                                    @error('page_name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title" class="font-weight-bold">Page Title (H1 / Heading)</label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title') }}" 
                                           placeholder="e.g. Comprehensive Health Assessment & Blood Testing">
                                    <small class="form-text text-muted">Primary page title or headline displayed on the page.</small>
                                    @error('title')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="font-weight-bold">Page Description / Content</label>
                            <textarea class="form-control summernote @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="5" 
                                      placeholder="Summary of this page's content and services">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Used as on-page description / content and fallback for meta descriptions.</small>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="image" class="font-weight-bold">Featured / OG Share Image</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('image') is-invalid @enderror" 
                                       id="image" name="image" accept="image/*">
                                <label class="custom-file-label" for="image">Choose image file...</label>
                            </div>
                            <small class="form-text text-muted">
                                Recommended: 1200x630 px (PNG, JPG, WebP - max 3MB). Used for social media cards.
                            </small>
                            @error('image')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror

                            <!-- Image Preview Area -->
                            <div id="imagePreviewContainer" class="mt-3 d-none">
                                <div class="p-2 border rounded bg-light d-inline-block">
                                    <img id="imagePreview" src="#" alt="Image Preview" style="max-height: 180px; max-width: 100%; object-fit: cover; border-radius: 4px;">
                                    <div class="mt-1 small text-muted text-center" id="imagePreviewName"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Core SEO Meta Tags -->
                <div class="card card-outline card-success shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold">
                            <i class="fas fa-search text-success mr-2"></i>Search Engine Meta Tags
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Meta Title -->
                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="meta_title" class="font-weight-bold mb-1">
                                    Meta Title
                                </label>
                                <span class="badge badge-light border" id="metaTitleCount">0 / 60 characters</span>
                            </div>
                            <input type="text" class="form-control @error('meta_title') is-invalid @enderror" 
                                   id="meta_title" name="meta_title" value="{{ old('meta_title') }}" 
                                   placeholder="e.g. Expert Health Services & Private Blood Tests in London | Insight Health" maxlength="255">
                            <small class="form-text text-muted">Optimal length: 50–60 characters. Appears as the clickable headline in Google search results.</small>
                            @error('meta_title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Meta Description -->
                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="meta_description" class="font-weight-bold mb-1">
                                    Meta Description
                                </label>
                                <span class="badge badge-light border" id="metaDescCount">0 / 160 characters</span>
                            </div>
                            <textarea class="form-control @error('meta_description') is-invalid @enderror" 
                                      id="meta_description" name="meta_description" rows="3" 
                                      placeholder="Brief and compelling summary of the page for search engine results..." maxlength="500">{{ old('meta_description') }}</textarea>
                            <small class="form-text text-muted">Optimal length: 150–160 characters. Appears under the title in Google search results.</small>
                            @error('meta_description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Meta Keywords & Canonical URL -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meta_keywords" class="font-weight-bold">Meta Keywords</label>
                                    <input type="text" class="form-control @error('meta_keywords') is-invalid @enderror" 
                                           id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords') }}" 
                                           placeholder="e.g. blood test, clinic, health assessment, private gp" maxlength="500">
                                    <small class="form-text text-muted">Comma-separated target keywords.</small>
                                    @error('meta_keywords')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="canonical_url" class="font-weight-bold">
                                        Canonical URL
                                    </label>
                                    <input type="url" class="form-control @error('canonical_url') is-invalid @enderror" 
                                           id="canonical_url" name="canonical_url" value="{{ old('canonical_url') }}" 
                                           placeholder="https://insighthealthservices.co.uk/services" maxlength="500">
                                    <small class="form-text text-muted">Prevents duplicate content penalties by indicating the primary canonical URL.</small>
                                    @error('canonical_url')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Advanced SEO Suggestions (Collapsible) -->
                <div class="card card-outline card-secondary shadow-sm mb-4">
                    <div class="card-header cursor-pointer" data-toggle="collapse" data-target="#advancedSeoBody" aria-expanded="false" style="cursor: pointer;">
                        <h3 class="card-title font-weight-bold">
                            <i class="fas fa-sliders-h text-info mr-2"></i>Advanced SEO & Open Graph (Recommended Suggestions)
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool"><i class="fas fa-chevron-down"></i></button>
                        </div>
                    </div>
                    <div class="collapse" id="advancedSeoBody">
                        <div class="card-body bg-light">
                            <div class="alert alert-info py-2 px-3 small">
                                <i class="fas fa-info-circle mr-1"></i> These fields are optional suggestions. If left blank, they will automatically fall back to your Meta Title, Meta Description, and default indexing.
                            </div>

                            <div class="form-group">
                                <label for="robots" class="font-weight-bold">Robots Meta Directive (Search Engine Indexing)</label>
                                <select class="form-control" id="robots" name="robots">
                                    <option value="index, follow" {{ old('robots', 'index, follow') == 'index, follow' ? 'selected' : '' }}>index, follow (Standard: Search engines index this page and follow its links)</option>
                                    <option value="noindex, follow" {{ old('robots') == 'noindex, follow' ? 'selected' : '' }}>noindex, follow (Hide this page from search results, but follow links)</option>
                                    <option value="noindex, nofollow" {{ old('robots') == 'noindex, nofollow' ? 'selected' : '' }}>noindex, nofollow (Complete block: Do not index or follow)</option>
                                    <option value="index, nofollow" {{ old('robots') == 'index, nofollow' ? 'selected' : '' }}>index, nofollow (Index page, do not follow outbound links)</option>
                                </select>
                                <small class="form-text text-muted">Controls whether Google and other search engines index this page.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="og_title" class="font-weight-bold">Open Graph Title (Social Media)</label>
                                        <input type="text" class="form-control" id="og_title" name="og_title" value="{{ old('og_title') }}" placeholder="Custom title for Facebook / WhatsApp / LinkedIn" maxlength="255">
                                        <small class="form-text text-muted">Defaults to Meta Title if blank.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="og_description" class="font-weight-bold">Open Graph Description</label>
                                        <textarea class="form-control" id="og_description" name="og_description" rows="2" placeholder="Custom description for social share cards">{{ old('og_description') }}</textarea>
                                        <small class="form-text text-muted">Defaults to Meta Description if blank.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label for="schema_markup" class="font-weight-bold">JSON-LD Schema Markup (Structured Data)</label>
                                <textarea class="form-control font-monospace" id="schema_markup" name="schema_markup" rows="4" placeholder='<script type="application/ld+json">
{
  "&#64;context": "https://schema.org",
  "&#64;type": "MedicalBusiness",
  "name": "Insight Health Services"
}
</script>'>{{ old('schema_markup') }}</textarea>
                                <small class="form-text text-muted">Structured schema markup for Google Rich Snippets (e.g. MedicalClinic, FAQ, LocalBusiness).</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Card -->
                <div class="card shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <a href="{{ route('page_seos.admin.index') }}" class="btn btn-default">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success px-4 font-weight-bold shadow-sm">
                            <i class="fas fa-save mr-2"></i> Save Page SEO
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Google SERP Simulator -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 20px; z-index: 10;">
                    <!-- Google Preview Card -->
                    <div class="card card-outline card-info shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h3 class="card-title font-weight-bold text-dark">
                                <i class="fab fa-google text-danger mr-2"></i>Google SERP Preview
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-info">Live Simulation</span>
                            </div>
                        </div>
                        <div class="card-body p-3" style="background-color: #ffffff; border-radius: 0 0 4px 4px;">
                            <div class="p-3 border rounded" style="font-family: Arial, sans-serif; background-color: #fff; box-shadow: 0 1px 6px rgba(32,33,36,0.15);">
                                <!-- Google URL breadcrumb -->
                                <div class="d-flex align-items-center mb-1 text-truncate">
                                    <span class="badge badge-light border rounded-circle mr-1" style="width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">🌐</span>
                                    <span style="font-size: 12px; color: #202124;" id="previewUrl">https://insighthealthservices.co.uk</span>
                                </div>
                                <!-- Google Title -->
                                <div id="previewTitle" style="color: #1a0dab; font-size: 18px; line-height: 1.3; font-weight: 400; cursor: pointer; text-decoration: none; word-break: break-word;">
                                    Page Title will appear here
                                </div>
                                <!-- Google Snippet -->
                                <div id="previewDesc" class="mt-1" style="color: #4d5156; font-size: 13px; line-height: 1.5; word-break: break-word;">
                                    Provide a meta description to see how this page snippet will appear in Google search results.
                                </div>
                            </div>
                            <div class="mt-2 text-muted small text-center">
                                <i class="fas fa-info-circle mr-1"></i> Preview dynamically updates as you type.
                            </div>
                        </div>
                    </div>

                    <!-- SEO Quick Checklist Card -->
                    <div class="card card-outline card-secondary shadow-sm">
                        <div class="card-header bg-white">
                            <h3 class="card-title font-weight-bold text-dark">
                                <i class="fas fa-check-double text-success mr-2"></i>SEO Optimization Checklist
                            </h3>
                        </div>
                        <div class="card-body p-3">
                            <ul class="list-unstyled mb-0" id="seoChecklist">
                                <li class="mb-2" id="chk-page">
                                    <i class="fas fa-times-circle text-danger mr-2"></i> Page identifier specified
                                </li>
                                <li class="mb-2" id="chk-title">
                                    <i class="fas fa-times-circle text-danger mr-2"></i> Meta title added (50-60 chars)
                                </li>
                                <li class="mb-2" id="chk-desc">
                                    <i class="fas fa-times-circle text-danger mr-2"></i> Meta description added (120-160 chars)
                                </li>
                                <li class="mb-2" id="chk-canonical">
                                    <i class="fas fa-minus-circle text-muted mr-2"></i> Canonical URL configured
                                </li>
                                <li class="mb-0" id="chk-image">
                                    <i class="fas fa-minus-circle text-muted mr-2"></i> Featured / Social image uploaded
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.css" rel="stylesheet">
<style>
    .font-monospace {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.85rem;
    }
    .custom-file-label::after {
        content: "Browse";
    }
    .note-editor.note-frame {
        border: 1px solid #ced4da;
        border-radius: .25rem;
    }
    .note-modal {
        position: fixed !important;
        top: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        left: 0 !important;
        z-index: 1070 !important;
    }
    .note-modal-backdrop {
        position: fixed !important;
        top: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        left: 0 !important;
        z-index: 1065 !important;
        background-color: rgba(0, 0, 0, 0.5) !important;
    }
    .note-modal .note-modal-content {
        position: relative;
        z-index: 1071 !important;
        background: #fff;
    }
    .note-modal input.note-input {
        background-color: #fff !important;
        cursor: text !important;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>

    $(document).ready(function() {
        // Strip HTML helper
        function stripHtml(html) {
            if (!html) return '';
            let tmp = document.createElement("DIV");
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || "";
        }

        // Initialize Summernote
        $('#description').summernote({
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onChange: function(contents) {
                    $('#description').val(contents);
                    updatePreview();
                }
            }
        });

        // Sync Summernote before submit
        $('#pageSeoForm').on('submit', function() {
            if ($('#description').data('summernote')) {
                var code = $('#description').summernote('code');
                $('#description').val(code === '<p><br></p>' ? '' : code);
            }
        });
        // Image preview
        $('#image').on('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                $(this).next('.custom-file-label').text(file.name);
                let reader = new FileReader();
                reader.onload = function(event) {
                    $('#imagePreview').attr('src', event.target.result);
                    $('#imagePreviewName').text(file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)');
                    $('#imagePreviewContainer').removeClass('d-none');
                };
                reader.readAsDataURL(file);
                updateChecklist();
            }
        });

        // Live Counters and Preview
        function updatePreview() {
            let page = $('#page_name').val().trim();
            let title = $('#title').val().trim();
            let metaTitle = $('#meta_title').val().trim();
            let desc = stripHtml($('#description').val()).trim();
            let metaDesc = $('#meta_description').val().trim();
            let canonical = $('#canonical_url').val().trim();

            let titleLen = metaTitle.length;
            $('#metaTitleCount').text(titleLen + ' / 60 characters');
            if (titleLen >= 50 && titleLen <= 60) {
                $('#metaTitleCount').removeClass('badge-light badge-warning badge-danger').addClass('badge-success');
            } else if (titleLen > 60) {
                $('#metaTitleCount').removeClass('badge-light badge-success badge-warning').addClass('badge-danger');
            } else if (titleLen > 0) {
                $('#metaTitleCount').removeClass('badge-light badge-success badge-danger').addClass('badge-warning');
            } else {
                $('#metaTitleCount').removeClass('badge-success badge-warning badge-danger').addClass('badge-light');
            }

            let descLen = metaDesc.length;
            $('#metaDescCount').text(descLen + ' / 160 characters');
            if (descLen >= 120 && descLen <= 160) {
                $('#metaDescCount').removeClass('badge-light badge-warning badge-danger').addClass('badge-success');
            } else if (descLen > 160) {
                $('#metaDescCount').removeClass('badge-light badge-success badge-warning').addClass('badge-danger');
            } else if (descLen > 0) {
                $('#metaDescCount').removeClass('badge-light badge-success badge-danger').addClass('badge-warning');
            } else {
                $('#metaDescCount').removeClass('badge-success badge-warning badge-danger').addClass('badge-light');
            }

            let displayTitle = metaTitle || title || (page ? page + ' | Insight Health Services' : 'Page Title will appear here');
            $('#previewTitle').text(displayTitle);

            let displayDesc = metaDesc || desc || 'Provide a meta description to see how this page snippet will appear in Google search results.';
            $('#previewDesc').text(displayDesc);

            let displayUrl = canonical || ('https://insighthealthservices.co.uk' + (page ? '/' + encodeURIComponent(page.toLowerCase().replace(/\s+/g, '-')) : ''));
            $('#previewUrl').text(displayUrl);

            updateChecklist();
        }

        function updateChecklist() {
            let page = $('#page_name').val().trim();
            let metaTitle = $('#meta_title').val().trim();
            let metaDesc = $('#meta_description').val().trim();
            let canonical = $('#canonical_url').val().trim();
            let hasImage = $('#image')[0].files.length > 0;

            if (page) {
                $('#chk-page').html('<i class="fas fa-check-circle text-success mr-2"></i> Page identifier specified');
            } else {
                $('#chk-page').html('<i class="fas fa-times-circle text-danger mr-2"></i> Page identifier specified');
            }

            if (metaTitle.length >= 40 && metaTitle.length <= 65) {
                $('#chk-title').html('<i class="fas fa-check-circle text-success mr-2"></i> Meta title optimal (' + metaTitle.length + ' chars)');
            } else if (metaTitle.length > 0) {
                $('#chk-title').html('<i class="fas fa-exclamation-circle text-warning mr-2"></i> Meta title added (' + metaTitle.length + ' chars)');
            } else {
                $('#chk-title').html('<i class="fas fa-times-circle text-danger mr-2"></i> Meta title added (50-60 chars)');
            }

            if (metaDesc.length >= 100 && metaDesc.length <= 165) {
                $('#chk-desc').html('<i class="fas fa-check-circle text-success mr-2"></i> Meta description optimal (' + metaDesc.length + ' chars)');
            } else if (metaDesc.length > 0) {
                $('#chk-desc').html('<i class="fas fa-exclamation-circle text-warning mr-2"></i> Meta description added (' + metaDesc.length + ' chars)');
            } else {
                $('#chk-desc').html('<i class="fas fa-times-circle text-danger mr-2"></i> Meta description added (120-160 chars)');
            }

            if (canonical) {
                $('#chk-canonical').html('<i class="fas fa-check-circle text-success mr-2"></i> Canonical URL configured');
            } else {
                $('#chk-canonical').html('<i class="fas fa-minus-circle text-muted mr-2"></i> Canonical URL configured');
            }

            if (hasImage) {
                $('#chk-image').html('<i class="fas fa-check-circle text-success mr-2"></i> Featured image attached');
            } else {
                $('#chk-image').html('<i class="fas fa-minus-circle text-muted mr-2"></i> Featured image uploaded');
            }
        }

        $('#page_name, #title, #description, #meta_title, #meta_description, #canonical_url').on('input', updatePreview);
        updatePreview();
    });
</script>
@stop
