@extends('adminlte::page')

@section('title', 'Manage FAQs')

@section('content_header')
    <div class="row">
        <div class="col-md-8">
            <h1>Manage FAQs</h1>
        </div>
        <div class="col-md-4 text-right">
            <button type="button" class="btn" style="background-color: #28a745; color: white; border: none;" data-toggle="modal" data-target="#addFaqModal">
                <i class="fas fa-plus mr-2"></i>Add New FAQ
            </button>
        </div>
    </div>
@endsection

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
            <h3 class="card-title">FAQ List</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover" id="faqsTable">
                <thead>
                    <tr>
                        <th>SI</th>
                        <th>Category</th>
                        <th>Question</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add FAQ Modal -->
    <div class="modal fade" id="addFaqModal" tabindex="-1" role="dialog" aria-labelledby="addFaqModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="addFaqModalLabel" style="font-size: 1.5rem;">Add New FAQ</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('faqs.admin.store') }}" method="POST" id="addFaqForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_category" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Category <span class="text-danger">*</span></label>
                                    <select class="form-control" id="add_category" name="category_id" required onchange="loadFaqSubCategories(this.value, 'add_sub_category')">
                                        <option value="">-- Select Category --</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_sub_category" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Sub Category</label>
                                    <select class="form-control" id="add_sub_category" name="sub_category_id">
                                        <option value="">-- Select Sub Category --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="faqRowsContainer">
                            <div class="faq-row card card-outline card-success mb-3">
                                <div class="card-header" style="display:flex;justify-content:flex-end;padding:4px 8px;">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-faq-row" title="Remove this FAQ"><i class="fas fa-times"></i></button>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Question <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="questions[]" placeholder="Enter question" required>
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Answer <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="answers[]" rows="4" placeholder="Enter answer"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-success btn-block" id="addFaqRowBtn">
                            <i class="fas fa-plus mr-2"></i>Add Another FAQ
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save All FAQs</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit FAQ Modals -->
    @foreach($faqs ?? [] as $faq)
    <div class="modal fade" id="editFaqModal{{ $faq->id }}" tabindex="-1" role="dialog" aria-labelledby="editFaqModalLabel{{ $faq->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="editFaqModalLabel{{ $faq->id }}" style="font-size: 1.5rem;">Edit FAQ</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('faqs.admin.update', $faq->id) }}" method="POST" id="editFaqForm{{ $faq->id }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_category{{ $faq->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Category <span class="text-danger">*</span></label>
                                    <select class="form-control" id="edit_category{{ $faq->id }}" name="category_id" required onchange="loadFaqSubCategories(this.value, 'edit_sub_category{{ $faq->id }}', {{ $faq->id }})">
                                        <option value="">-- Select Category --</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ $faq->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_sub_category{{ $faq->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Sub Category</label>
                                    <select class="form-control" id="edit_sub_category{{ $faq->id }}" name="sub_category_id">
                                        <option value="">-- Select Sub Category --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit_question{{ $faq->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Question <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_question{{ $faq->id }}" name="question" value="{{ $faq->question }}" placeholder="Enter question" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_answer{{ $faq->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Answer <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_answer{{ $faq->id }}" name="answer" rows="5" placeholder="Enter answer">{{ $faq->answer }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>Update FAQ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <!-- View FAQ Modal -->
    <div class="modal fade" id="viewFaqModal" tabindex="-1" role="dialog" aria-labelledby="viewFaqModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.3);">
                <div style="background:linear-gradient(135deg,#2a1774,#401ce6);padding:24px 28px;position:relative;">
                    <span id="viewFaqCategory" style="display:inline-block;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);color:#fff;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:3px 12px;border-radius:50px;border:1px solid rgba(255,255,255,0.25);margin-bottom:10px;"></span>
                    <span id="viewFaqSubCategory" style="display:inline-block;background:rgba(255,255,255,0.1);backdrop-filter:blur(8px);color:#ccc;font-size:0.65rem;font-weight:600;padding:3px 10px;border-radius:50px;border:1px solid rgba(255,255,255,0.15);margin-bottom:10px;margin-left:6px;display:none;"></span>
                    <h2 id="viewFaqModalLabel" style="font-size:1.2rem;font-weight:700;color:#fff;margin:0;line-height:1.4;text-shadow:0 2px 6px rgba(0,0,0,0.3);"></h2>
                    <button type="button" data-dismiss="modal" style="position:absolute;top:10px;right:12px;width:30px;height:30px;border-radius:50%;border:none;background:rgba(0,0,0,0.25);color:#fff;font-size:1.2rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0.7;">&times;</button>
                </div>
                <div style="max-height:40vh;overflow-y:auto;padding:20px 28px 24px;background:#fafbfc;">
                    <div id="viewFaqAnswer" style="font-size:0.95rem;line-height:1.85;color:#374151;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteFaqModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <div class="modal-body text-center" style="padding:40px 32px 32px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:#fff0f0;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="fas fa-trash-alt" style="font-size:1.5rem;color:#e74c3c;"></i>
                    </div>
                    <h5 style="font-weight:700;color:#212529;margin-bottom:8px;">Delete FAQ</h5>
                    <p style="color:#6c757d;font-size:0.9rem;margin-bottom:24px;">Are you sure you want to delete this FAQ? This action cannot be undone.</p>
                    <div style="display:flex;gap:10px;justify-content:center;">
                        <button type="button" id="confirmDeleteFaqBtn" class="btn" style="background:#e74c3c;color:#fff;font-weight:600;padding:8px 24px;border-radius:8px;">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<style>
    #faqsTable th,
    #faqsTable td {
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
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        let table = $('#faqsTable').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "{{ route('faqs.admin.data') }}",
                "type": "GET",
                "cache": false
            },
            "columns": [
                { "data": null, "name": "SI" },
                { "data": "category" },
                { "data": "question" },
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
                        let viewBtn = '<button type="button" class="btn btn-sm btn-info view-faq-btn" data-id="' + data + '" title="View"><i class="fas fa-eye"></i></button> ';
                        let editBtn = '<button type="button" class="btn btn-sm btn-warning edit-faq-btn" data-id="' + data + '" title="Edit"><i class="fas fa-edit"></i></button> ';
                        let deleteBtn = '<button type="button" class="btn btn-sm btn-danger delete-faq-btn" data-id="' + data + '" title="Delete"><i class="fas fa-trash"></i></button>';
                        return viewBtn + editBtn + deleteBtn;
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

        // View FAQ
        $(document).on('click', '.view-faq-btn', function() {
            let faqId = $(this).data('id');
            $.ajax({
                url: '/admin/faqs/' + faqId + '/show',
                type: 'GET',
                success: function(faq) {
                    $('#viewFaqModalLabel').text(faq.question);
                    $('#viewFaqCategory').text(faq.category);
                    if (faq.sub_category) {
                        $('#viewFaqSubCategory').text(faq.sub_category).show();
                    } else {
                        $('#viewFaqSubCategory').hide();
                    }
                    $('#viewFaqAnswer').html(faq.answer);
                    $('#viewFaqModal').modal('show');
                },
                error: function() {
                    alert('Failed to load FAQ details.');
                }
            });
        });

        // Load sub-categories for a given category
        window.loadFaqSubCategories = function(categoryId, targetSelectId, faqId) {
            var $select = $('#' + targetSelectId);
            $select.html('<option value="">-- Select Sub Category --</option>');
            if (!categoryId) return;
            $.ajax({
                url: '/admin/blogs/sub-categories/' + categoryId,
                type: 'GET',
                success: function(data) {
                    $.each(data, function(i, sc) {
                        $select.append('<option value="' + sc.id + '">' + sc.name + '</option>');
                    });
                    // Pre-select current value
                    if (faqId) {
                        var rows = $('#faqsTable').DataTable().rows().data().filter(function(r) { return r.id == faqId; });
                        if (rows.length && rows[0].sub_category_id) {
                            $select.val(rows[0].sub_category_id);
                        }
                    }
                }
            });
        };

        // Edit FAQ — load sub-categories before showing modal
        $(document).on('click', '.edit-faq-btn', function() {
            let faqId = $(this).data('id');
            let categorySelect = document.getElementById('edit_category' + faqId);
            if (categorySelect && categorySelect.value) {
                loadFaqSubCategories(categorySelect.value, 'edit_sub_category' + faqId, faqId);
            }
            $('#editFaqModal' + faqId).modal('show');
        });

        // Delete FAQ
        $(document).on('click', '.delete-faq-btn', function() {
            let faqId = $(this).data('id');
            $('#confirmDeleteFaqBtn').data('faq-id', faqId);
            $('#deleteFaqModal').modal('show');
        });

        // Confirm delete
        $(document).on('click', '#confirmDeleteFaqBtn', function() {
            let faqId = $(this).data('faq-id');
            $.ajax({
                url: '/admin/faqs/' + faqId,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#deleteFaqModal').modal('hide');
                    location.reload();
                },
                error: function(error) {
                    alert('Error deleting FAQ!');
                }
            });
        });

        // Add another FAQ row
        $('#addFaqRowBtn').on('click', function() {
            var $row = $('.faq-row').first().clone();
            $row.find('input, textarea').val('');
            $('#faqRowsContainer').append($row);
        });

        // Remove a FAQ row
        $(document).on('click', '.remove-faq-row', function() {
            if ($('.faq-row').length > 1) {
                $(this).closest('.faq-row').remove();
            }
        });

        // Reload table when modal is closed
        $(document).on('hidden.bs.modal', '.modal', function() {
            if($(this).attr('id') === 'addFaqModal') {
                table.ajax.reload();
                document.getElementById('addFaqForm').reset();
            }
        });
    });
</script>
@endsection
