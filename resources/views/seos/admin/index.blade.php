@extends('adminlte::page')

@section('title', 'Manage SEO')

@section('content_header')
    <div class="row">
        <div class="col-md-8">
            <h1>Manage SEO</h1>
        </div>
        <div class="col-md-4 text-right">
            <button type="button" class="btn" style="background-color: #28a745; color: white; border: none;" data-toggle="modal" data-target="#addSeoModal">
                <i class="fas fa-plus mr-2"></i>Add New SEO
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
            <h3 class="card-title">SEO Records</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover" id="seoTable">
                <thead>
                    <tr>
                        <th>SI</th>
                        <th>Page</th>
                        <th>Meta Title</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add SEO Modal -->
    <div class="modal fade" id="addSeoModal" tabindex="-1" role="dialog" aria-labelledby="addSeoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="addSeoModalLabel" style="font-size: 1.5rem;">Add New SEO Record</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('seos.admin.store') }}" method="POST" id="addSeoForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="add_page" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Page <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_page" name="page" placeholder="e.g., home, about, contact" required maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="add_meta_title" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Title</label>
                            <input type="text" class="form-control" id="add_meta_title" name="meta_title" placeholder="Enter meta title" maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="add_meta_description" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Description</label>
                            <textarea class="form-control" id="add_meta_description" name="meta_description" rows="4" placeholder="Enter meta description"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="add_meta_keywords" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Keywords</label>
                            <input type="text" class="form-control" id="add_meta_keywords" name="meta_keywords" placeholder="Enter meta keywords (comma separated)" maxlength="255">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>Save Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit SEO Modals -->
    @foreach($seos ?? [] as $seo)
    <div class="modal fade" id="editSeoModal{{ $seo->id }}" tabindex="-1" role="dialog" aria-labelledby="editSeoModalLabel{{ $seo->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="editSeoModalLabel{{ $seo->id }}" style="font-size: 1.5rem;">Edit SEO Record</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('seos.admin.update', $seo->id) }}" method="POST" id="editSeoForm{{ $seo->id }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_page_{{ $seo->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Page <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_page_{{ $seo->id }}" name="page" value="{{ $seo->page }}" required maxlength="255" readonly style="background-color: #e9ecef;">
                        </div>

                        <div class="form-group">
                            <label for="edit_meta_title_{{ $seo->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Title</label>
                            <input type="text" class="form-control" id="edit_meta_title_{{ $seo->id }}" name="meta_title" value="{{ $seo->meta_title }}" placeholder="Enter meta title" maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="edit_meta_description_{{ $seo->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Description</label>
                            <textarea class="form-control" id="edit_meta_description_{{ $seo->id }}" name="meta_description" rows="4" placeholder="Enter meta description">{{ $seo->meta_description }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="edit_meta_keywords_{{ $seo->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Keywords</label>
                            <input type="text" class="form-control" id="edit_meta_keywords_{{ $seo->id }}" name="meta_keywords" value="{{ $seo->meta_keywords }}" placeholder="Enter meta keywords (comma separated)" maxlength="255">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>Update Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <!-- View SEO Modal -->
    <div class="modal fade" id="viewSeoModal" tabindex="-1" role="dialog" aria-labelledby="viewSeoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.3);">
                <div style="background:linear-gradient(135deg,#2a1774,#401ce6);padding:24px 28px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <span id="viewSeoPage" style="display:inline-block;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);color:#fff;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:3px 12px;border-radius:50px;border:1px solid rgba(255,255,255,0.25);"></span>
                        </div>
                        <button type="button" data-dismiss="modal" style="width:30px;height:30px;border-radius:50%;border:none;background:rgba(0,0,0,0.25);color:#fff;font-size:1.2rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0.7;">&times;</button>
                    </div>
                </div>
                <div style="padding:20px 28px 24px;background:#fafbfc;">
                    <div style="margin-bottom:14px;">
                        <strong style="font-size:0.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.3px;">Meta Title</strong>
                        <p id="viewSeoMetaTitle" style="margin:4px 0 0 0;color:#374151;font-size:0.95rem;">N/A</p>
                    </div>
                    <div style="margin-bottom:14px;">
                        <strong style="font-size:0.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.3px;">Meta Description</strong>
                        <p id="viewSeoMetaDescription" style="margin:4px 0 0 0;color:#374151;font-size:0.95rem;">N/A</p>
                    </div>
                    <div>
                        <strong style="font-size:0.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.3px;">Meta Keywords</strong>
                        <p id="viewSeoMetaKeywords" style="margin:4px 0 0 0;color:#374151;font-size:0.95rem;">N/A</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<style>
    .modal-right {
        margin-right: 0;
        margin-left: auto;
        margin-top: 0;
    }
    @media (min-width: 576px) {
    #seoTable th,
    #seoTable td {
        border-left: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
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
<script>
    $(document).ready(function() {
        let table = $('#seoTable').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "{{ route('seos.admin.data') }}",
                "type": "GET",
                "cache": false
            },
            "columns": [
                { "data": null, "name": "SI" },
                { "data": "page" },
                { "data": "meta_title" },
                { "data": "id" }
            ],
            "columnDefs": [
                {
                    "targets": 0,
                    "orderable": false
                },
                {
                    "targets": 2,
                    "render": function(data, type, row) {
                        return data || '<span class="text-muted">N/A</span>';
                    }
                },
                {
                    "targets": 3,
                    "orderable": false,
                    "render": function(data, type, row) {
                        let viewBtn = '<button type="button" class="btn btn-sm btn-info view-seo-btn" data-id="' + data + '" title="View"><i class="fas fa-eye"></i></button> ';
                        let editBtn = '<button type="button" class="btn btn-sm btn-warning edit-seo-btn" data-id="' + data + '" title="Edit"><i class="fas fa-edit"></i></button>';
                        return viewBtn + editBtn;
                    }
                }
            ],
            "pageLength": 10,
            "order": [[1, "asc"]]
        });

        // Re-number SI column after each draw
        table.on('draw.dt', function() {
            var api = $(this).DataTable();
            var pageInfo = api.page.info();
            var rows = api.rows({page: 'current', order: 'current'}).nodes();
            $(rows).each(function(i) {
                $('td:eq(0)', this).text(pageInfo.start + i + 1);
            });
        });

        // Handle view button click
        $(document).on('click', '.view-seo-btn', function() {
            let seoId = $(this).data('id');
            $.ajax({
                url: '/admin/seos/' + seoId + '/show',
                type: 'GET',
                success: function(seo) {
                    $('#viewSeoPage').text(seo.page);
                    $('#viewSeoMetaTitle').text(seo.meta_title || 'N/A');
                    $('#viewSeoMetaDescription').text(seo.meta_description || 'N/A');
                    $('#viewSeoMetaKeywords').text(seo.meta_keywords || 'N/A');
                    $('#viewSeoModal').modal('show');
                },
                error: function() {
                    alert('Failed to load SEO details.');
                }
            });
        });

        // Handle edit button click
        $(document).on('click', '.edit-seo-btn', function() {
            let seoId = $(this).data('id');
            $('#editSeoModal' + seoId).modal('show');
        });

        // Reload table when add modal is closed
        $(document).on('hidden.bs.modal', '.modal', function() {
            if($(this).attr('id') === 'addSeoModal') {
                table.ajax.reload();
                document.getElementById('addSeoForm').reset();
            }
        });
    });
</script>
@stop
