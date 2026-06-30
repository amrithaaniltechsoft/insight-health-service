@extends('adminlte::page')

@section('title', $category->name . ' - Services')

@section('content_header')
    <div class="row">
        <div class="col-md-8">
            <h1>{{ $category->name }} - Services</h1>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('services.admin.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to Categories
            </a>
            {{-- <button type="button" class="btn btn-info" data-toggle="modal" data-target="#importServiceModal" title="Import from Excel">
                <i class="fas fa-file-excel mr-2"></i>Import Excel
            </button> --}}
            <button type="button" class="btn" style="background-color: #28a745; color: white; border: none;" data-toggle="modal" data-target="#addServiceModal">
                <i class="fas fa-plus mr-2"></i>Add
            </button>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $category->name }} Services</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover" id="categoryServicesTable">
                <thead>
                    <tr>
                        <th>SI</th>
                        @if($category->id == 4)<th>Title</th>@endif
                        <th>{{ $category->id == 4 ? 'Subcategory' : 'Title' }}</th>
                        <th>Price</th>
                        @if($category->id != 4)<th>Image</th>@endif
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    {{-- <!-- Import Excel Modal -->
    <div class="modal fade" id="importServiceModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:500px;">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background-color: #17a2b8; color: white; border:none;">
                    <h5 class="modal-title w-100 text-center" style="font-size:1.3rem;">
                        <i class="fas fa-file-excel mr-2"></i>Import Services from Excel
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white;position:absolute;right:1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('services.admin.import', $category->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body" style="padding:24px 28px;">
                        @if($category->id == 4)
                        <div class="mb-3 p-3" style="background:#f8f9fa;border-radius:8px;font-size:0.85rem;">
                            <strong style="color:#17a2b8;">Excel columns (header row):</strong><br>
                            Category, Sub-Category, Title, Rate, Description, Package Include, Turn Around Time
                        </div>
                        @else
                        <div class="mb-3 p-3" style="background:#f8f9fa;border-radius:8px;font-size:0.85rem;">
                            <strong style="color:#17a2b8;">Excel columns order:</strong><br>
                            1. Service Name <span class="text-danger">*</span><br>
                            2. Price<br>
                            3. Appointment<br>
                            4. Service Overview<br>
                            5. FAQ Link<br>
                            6. Video Link<br>
                            7. Description<br>
                            8. Description 2
                        </div>
                        @endif
                        <div class="form-group">
                            <label style="color: #6c757d; font-size: 14px; font-weight: 600 !important;">Choose Excel File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="file" accept=".xlsx,.xls,.csv" required>
                                <label class="custom-file-label">Choose file</label>
                            </div>
                        </div>
                        <small class="text-muted">First row should be column headers (will be skipped).</small>
                    </div>
                    <div class="modal-footer" style="border-top:none;padding:0 28px 20px;">
                        <button type="submit" class="btn btn-block" style="background-color:#17a2b8;color:#fff;font-weight:600;border-radius:8px;">
                            <i class="fas fa-upload mr-2"></i>Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div> --}}

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" role="dialog" aria-labelledby="addServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="addServiceModalLabel" style="font-size: 1.5rem;">
                        @if($category->id == 4)Add Blood test
                        @elseif($category->id == 1)Add Pregnancy Scans
                        @elseif($category->id == 2)Add Diagnostics
                        @elseif($category->id == 3)Add Physiotherapy
                        @elseif($category->id == 5)Add Other Diagnostics
                        @else Add Service
                        @endif
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('services.admin.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="category_id" value="{{ $category->id }}">
                    <div class="modal-body">
                        <div class="row">
                            @if($category->id == 4)
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Subcategory <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-control" name="sub_category_id" required id="addSubCategorySelect">
                                            <option value="">-- Select Subcategory --</option>
                                            @foreach($subCategories as $sub)
                                            <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addSubCategoryModal" title="Add Subcategory">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#manageSubCategoryModal" title="Manage Subcategories">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="title" placeholder="Enter title" required>
                                </div>
                            </div>
                            @else
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="service_name" placeholder="Enter title" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Price <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" name="price" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Appointment <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="appointment" placeholder="e.g. Call to book" required>
                                </div>
                            </div>
                            @endif
                        </div>
                        @if($category->id == 4)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Rate <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" name="price" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Turn Around Time <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="turn_around_time" placeholder="e.g. 24 hours" required>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="form-group">
                            <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Service Overview <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="service_overview" rows="3" placeholder="Brief overview..." required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">FAQ Link</label>
                                    <input type="text" class="form-control" name="faq_link" placeholder="FAQ URL or slug">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Video Link</label>
                                    <input type="text" class="form-control" name="video_link" placeholder="YouTube/Vimeo URL">
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="form-group">
                            <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control summernote" name="description1" rows="4" required></textarea>
                        </div>
                        @if($category->id == 4)
                        <div class="form-group">
                            <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Package Include <span class="text-danger">*</span></label>
                            <textarea class="form-control summernote" name="package_include" rows="4" required></textarea>
                        </div>
                        @else
                        <div class="form-group">
                            <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description 2</label>
                            <textarea class="form-control summernote" name="description2" rows="4"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Image</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" name="image" accept="image/*">
                                            <label class="custom-file-label">Choose file</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>
                            @if($category->id == 4)Save Blood test
                            @elseif($category->id == 1)Save Pregnancy Scans
                            @elseif($category->id == 2)Save Diagnostics
                            @elseif($category->id == 3)Save Physiotherapy
                            @elseif($category->id == 5)Save Other Diagnostics
                            @else Save Service
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Subcategory Modal -->
    @if($category->id == 4)
    <div class="modal fade" id="addSubCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:450px;">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background-color: #28a745; color: white; border:none;">
                    <h5 class="modal-title w-100 text-center" style="font-size:1.3rem;">Add Subcategory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white;position:absolute;right:1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding:24px 28px;">
                    <div class="form-group">
                        <label style="color: #6c757d; font-size: 14px; font-weight: 600 !important;">Subcategory Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="newSubCategoryName" placeholder="Enter subcategory name">
                    </div>
                    <div id="addSubCategoryError" class="text-danger mb-2" style="display:none;"></div>
                    <button type="button" class="btn btn-block" id="saveSubCategoryBtn" style="background-color:#28a745;color:#fff;font-weight:600;border-radius:8px;">
                        <i class="fas fa-save mr-2"></i>Save Subcategory
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Manage Subcategories Modal -->
    @if($category->id == 4)
    <div class="modal fade" id="manageSubCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:550px;">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background-color: #401ce6; color: white; border:none;">
                    <h5 class="modal-title w-100 text-center" style="font-size:1.3rem;">Manage Subcategories</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white;position:absolute;right:1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding:20px 24px;">
                    <table class="table table-striped" id="manageSubCategoryTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th style="width:120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subCategories as $i => $sub)
                            <tr data-id="{{ $sub->id }}">
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <span class="subcat-name">{{ $sub->name }}</span>
                                    <input type="text" class="form-control subcat-edit-input" style="display:none;" value="{{ $sub->name }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning edit-subcat-btn" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger delete-subcat-btn" title="Delete"><i class="fas fa-trash"></i></button>
                                    <button type="button" class="btn btn-sm btn-success save-subcat-btn" style="display:none;" title="Save"><i class="fas fa-check"></i></button>
                                    <button type="button" class="btn btn-sm btn-secondary cancel-subcat-btn" style="display:none;" title="Cancel"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Service Modal (single dynamic modal) -->
    <div class="modal fade" id="editServiceModal" tabindex="-1" role="dialog" aria-labelledby="editServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="editServiceModalLabel" style="font-size: 1.5rem;">
                        @if($category->id == 4)Edit Blood test
                        @elseif($category->id == 1)Edit Pregnancy Scans
                        @elseif($category->id == 2)Edit Diagnostics
                        @elseif($category->id == 3)Edit Physiotherapy
                        @elseif($category->id == 5)Edit Other Diagnostics
                        @else Edit Service
                        @endif
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editServiceForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="category_id" value="{{ $category->id }}">
                    <input type="hidden" id="editServiceId" value="">
                    <div class="modal-body" id="editServiceBody">
                        <!-- Populated via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>
                            @if($category->id == 4)Update Blood test
                            @elseif($category->id == 1)Edit Pregnancy Scans
                            @elseif($category->id == 2)Edit Diagnostics
                            @elseif($category->id == 3)Edit Physiotherapy
                            @elseif($category->id == 5)Edit Other Diagnostics
                            @else Update Service
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Service Modal -->
    <div class="modal fade" id="viewServiceModal" tabindex="-1" role="dialog" aria-labelledby="viewServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.3);">
                <div style="display:flex;background:linear-gradient(135deg,#2a1774,#401ce6);">
                    @if($category->id != 4)
                    <div style="flex:0 0 300px;padding:24px;display:flex;align-items:center;justify-content:center;">
                        <img id="viewServiceImage" src="" alt="" style="width:100%;height:200px;object-fit:contain;border-radius:10px;background:rgba(255,255,255,0.1);padding:8px;display:none;">
                        <div id="viewServiceNoImage" style="width:100%;height:200px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.05);border-radius:10px;color:rgba(255,255,255,0.3);font-size:0.85rem;">No Image</div>
                    </div>
                    @endif
                    <div style="flex:1;padding:{{ $category->id == 4 ? '24px' : '24px 24px 24px 4px' }};display:flex;flex-direction:column;justify-content:center;">
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;align-self:flex-start;">
                            <span id="viewServiceCategory" style="display:inline-block;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);color:#fff;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:3px 12px;border-radius:50px;border:1px solid rgba(255,255,255,0.25);"></span>
                            <span id="viewServicePrice" style="display:inline-block;background:rgba(255,215,0,0.2);backdrop-filter:blur(8px);color:#ffd700;font-size:0.85rem;font-weight:700;padding:3px 12px;border-radius:50px;border:1px solid rgba(255,215,0,0.3);"></span>
                        </div>
                        <h2 id="viewServiceModalLabel" style="font-size:1.2rem;font-weight:700;color:#fff;margin:0 0 8px 0;line-height:1.4;text-shadow:0 2px 6px rgba(0,0,0,0.3);"></h2>
                    </div>
                    <button type="button" data-dismiss="modal" style="position:absolute;top:10px;right:12px;width:30px;height:30px;border-radius:50%;border:none;background:rgba(0,0,0,0.25);color:#fff;font-size:1.2rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0.7;">&times;</button>
                </div>
                <div style="max-height:40vh;overflow-y:auto;padding:20px 28px 24px;background:#fafbfc;">
                    <div id="viewServiceTitle" style="font-size:0.95rem;line-height:1.85;color:#374151;margin-bottom:16px;display:none;"><strong>Title:</strong> <span></span></div>
                    <div id="viewServiceOverview" style="font-size:0.95rem;line-height:1.85;color:#374151;margin-bottom:16px;"></div>
                    <div id="viewServiceDescription1" style="font-size:0.95rem;line-height:1.85;color:#374151;margin-bottom:16px;"></div>
                    <div id="viewServicePackageInclude" style="font-size:0.95rem;line-height:1.85;color:#374151;margin-bottom:16px;display:none;"><strong>Package Include:</strong><br> <span></span></div>
                    <div id="viewServiceDescription2" style="font-size:0.95rem;line-height:1.85;color:#374151;margin-bottom:16px;"></div>
                    <div id="viewServiceTurnAround" style="font-size:0.95rem;line-height:1.85;color:#374151;margin-bottom:16px;display:none;"><strong>Turn Around Time:</strong> <span></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Service Modal -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <div class="modal-body text-center" style="padding:40px 32px 32px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:#fff0f0;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="fas fa-trash-alt" style="font-size:1.5rem;color:#e74c3c;"></i>
                    </div>
                    <h5 style="font-weight:700;color:#212529;margin-bottom:8px;">Delete Service</h5>
                    <p style="color:#6c757d;font-size:0.9rem;margin-bottom:24px;">Are you sure you want to delete this service? This action cannot be undone.</p>
                    <div style="display:flex;gap:10px;justify-content:center;">
                        <button type="button" id="confirmDeleteServiceBtn" class="btn" style="background:#e74c3c;color:#fff;font-weight:600;padding:8px 24px;border-radius:8px;">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="serviceImagePreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:600px;">
            <div class="modal-content" style="border:none;background:transparent;box-shadow:none;">
                <div class="modal-body" style="padding:0;">
                    <img id="serviceImagePreviewSrc" src="" alt="Preview" style="width:100%;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<style>
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
    .modal-dialog {
        margin-top: 0;
    }
    #categoryServicesTable th,
    #categoryServicesTable td {
        border-left: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
    var categoryId = {{ $category->id }};
    var isBloodTest = categoryId == 4;

    var columns = [
        { "data": null, "name": "SI" }
    ];

    if (isBloodTest) {
        columns.push({ "data": "title" });
        columns.push({ "data": "subcategory", "name": "Subcategory" });
    } else {
        columns.push({ "data": "display_title", "name": "Title" });
    }

    columns.push({ "data": "price" });

    if (!isBloodTest) {
        columns.push({ "data": "image_html", "name": "Image" });
    }

    columns.push({ "data": "id" });

    let categoryTable = $('#categoryServicesTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "{{ route('services.admin.category.data', $category->id) }}",
            "type": "GET",
            "cache": false
        },
        "columns": columns,
        "columnDefs": [
            {
                "targets": 0,
                "orderable": false
            },
            {
                "targets": columns.length - 1,
                "orderable": false,
                "render": function(data, type, row) {
                    let viewBtn = '<button type="button" class="btn btn-sm btn-info view-service-btn" data-id="' + data + '" title="View"><i class="fas fa-eye"></i></button> ';
                    let editBtn = '<button type="button" class="btn btn-sm btn-warning edit-service-btn" data-id="' + data + '" title="Edit"><i class="fas fa-edit"></i></button> ';
                    let deleteBtn = '<button type="button" class="btn btn-sm btn-danger delete-service-btn" data-id="' + data + '" title="Delete"><i class="fas fa-trash"></i></button>';
                    return viewBtn + editBtn + deleteBtn;
                }
            }
        ],
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "order": []
    });

    categoryTable.on('draw.dt', function() {
        var api = $(this).DataTable();
        var pageInfo = api.page.info();
        var rows = api.rows({page: 'current', order: 'current'}).nodes();
        $(rows).each(function(i) {
            $('td:eq(0)', this).text(pageInfo.start + i + 1);
        });
    });

    $('.summernote').summernote({
        height: 200,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });

    document.querySelectorAll('input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function(e) {
            let fileName = e.target.files[0]?.name || 'Choose file';
            let label = e.target.nextElementSibling;
            if(label) label.textContent = fileName;
        });
    });

    // Import modal file input
    $('#importServiceModal').on('change', '.custom-file-input', function() {
        var fileName = this.files[0]?.name || 'Choose file';
        $(this).next('.custom-file-label').text(fileName);
    });

    $(document).on('click', '.view-service-btn', function() {
        let id = $(this).data('id');
        $.ajax({
            url: '/admin/services/' + id + '/show',
            type: 'GET',
            success: function(service) {
                $('#viewServiceModalLabel').text(service.service_name);
                $('#viewServiceCategory').text(service.category);
                $('#viewServicePrice').text(service.price ? service.price : '');
                if (service.title) {
                    $('#viewServiceTitle').show().find('span').text(service.title);
                } else {
                    $('#viewServiceTitle').hide();
                }
                $('#viewServiceOverview').html(service.service_overview || '');
                $('#viewServiceDescription1').html(service.description1 || '');
                if (service.package_include) {
                    $('#viewServicePackageInclude').show().find('span').html(service.package_include);
                } else {
                    $('#viewServicePackageInclude').hide();
                }
                $('#viewServiceDescription2').html(service.description2 || '');
                if (service.turn_around_time) {
                    $('#viewServiceTurnAround').show().find('span').text(service.turn_around_time);
                } else {
                    $('#viewServiceTurnAround').hide();
                }
                if (!isBloodTest) {
                    if (service.image) {
                        $('#viewServiceImage').attr('src', service.image).show();
                        $('#viewServiceNoImage').hide();
                    } else {
                        $('#viewServiceImage').hide();
                        $('#viewServiceNoImage').show();
                    }
                }
                $('#viewServiceModal').modal('show');
            },
            error: function() {
                alert('Failed to load service details.');
            }
        });
    });

    // Edit service - fetch data and populate modal dynamically
    $(document).on('click', '.edit-service-btn', function() {
        var id = $(this).data('id');
        $.ajax({
            url: '/admin/services/' + id + '/show',
            type: 'GET',
            success: function(service) {
                $('#editServiceId').val(service.id);
                $('#editServiceForm').attr('action', '/admin/services/' + service.id);
                var html = '';
                if (categoryId == 4) {
                    // Blood Tests
                    var subcatOptions = '<option value="">-- Select Subcategory --</option>';
                    @foreach($subCategories as $sub)
                    subcatOptions += '<option value="{{ $sub->id }}">{{ $sub->name }}</option>';
                    @endforeach
                    html += '<div class="row">';
                    html += '<div class="col-md-6"><div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Subcategory <span class="text-danger">*</span></label><div class="input-group"><select class="form-control" name="sub_category_id" required id="editSubCategorySelect"><option value="">-- Select Subcategory --</option>@foreach($subCategories as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach</select><div class="input-group-append"><button type="button" class="btn btn-success" data-toggle="modal" data-target="#addSubCategoryModal" title="Add Subcategory"><i class="fas fa-plus"></i></button><button type="button" class="btn btn-info" data-toggle="modal" data-target="#manageSubCategoryModal" title="Manage Subcategories"><i class="fas fa-cog"></i></button></div></div></div></div>';
                    html += '<div class="col-md-6"><div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Title <span class="text-danger">*</span></label><input type="text" class="form-control" name="title" value="' + (service.title || '') + '" required></div></div>';
                    html += '</div>';
                    html += '<div class="row"><div class="col-md-6"><div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Rate <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" name="price" value="' + (service.price || '') + '" required></div></div>';
                    html += '<div class="col-md-6"><div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Turn Around Time <span class="text-danger">*</span></label><input type="text" class="form-control" name="turn_around_time" value="' + (service.turn_around_time || '') + '" required></div></div></div>';
                    html += '<div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description <span class="text-danger">*</span></label><textarea class="form-control summernote" name="description1" rows="4" required>' + (service.description1 || '') + '</textarea></div>';
                    html += '<div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Package Include <span class="text-danger">*</span></label><textarea class="form-control summernote" name="package_include" rows="4" required>' + (service.package_include || '') + '</textarea></div>';
                    $('#editServiceBody').html(html);
                    $('#editSubCategorySelect').val(service.sub_category_id);
                } else {
                    // Other categories
                    html += '<div class="row">';
                    html += '<div class="col-md-6"><div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Title <span class="text-danger">*</span></label><input type="text" class="form-control" name="service_name" value="' + (service.service_name || '') + '" required></div></div>';
                    html += '<div class="col-md-3"><div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Price <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" name="price" value="' + (service.price || '') + '" required></div></div>';
                    html += '<div class="col-md-3"><div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Appointment <span class="text-danger">*</span></label><input type="text" class="form-control" name="appointment" value="' + (service.appointment || '') + '" required></div></div>';
                    html += '</div>';
                    html += '<div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Service Overview <span class="text-danger">*</span></label><textarea class="form-control" name="service_overview" rows="3" required>' + (service.service_overview || '') + '</textarea></div>';
                    html += '<div class="row"><div class="col-md-6"><div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">FAQ Link</label><input type="text" class="form-control" name="faq_link" value="' + (service.faq_link || '') + '"></div></div>';
                    html += '<div class="col-md-6"><div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Video Link</label><input type="text" class="form-control" name="video_link" value="' + (service.video_link || '') + '"></div></div></div>';
                    html += '<div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description</label><textarea class="form-control summernote" name="description1" rows="4">' + (service.description1 || '') + '</textarea></div>';
                    html += '<div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description 2</label><textarea class="form-control summernote" name="description2" rows="4">' + (service.description2 || '') + '</textarea></div>';
                    html += '<div class="row"><div class="col-md-6"><div class="form-group"><label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Image</label><div class="input-group"><div class="custom-file"><input type="file" class="custom-file-input" name="image" accept="image/*"><label class="custom-file-label">Choose file</label></div></div></div></div></div>';
                    if (service.image) {
                        html += '<div class="row"><div class="col-md-12"><div class="mb-3"><small class="d-block text-muted mb-2">Current image:</small><img src="' + service.image + '" alt="' + service.service_name + '" class="img-fluid" style="max-height: 150px;"></div></div></div>';
                    }
                    $('#editServiceBody').html(html);
                }
                // Init Summernote on new textareas
                $('#editServiceBody').find('.summernote').each(function() {
                    if (!$(this).data('summernote')) {
                        $(this).summernote({
                            height: 200,
                            toolbar: [['style', ['style']],['font', ['bold', 'underline', 'clear']],['fontname', ['fontname']],['color', ['color']],['para', ['ul', 'ol', 'paragraph']],['table', ['table']],['insert', ['link', 'picture', 'video']],['view', ['fullscreen', 'codeview', 'help']]]
                        });
                    }
                });
                $('#editServiceModal').modal('show');
            },
            error: function() {
                alert('Failed to load service details.');
            }
        });
    });

    $(document).on('shown.bs.modal', '.modal', function() {
        $(this).find('.summernote').each(function() {
            if (!$(this).data('summernote')) {
                $(this).summernote({
                    height: 200,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'underline', 'clear']],
                        ['fontname', ['fontname']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link', 'picture', 'video']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ]
                });
            }
        });
    });

    // Manage subcategories - inline edit
    $(document).on('click', '.edit-subcat-btn', function() {
        var row = $(this).closest('tr');
        row.find('.subcat-name').hide();
        row.find('.subcat-edit-input').show();
        row.find('.edit-subcat-btn, .delete-subcat-btn').hide();
        row.find('.save-subcat-btn, .cancel-subcat-btn').show();
    });

    $(document).on('click', '.cancel-subcat-btn', function() {
        var row = $(this).closest('tr');
        row.find('.subcat-edit-input').val(row.find('.subcat-name').text().trim());
        row.find('.subcat-name').show();
        row.find('.subcat-edit-input').hide();
        row.find('.edit-subcat-btn, .delete-subcat-btn').show();
        row.find('.save-subcat-btn, .cancel-subcat-btn').hide();
    });

    $(document).on('click', '.save-subcat-btn', function() {
        var row = $(this).closest('tr');
        var id = row.data('id');
        var name = row.find('.subcat-edit-input').val().trim();
        if (!name) return;
        $.ajax({
            url: '/admin/services/sub-category/' + id,
            type: 'PUT',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                name: name
            },
            success: function(response) {
                if (response.success) {
                    row.find('.subcat-name').text(response.name).show();
                    row.find('.subcat-edit-input').hide();
                    row.find('.edit-subcat-btn, .delete-subcat-btn').show();
                    row.find('.save-subcat-btn, .cancel-subcat-btn').hide();
                    // Update dropdowns
                    $('select[name="sub_category_id"] option[value="' + id + '"]').text(response.name);
                }
            }
        });
    });

    $(document).on('click', '.delete-subcat-btn', function() {
        if (!confirm('Delete this subcategory and all its services?')) return;
        var row = $(this).closest('tr');
        var id = row.data('id');
        $.ajax({
            url: '/admin/services/sub-category/' + id,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Remove from dropdowns
                    $('select[name="sub_category_id"] option[value="' + id + '"]').remove();
                    row.remove();
                }
            }
        });
    });

    // Add subcategory
    $('#saveSubCategoryBtn').on('click', function() {
        var name = $('#newSubCategoryName').val().trim();
        if (!name) {
            $('#addSubCategoryError').text('Please enter a subcategory name.').show();
            return;
        }
        $('#addSubCategoryError').hide();
        $.ajax({
            url: '{{ route("services.admin.subcategory.store") }}',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                category_id: {{ $category->id }},
                name: name
            },
            success: function(response) {
                if (response.success) {
                    var option = '<option value="' + response.id + '">' + response.name + '</option>';
                    $('#addSubCategorySelect').append(option).val(response.id);
                    $('[id^="editSubCategorySelect"]').each(function() {
                        $(this).append('<option value="' + response.id + '">' + response.name + '</option>');
                    });
                    var rowCount = $('#manageSubCategoryTable tbody tr').length + 1;
                    var newRow = '<tr data-id="' + response.id + '"><td>' + rowCount + '</td><td><span class="subcat-name">' + response.name + '</span><input type="text" class="form-control subcat-edit-input" style="display:none;" value="' + response.name + '"></td><td><button type="button" class="btn btn-sm btn-warning edit-subcat-btn" title="Edit"><i class="fas fa-edit"></i></button> <button type="button" class="btn btn-sm btn-danger delete-subcat-btn" title="Delete"><i class="fas fa-trash"></i></button><button type="button" class="btn btn-sm btn-success save-subcat-btn" style="display:none;" title="Save"><i class="fas fa-check"></i></button> <button type="button" class="btn btn-sm btn-secondary cancel-subcat-btn" style="display:none;" title="Cancel"><i class="fas fa-times"></i></button></td></tr>';
                    $('#manageSubCategoryTable tbody').append(newRow);
                    $('#addSubCategoryModal').modal('hide');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.errors?.name?.[0] || 'Error adding subcategory.';
                $('#addSubCategoryError').text(msg).show();
            }
        });
    });

    $('#addSubCategoryModal').on('hidden.bs.modal', function() {
        $('#newSubCategoryName').val('');
        $('#addSubCategoryError').hide();
    });

    $(document).on('click', '.delete-service-btn', function() {
        let id = $(this).data('id');
        $('#confirmDeleteServiceBtn').data('service-id', id);
        $('#deleteServiceModal').modal('show');
    });

    $(document).on('click', '#confirmDeleteServiceBtn', function() {
        let id = $(this).data('service-id');
        $.ajax({
            url: '/admin/services/' + id,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteServiceModal').modal('hide');
                categoryTable.ajax.reload();
            },
            error: function(error) {
                alert('Error deleting service!');
            }
        });
    });

    // Reload table when service modals close
    $(document).on('hidden.bs.modal', '#addServiceModal, #importServiceModal, #editServiceModal', function() {
        categoryTable.ajax.reload();
    });

    // Destroy Summernote on edit modal hide to prevent issues
    $('#editServiceModal').on('hidden.bs.modal', function() {
        $(this).find('.summernote').each(function() {
            if ($(this).data('summernote')) {
                $(this).summernote('destroy');
            }
        });
    });
</script>
@stop
