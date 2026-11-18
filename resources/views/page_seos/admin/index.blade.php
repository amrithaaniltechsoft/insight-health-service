@extends('adminlte::page')

@section('title', 'Page SEO Management')

@section('content')
    <div class="pt-3"></div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" id="successAlert">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <script>
            setTimeout(function() {
                var el = document.getElementById('successAlert');
                if (el) {
                    el.classList.remove('show');
                    setTimeout(function() { el.remove(); }, 150);
                }
            }, 3500);
        </script>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h5><i class="icon fas fa-ban mr-1"></i> Please correct the validation errors:</h5>
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



    <!-- Table Card -->
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title font-weight-bold mb-0" style="font-size: 1.25rem;">
                        <i class="fas fa-globe text-primary mr-2"></i>Page SEO Management
                    </h3>
                </div>
                <button type="button" class="btn btn-success shadow-sm font-weight-bold" data-toggle="modal" data-target="#addPageSeoModal">
                    <i class="fas fa-plus mr-1"></i> Add Page SEO
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover" id="pageSeoTable" style="width: 100%;">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">#</th>
                        <th style="width: 70px;" class="text-center">Image</th>
                        <th style="width: 150px;">Page Name</th>
                        <th>Meta / Page Title</th>
                        <th>Canonical URL</th>
                        <th style="width: 110px;" class="text-center">Indexing</th>
                        <th style="width: 130px;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pageSeos as $index => $seo)
                    <tr>
                        <td class="text-center font-weight-bold">{{ $index + 1 }}</td>
                        <td class="text-center">
                            @if($seo->image_url)
                                <img src="{{ $seo->image_url }}" class="rounded border shadow-sm" style="width: 48px; height: 34px; object-fit: cover;" alt="{{ $seo->page_name }}">
                            @else
                                <span class="badge badge-light border text-muted"><i class="fas fa-image"></i> None</span>
                            @endif
                        </td>
                        <td>
                            <strong class="text-primary font-weight-bold">{{ $seo->page_name }}</strong>
                        </td>
                        <td>
                            <div>{{ $seo->meta_title ?: ($seo->title ?: '—') }}</div>
                            @if($seo->meta_description)
                                <small class="text-muted d-block text-truncate" style="max-width: 320px;">{{ $seo->meta_description }}</small>
                            @endif
                        </td>
                        <td>
                            @if($seo->canonical_url)
                                <a href="{{ $seo->canonical_url }}" target="_blank" class="text-truncate d-inline-block small" style="max-width: 220px;" title="{{ $seo->canonical_url }}">
                                    <i class="fas fa-external-link-alt mr-1"></i>{{ $seo->canonical_url }}
                                </a>
                            @else
                                <span class="badge badge-light text-muted">Auto-generated</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $robots = $seo->robots ?? 'index, follow';
                            @endphp
                            @if(str_contains($robots, 'noindex'))
                                <span class="badge badge-danger">{{ $robots }}</span>
                            @else
                                <span class="badge badge-success">{{ $robots }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-xs btn-info view-seo-btn mr-1" data-id="{{ $seo->id }}" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-xs btn-warning edit-page-seo-btn mr-1" data-id="{{ $seo->id }}" title="Edit in Modal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-xs btn-danger delete-seo-btn" data-id="{{ $seo->id }}" data-page="{{ $seo->page_name }}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Page SEO Slide-In Modal -->
    <div class="modal fade" id="addPageSeoModal" tabindex="-1" role="dialog" aria-labelledby="addPageSeoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100 font-weight-bold" id="addPageSeoModalLabel" style="font-size: 1.4rem;">
                        <i class="fas fa-plus-circle mr-2"></i>Add New Page SEO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('page_seos.admin.store') }}" method="POST" enctype="multipart/form-data" id="addPageSeoForm">
                    @csrf
                    <div class="modal-body p-4" style="max-height: calc(100vh - 180px); overflow-y: auto;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_page_name" class="font-weight-bold" style="color: #495057;">Page Name / Identifier <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_page_name" name="page_name" placeholder="e.g. Home, About Us, Services, /blood-tests" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_title" class="font-weight-bold" style="color: #495057;">Page Title (H1 / Heading)</label>
                                    <input type="text" class="form-control" id="add_title" name="title" placeholder="e.g. Comprehensive Health Assessment">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="add_description" class="font-weight-bold" style="color: #495057;">Page Description / Content</label>
                            <textarea class="form-control" id="add_description" name="description" rows="3" placeholder="Brief summary of this page's content"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="add_image" class="font-weight-bold" style="color: #495057;">Featured / Social Share Image (File)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="add_image" name="image" accept="image/*">
                                <label class="custom-file-label" for="add_image">Choose image file...</label>
                            </div>
                            <small class="form-text text-muted">PNG, JPG, WebP (Max 3MB). Used for social cards.</small>
                            <div id="add_image_preview_container" class="mt-2" style="display: none;">
                                <img id="add_image_preview" src="" alt="Preview" class="border rounded" style="max-height: 90px; max-width: 160px; object-fit: cover;">
                            </div>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-success mb-3"><i class="fas fa-search mr-1"></i> Search Engine Meta Tags</h6>

                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="add_meta_title" class="font-weight-bold" style="color: #495057;">Meta Title</label>
                                <span class="badge badge-light border" id="add_meta_title_count">0 / 60</span>
                            </div>
                            <input type="text" class="form-control" id="add_meta_title" name="meta_title" placeholder="e.g. Private Blood Tests in London | Insight Health" maxlength="255">
                            <small class="form-text text-muted">Recommended: 50–60 characters.</small>
                        </div>

                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="add_meta_description" class="font-weight-bold" style="color: #495057;">Meta Description</label>
                                <span class="badge badge-light border" id="add_meta_desc_count">0 / 160</span>
                            </div>
                            <textarea class="form-control" id="add_meta_description" name="meta_description" rows="3" placeholder="Compelling summary shown in Google search results" maxlength="500"></textarea>
                            <small class="form-text text-muted">Recommended: 120–160 characters.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_meta_keywords" class="font-weight-bold" style="color: #495057;">Meta Keywords</label>
                                    <input type="text" class="form-control" id="add_meta_keywords" name="meta_keywords" placeholder="blood test, clinic, london (comma separated)">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_canonical_url" class="font-weight-bold" style="color: #495057;">Canonical URL</label>
                                    <input type="url" class="form-control" id="add_canonical_url" name="canonical_url" placeholder="https://insighthealthservices.co.uk/services">
                                    <small class="form-text text-muted">Prevents duplicate content issues.</small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-info mb-3"><i class="fas fa-sliders-h mr-1"></i> Suggested Inputs (Advanced SEO)</h6>

                        <div class="form-group">
                            <label for="add_robots" class="font-weight-bold" style="color: #495057;">Robots Indexing Directive</label>
                            <select class="form-control" id="add_robots" name="robots">
                                <option value="index, follow" selected>index, follow (Standard: Allow Google to index and follow)</option>
                                <option value="noindex, follow">noindex, follow (Hide from Google, but follow links)</option>
                                <option value="noindex, nofollow">noindex, nofollow (Complete block: Do not index or follow)</option>
                                <option value="index, nofollow">index, nofollow (Index page, do not follow links)</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_og_title" class="font-weight-bold" style="color: #495057;">Open Graph Title (Social Media)</label>
                                    <input type="text" class="form-control" id="add_og_title" name="og_title" placeholder="Custom title for Facebook / WhatsApp / LinkedIn">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_og_description" class="font-weight-bold" style="color: #495057;">Open Graph Description</label>
                                    <textarea class="form-control" id="add_og_description" name="og_description" rows="2" placeholder="Custom description for social cards"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="add_schema_markup" class="font-weight-bold" style="color: #495057;">JSON-LD Schema Markup (Structured Data)</label>
                            <textarea class="form-control" id="add_schema_markup" name="schema_markup" rows="3" placeholder='<script type="application/ld+json">
{
  "&#64;context": "https://schema.org",
  "&#64;type": "MedicalBusiness",
  "name": "Insight Health Services"
}
</script>'></textarea>
                            <small class="form-text text-muted">Schema markup for Google rich snippets.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success font-weight-bold">
                            <i class="fas fa-save mr-1"></i> Save Page SEO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Page SEO Slide-In Modal -->
    <div class="modal fade" id="editPageSeoModal" tabindex="-1" role="dialog" aria-labelledby="editPageSeoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100 font-weight-bold" id="editPageSeoModalLabel" style="font-size: 1.4rem;">
                        <i class="fas fa-edit mr-2"></i>Edit Page SEO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editPageSeoForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4" style="max-height: calc(100vh - 180px); overflow-y: auto;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_edit_page_name" class="font-weight-bold" style="color: #495057;">Page Name / Identifier <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="modal_edit_page_name" name="page_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_edit_title" class="font-weight-bold" style="color: #495057;">Page Title (H1 / Heading)</label>
                                    <input type="text" class="form-control" id="modal_edit_title" name="title">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="modal_edit_description" class="font-weight-bold" style="color: #495057;">Page Description / Summary</label>
                            <textarea class="form-control" id="modal_edit_description" name="description" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="modal_edit_image" class="font-weight-bold" style="color: #495057;">Featured / Social Share Image (File)</label>
                            <div id="modal_edit_current_image_container" class="mb-2" style="display: none;">
                                <small class="text-muted d-block mb-1">Current Image:</small>
                                <img id="modal_edit_current_image" src="" alt="Current Image" class="border rounded" style="max-height: 90px; max-width: 160px; object-fit: cover;">
                            </div>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="modal_edit_image" name="image" accept="image/*">
                                <label class="custom-file-label" for="modal_edit_image">Choose new image file...</label>
                            </div>
                            <small class="form-text text-muted">Leave empty to keep current image.</small>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-success mb-3"><i class="fas fa-search mr-1"></i> Search Engine Meta Tags</h6>

                        <div class="form-group">
                            <label for="modal_edit_meta_title" class="font-weight-bold" style="color: #495057;">Meta Title</label>
                            <input type="text" class="form-control" id="modal_edit_meta_title" name="meta_title" placeholder="Optimal: 50-60 characters">
                        </div>

                        <div class="form-group">
                            <label for="modal_edit_meta_description" class="font-weight-bold" style="color: #495057;">Meta Description</label>
                            <textarea class="form-control" id="modal_edit_meta_description" name="meta_description" rows="3" placeholder="Optimal: 150-160 characters"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_edit_meta_keywords" class="font-weight-bold" style="color: #495057;">Meta Keywords</label>
                                    <input type="text" class="form-control" id="modal_edit_meta_keywords" name="meta_keywords" placeholder="Comma separated keywords">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_edit_canonical_url" class="font-weight-bold" style="color: #495057;">Canonical URL</label>
                                    <input type="url" class="form-control" id="modal_edit_canonical_url" name="canonical_url" placeholder="https://insighthealthservices.co.uk/page">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-info mb-3"><i class="fas fa-sliders-h mr-1"></i> Suggested Inputs (Advanced SEO)</h6>

                        <div class="form-group">
                            <label for="modal_edit_robots" class="font-weight-bold" style="color: #495057;">Robots Indexing Directive</label>
                            <select class="form-control" id="modal_edit_robots" name="robots">
                                <option value="index, follow">index, follow (Standard)</option>
                                <option value="noindex, follow">noindex, follow</option>
                                <option value="noindex, nofollow">noindex, nofollow</option>
                                <option value="index, nofollow">index, nofollow</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_edit_og_title" class="font-weight-bold" style="color: #495057;">Open Graph Title</label>
                                    <input type="text" class="form-control" id="modal_edit_og_title" name="og_title">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_edit_og_description" class="font-weight-bold" style="color: #495057;">Open Graph Description</label>
                                    <textarea class="form-control" id="modal_edit_og_description" name="og_description" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="modal_edit_schema_markup" class="font-weight-bold" style="color: #495057;">JSON-LD Schema Markup</label>
                            <textarea class="form-control" id="modal_edit_schema_markup" name="schema_markup" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success font-weight-bold">
                            <i class="fas fa-save mr-1"></i> Update Page SEO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View SEO Modal -->
    <div class="modal fade" id="viewSeoModal" tabindex="-1" role="dialog" aria-labelledby="viewSeoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title font-weight-bold" id="viewSeoModalLabel">
                        <i class="fas fa-search mr-2"></i>SEO Details: <span id="modalPageTitle" class="badge badge-light text-primary"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <!-- Google SERP Simulation in Modal -->
                    <div class="card border mb-3 shadow-none">
                        <div class="card-header bg-white py-2">
                            <span class="small font-weight-bold text-muted">
                                <i class="fab fa-google text-danger mr-1"></i> Google Search Result Preview
                            </span>
                        </div>
                        <div class="card-body p-3 bg-white">
                            <div style="font-family: Arial, sans-serif;">
                                <div class="text-truncate mb-1" style="font-size: 12px; color: #202124;">
                                    🌐 <span id="modalGoogleUrl">https://insighthealthservices.co.uk</span>
                                </div>
                                <div id="modalGoogleTitle" style="color: #1a0dab; font-size: 18px; line-height: 1.3; font-weight: 500;">
                                    Page Title
                                </div>
                                <div id="modalGoogleDesc" class="mt-1" style="color: #4d5156; font-size: 13px; line-height: 1.5;">
                                    Meta description goes here...
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media Card Simulation in Modal -->
                    <div class="card border mb-3 shadow-none" id="modalSocialCardSection">
                        <div class="card-header bg-white py-2">
                            <span class="small font-weight-bold text-muted">
                                <i class="fab fa-facebook text-primary mr-1"></i> Social Sharing Card Preview (Facebook, LinkedIn, WhatsApp)
                            </span>
                        </div>
                        <div class="card-body p-3 bg-white">
                            <div class="row align-items-center">
                                <div class="col-md-4 text-center mb-2 mb-md-0">
                                    <img id="modalSocialImage" src="" alt="Social Image" class="img-fluid rounded border" style="max-height: 120px; object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <div class="small text-uppercase text-muted" id="modalSocialDomain">insighthealthservices.co.uk</div>
                                    <div class="font-weight-bold text-dark" id="modalSocialTitle"></div>
                                    <div class="small text-muted text-truncate" id="modalSocialDesc"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Properties Table -->
                    <div class="card border mb-0 shadow-none">
                        <div class="card-header bg-white py-2">
                            <span class="small font-weight-bold text-muted">
                                <i class="fas fa-list-alt text-secondary mr-1"></i> Full Tag Specifications
                            </span>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <tr>
                                    <th style="width: 25%;" class="pl-3">Page Name:</th>
                                    <td id="modalPropPage" class="font-weight-bold text-primary"></td>
                                </tr>
                                <tr>
                                    <th class="pl-3">Page Title (H1):</th>
                                    <td id="modalPropTitle"></td>
                                </tr>
                                <tr>
                                    <th class="pl-3">Meta Title:</th>
                                    <td id="modalPropMetaTitle"></td>
                                </tr>
                                <tr>
                                    <th class="pl-3">Meta Description:</th>
                                    <td id="modalPropMetaDesc"></td>
                                </tr>
                                <tr>
                                    <th class="pl-3">Meta Keywords:</th>
                                    <td id="modalPropMetaKeywords"></td>
                                </tr>
                                <tr>
                                    <th class="pl-3">Canonical URL:</th>
                                    <td id="modalPropCanonical"></td>
                                </tr>
                                <tr>
                                    <th class="pl-3">Robots Directive:</th>
                                    <td id="modalPropRobots"></td>
                                </tr>
                                <tr>
                                    <th class="pl-3">Schema Markup:</th>
                                    <td>
                                        <pre id="modalPropSchema" class="bg-light p-2 rounded mb-0 text-muted" style="max-height: 120px; overflow-y: auto; font-size: 11px;"></pre>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Delete Action -->
    <form id="deleteSeoForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<style>
    #pageSeoTable tbody tr td {
        vertical-align: middle;
    }
    .modal-right {
        margin-right: 0;
        margin-left: auto;
        margin-top: 0;
    }
    @media (min-width: 576px) {
        .modal-right {
            margin-right: 0;
            margin-left: auto;
            margin-top: 0;
        }
    }
