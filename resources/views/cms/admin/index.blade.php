@extends('adminlte::page')

@section('title', 'Manage CMS')

@section('content_header')
    <div class="row">
        <div class="col-md-8">
            <h1>Manage CMS</h1>
        </div>
        <div class="col-md-4 text-right">
            <button type="button" class="btn" style="background-color: #28a745; color: white; border: none;" data-toggle="modal" data-target="#addCmsModal">
                <i class="fas fa-plus mr-2"></i>Add New Page
            </button>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
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
            }, 3000);
        </script>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">CMS Pages</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover" id="cmsTable">
                <thead>
                    <tr>
                        <th>SI</th>
                        <th>Page</th>
                        <th>Title</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add CMS Modal -->
    <div class="modal fade" id="addCmsModal" tabindex="-1" role="dialog" aria-labelledby="addCmsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="addCmsModalLabel" style="font-size: 1.5rem;">Add New CMS Page</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('cms.admin.store') }}" method="POST" enctype="multipart/form-data" id="addCmsForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_page" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Page <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_page" name="page" placeholder="Enter page name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_title" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_title" name="title" placeholder="Enter page title" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="add_description" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description</label>
                            <textarea class="form-control summernote" id="add_description" name="description" rows="5" placeholder="Enter page description"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_image" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Image</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="add_image" name="image" accept="image/*">
                                            <label class="custom-file-label" for="add_image">Choose file</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>Save Page
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit CMS Modals -->
    @foreach($cms ?? [] as $item)
    <div class="modal fade" id="editCmsModal{{ $item->id }}" tabindex="-1" role="dialog" aria-labelledby="editCmsModalLabel{{ $item->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="editCmsModalLabel{{ $item->id }}" style="font-size: 1.5rem;">Edit CMS Page</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('cms.admin.update', $item->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_page_{{ $item->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Page <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_page_{{ $item->id }}" name="page" value="{{ $item->page }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_title_{{ $item->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_title_{{ $item->id }}" name="title" value="{{ $item->title }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit_description_{{ $item->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description</label>
                            <textarea class="form-control summernote" id="edit_description_{{ $item->id }}" name="description" rows="5">{{ $item->description }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_image_{{ $item->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Image</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="edit_image_{{ $item->id }}" name="image" accept="image/*">
                                            <label class="custom-file-label" for="edit_image_{{ $item->id }}">Choose file</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($item->image)
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <small class="d-block text-muted mb-2">Current image:</small>
                                        <img src="{{ str_starts_with($item->image, 'http') ? $item->image : asset($item->image) }}" alt="{{ $item->title }}" class="img-fluid" style="max-height: 150px;">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>Update Page
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <!-- View CMS Modal -->
    <div class="modal fade" id="viewCmsModal" tabindex="-1" role="dialog" aria-labelledby="viewCmsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.3);">
                <div style="display:flex;background:linear-gradient(135deg,#2a1774,#401ce6);">
                    <div style="flex:0 0 300px;padding:24px;display:flex;align-items:center;justify-content:center;">
                        <img id="viewCmsImage" src="" alt="" style="width:100%;height:200px;object-fit:contain;border-radius:10px;background:rgba(255,255,255,0.1);padding:8px;display:none;">
                        <div id="viewCmsNoImage" style="width:100%;height:200px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.05);border-radius:10px;color:rgba(255,255,255,0.3);font-size:0.85rem;">No Image</div>
                    </div>
                    <div style="flex:1;padding:24px 24px 24px 4px;display:flex;flex-direction:column;justify-content:center;">
                        <span id="viewCmsPage" style="display:inline-block;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);color:#fff;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:3px 12px;border-radius:50px;border:1px solid rgba(255,255,255,0.25);margin-bottom:10px;align-self:flex-start;"></span>
                        <h2 id="viewCmsModalLabel" style="font-size:1.2rem;font-weight:700;color:#fff;margin:0 0 8px 0;line-height:1.4;text-shadow:0 2px 6px rgba(0,0,0,0.3);"></h2>
                    </div>
                    <button type="button" data-dismiss="modal" style="position:absolute;top:10px;right:12px;width:30px;height:30px;border-radius:50%;border:none;background:rgba(0,0,0,0.25);color:#fff;font-size:1.2rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0.7;">&times;</button>
                </div>
                <div style="max-height:40vh;overflow-y:auto;padding:20px 28px 24px;background:#fafbfc;">
                    <div id="viewCmsDescription" style="font-size:0.95rem;line-height:1.85;color:#374151;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete CMS Modal -->
    <div class="modal fade" id="deleteCmsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <div class="modal-body text-center" style="padding:40px 32px 32px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:#fff0f0;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="fas fa-trash-alt" style="font-size:1.5rem;color:#e74c3c;"></i>
                    </div>
                    <h5 style="font-weight:700;color:#212529;margin-bottom:8px;">Delete CMS Page</h5>
                    <p style="color:#6c757d;font-size:0.9rem;margin-bottom:24px;">Are you sure you want to delete this page? This action cannot be undone.</p>
                    <div style="display:flex;gap:10px;justify-content:center;">
                        <button type="button" id="confirmDeleteCmsBtn" class="btn" style="background:#e74c3c;color:#fff;font-weight:600;padding:8px 24px;border-radius:8px;">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.css" rel="stylesheet">
<style>
    #cmsTable th,
    #cmsTable td {
        border-left: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
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
    .modal-dialog {
        margin-top: 0;
    }
</style>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $(document).ready(function() {
        let table = $('#cmsTable').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "{{ route('cms.admin.data') }}",
                "type": "GET",
                "cache": false
            },
            "columns": [
                { "data": null, "name": "SI" },
                { "data": "page" },
                { "data": "title" },
                { "data": "image" },
                { "data": "id" }
            ],
            "columnDefs": [
                {
                    "targets": 0,
                    "orderable": false
                },
                {
                    "targets": 3,
                    "orderable": false,
                    "render": function(data, type, row) {
                        if (data) {
                            return '<a href="javascript:void(0)" class="cms-image-thumb" data-image="' + data + '"><img src="' + data + '" alt="' + row.title + '" style="width:60px;height:40px;object-fit:cover;border-radius:4px;cursor:pointer;"></a>';
                        }
                        return '<div style="width:60px;height:40px;background:#f1f3f5;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#adb5bd;"><i class="fas fa-image"></i></div>';
                    }
                },
                {
                    "targets": 4,
                    "orderable": false,
                    "render": function(data, type, row) {
                        let viewBtn = '<button type="button" class="btn btn-sm btn-info view-cms-btn" data-id="' + data + '" title="View"><i class="fas fa-eye"></i></button> ';
                        let editBtn = '<button type="button" class="btn btn-sm btn-warning edit-cms-btn" data-id="' + data + '" title="Edit"><i class="fas fa-edit"></i></button> ';
                        let deleteBtn = '<button type="button" class="btn btn-sm btn-danger delete-cms-btn" data-id="' + data + '" title="Delete"><i class="fas fa-trash"></i></button>';
                        return viewBtn + editBtn + deleteBtn;
                    }
                }
            ],
            "pageLength": 10,
            "order": [[1, "asc"]]
        });

        table.on('draw.dt', function() {
            var api = $(this).DataTable();
            var pageInfo = api.page.info();
            var rows = api.rows({page: 'current', order: 'current'}).nodes();
            $(rows).each(function(i) {
                $('td:eq(0)', this).text(pageInfo.start + i + 1);
            });
        });

        // Image thumbnail click preview
        $(document).on('click', '.cms-image-thumb', function() {
            var src = $(this).data('image');
            $('#cmsImagePreviewSrc').attr('src', src);
            $('#cmsImagePreviewModal').modal('show');
        });

        // View
        $(document).on('click', '.view-cms-btn', function() {
            let id = $(this).data('id');
            $.ajax({
                url: '/admin/cms/' + id + '/show',
                type: 'GET',
                success: function(item) {
                    $('#viewCmsModalLabel').text(item.title);
                    $('#viewCmsPage').text(item.page);
                    $('#viewCmsDescription').html(item.description || 'No description available.');
                    if (item.image) {
                        $('#viewCmsImage').attr('src', item.image).show();
                        $('#viewCmsNoImage').hide();
                    } else {
                        $('#viewCmsImage').hide();
                        $('#viewCmsNoImage').show();
                    }
                    $('#viewCmsModal').modal('show');
                },
                error: function() {
                    alert('Failed to load CMS page details.');
                }
            });
        });

        // Edit
        $(document).on('click', '.edit-cms-btn', function() {
            let id = $(this).data('id');
            $('#editCmsModal' + id).modal('show');
        });

        // Delete
        $(document).on('click', '.delete-cms-btn', function() {
            let id = $(this).data('id');
            $('#confirmDeleteCmsBtn').data('cms-id', id);
            $('#deleteCmsModal').modal('show');
        });

        $(document).on('click', '#confirmDeleteCmsBtn', function() {
            let id = $(this).data('cms-id');
            $.ajax({
                url: '/admin/cms/' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#deleteCmsModal').modal('hide');
                    location.reload();
                },
                error: function(error) {
                    alert('Error deleting CMS page!');
                }
            });
        });

        $('#addCmsForm').on('submit', function(e) {
            let description = $('#add_description').summernote('code');
            if(!description || description.trim() === '' || description === '<p><br></p>') {
            }
        });

        $(document).on('hidden.bs.modal', '.modal', function() {
            if($(this).attr('id') === 'addCmsModal') {
                table.ajax.reload();
                document.getElementById('addCmsForm').reset();
                $('#add_description').summernote('reset');
            }
        });
    });

    document.querySelectorAll('input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function(e) {
            let fileName = e.target.files[0]?.name || 'Choose file';
            let label = e.target.nextElementSibling;
            if(label) label.textContent = fileName;
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
</script>

<!-- Image Preview Modal -->
<div class="modal fade" id="cmsImagePreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:600px;">
        <div class="modal-content" style="border:none;background:transparent;box-shadow:none;">
            <div class="modal-body" style="padding:0;">
                <img id="cmsImagePreviewSrc" src="" alt="Preview" style="width:100%;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            </div>
        </div>
    </div>
</div>
@stop