</style>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('#pageSeoTable').DataTable({
                "pageLength": 10,
                "order": [[2, "asc"]],
                "columnDefs": [
                    { "orderable": false, "targets": [0, 1, 6] }
                ],
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search pages...",
                    "emptyTable": "No pages added yet."
                }
            });
        }

        // Custom file input label update & preview for Add Modal
        $('#add_image').on('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                $(this).next('.custom-file-label').text(file.name);
                let reader = new FileReader();
                reader.onload = function(event) {
                    $('#add_image_preview').attr('src', event.target.result);
                    $('#add_image_preview_container').show();
                };
                reader.readAsDataURL(file);
            }
        });

        // Custom file input label update for Edit Modal
        $('#modal_edit_image').on('change', function(e) {
            let fileName = e.target.files[0]?.name || 'Choose file';
            $(this).next('.custom-file-label').text(fileName);
        });

        // Add Modal Character Counters
        $('#add_meta_title').on('input', function() {
            let len = $(this).val().length;
            $('#add_meta_title_count').text(len + ' / 60');
            if (len >= 50 && len <= 60) {
                $('#add_meta_title_count').removeClass('badge-light badge-danger').addClass('badge-success');
            } else if (len > 60) {
                $('#add_meta_title_count').removeClass('badge-light badge-success').addClass('badge-danger');
            } else {
                $('#add_meta_title_count').removeClass('badge-success badge-danger').addClass('badge-light');
            }
        });

        $('#add_meta_description').on('input', function() {
            let len = $(this).val().length;
            $('#add_meta_desc_count').text(len + ' / 160');
            if (len >= 120 && len <= 160) {
                $('#add_meta_desc_count').removeClass('badge-light badge-danger').addClass('badge-success');
            } else if (len > 160) {
                $('#add_meta_desc_count').removeClass('badge-light badge-success').addClass('badge-danger');
            } else {
                $('#add_meta_desc_count').removeClass('badge-success badge-danger').addClass('badge-light');
            }
        });

        // Edit Modal Opening
        $(document).on('click', '.edit-page-seo-btn', function() {
            let seoId = $(this).data('id');
            $.ajax({
                url: '/admin/page-seos/' + seoId + '/show',
                type: 'GET',
                success: function(data) {
                    $('#editPageSeoForm').attr('action', '/admin/page-seos/' + data.id);
                    $('#modal_edit_page_name').val(data.page_name);
                    $('#modal_edit_title').val(data.title || '');
                    $('#modal_edit_description').val(data.description || '');
                    $('#modal_edit_meta_title').val(data.meta_title || '');
                    $('#modal_edit_meta_description').val(data.meta_description || '');
                    $('#modal_edit_meta_keywords').val(data.meta_keywords || '');
                    $('#modal_edit_canonical_url').val(data.canonical_url || '');
                    $('#modal_edit_robots').val(data.robots || 'index, follow');
                    $('#modal_edit_og_title').val(data.og_title || '');
                    $('#modal_edit_og_description').val(data.og_description || '');
                    $('#modal_edit_schema_markup').val(data.schema_markup || '');

                    if (data.image) {
                        $('#modal_edit_current_image').attr('src', data.image);
                        $('#modal_edit_current_image_container').show();
                    } else {
                        $('#modal_edit_current_image_container').hide();
                    }

                    $('#editPageSeoModal').modal('show');
                },
                error: function() {
                    alert('Failed to load page SEO data.');
                }
            });
        });

        // View Details Modal
        $(document).on('click', '.view-seo-btn', function() {
            let seoId = $(this).data('id');
            $.ajax({
                url: '/admin/page-seos/' + seoId + '/show',
                type: 'GET',
                success: function(seo) {
                    $('#modalPageTitle').text(seo.page_name);
                    $('#modalGoogleUrl').text(seo.canonical_url || ('https://insighthealthservices.co.uk/' + encodeURIComponent(seo.page_name.toLowerCase().replace(/\s+/g, '-'))));
                    $('#modalGoogleTitle').text(seo.meta_title || seo.title || seo.page_name);
                    $('#modalGoogleDesc').text(seo.meta_description || seo.description || 'No meta description provided.');

                    if (seo.image) {
                        $('#modalSocialImage').attr('src', seo.image).show();
                        $('#modalSocialCardSection').show();
                    } else {
                        $('#modalSocialCardSection').hide();
                    }

                    $('#modalSocialTitle').text(seo.og_title || seo.meta_title || seo.title || seo.page_name);
                    $('#modalSocialDesc').text(seo.og_description || seo.meta_description || seo.description || '');

                    $('#modalPropPage').text(seo.page_name);
                    $('#modalPropTitle').text(seo.title || 'N/A');
                    $('#modalPropMetaTitle').text(seo.meta_title || 'N/A');
                    $('#modalPropMetaDesc').text(seo.meta_description || 'N/A');
                    $('#modalPropMetaKeywords').text(seo.meta_keywords || 'N/A');
                    $('#modalPropCanonical').text(seo.canonical_url || 'Auto (Default)');
                    $('#modalPropRobots').html('<span class="badge badge-info">' + (seo.robots || 'index, follow') + '</span>');
                    
                    if (seo.schema_markup) {
                        $('#modalPropSchema').text(seo.schema_markup).show();
                    } else {
                        $('#modalPropSchema').text('None configured.').show();
                    }

                    $('#viewSeoModal').modal('show');
                },
                error: function() {
                    alert('Failed to load SEO details.');
                }
            });
        });

        // Delete Button
        $(document).on('click', '.delete-seo-btn', function() {
            let seoId = $(this).data('id');
            let pageName = $(this).data('page');
            
            if (confirm('Are you sure you want to delete the SEO configuration for page "' + pageName + '"?')) {
                let form = $('#deleteSeoForm');
                form.attr('action', '/admin/page-seos/' + seoId);
                form.submit();
            }
        });
    });
</script>
@stop
