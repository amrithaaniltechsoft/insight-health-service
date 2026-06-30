@extends('adminlte::page')

@section('title', 'Manage Shop')

@section('content_header')
    <div class="row">
        <div class="col-md-8">
            <h1>Manage Shop</h1>
        </div>
        <div class="col-md-4 text-right">
            <button type="button" class="btn" style="background-color: #28a745; color: white; border: none;" data-toggle="modal" data-target="#addShopModal">
                <i class="fas fa-plus mr-2"></i>Add New Product
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
            <h3 class="card-title">Shop Products</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover" id="shopTable">
                <thead>
                    <tr>
                        <th>SI</th>
                        <th>Category</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Shop Modal -->
    <div class="modal fade" id="addShopModal" tabindex="-1" role="dialog" aria-labelledby="addShopModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="addShopModalLabel" style="font-size: 1.5rem;">Add New Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('shops.admin.store') }}" method="POST" enctype="multipart/form-data" id="addShopForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_category" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Category <span class="text-danger">*</span></label>
                                    <select class="form-control" id="add_category" name="category" required>
                                        <option value="">-- Select Category --</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}">{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_product_name" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_product_name" name="product_name" placeholder="Enter product name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_price" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Price <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="add_price" name="price" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="add_description" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description</label>
                            <textarea class="form-control summernote" id="add_description" name="description" rows="4" placeholder="Enter product description"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_image" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Image <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="add_image" name="image" accept="image/*" required>
                                            <label class="custom-file-label" for="add_image">Choose file</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Shop Modals -->
    @foreach($shops ?? [] as $shop)
    <div class="modal fade" id="editShopModal{{ $shop->id }}" tabindex="-1" role="dialog" aria-labelledby="editShopModalLabel{{ $shop->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="editShopModalLabel{{ $shop->id }}" style="font-size: 1.5rem;">Edit Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('shops.admin.update', $shop->id) }}" method="POST" enctype="multipart/form-data" id="editShopForm{{ $shop->id }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_category_{{ $shop->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Category <span class="text-danger">*</span></label>
                                    <select class="form-control" id="edit_category_{{ $shop->id }}" name="category" required>
                                        <option value="">-- Select Category --</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}" {{ $shop->category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_product_name_{{ $shop->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_product_name_{{ $shop->id }}" name="product_name" value="{{ $shop->product_name }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_price_{{ $shop->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Price <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="edit_price_{{ $shop->id }}" name="price" value="{{ $shop->price }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit_description_{{ $shop->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description</label>
                            <textarea class="form-control summernote" id="edit_description_{{ $shop->id }}" name="description" rows="4">{{ $shop->description }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_image_{{ $shop->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Image</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="edit_image_{{ $shop->id }}" name="image" accept="image/*">
                                            <label class="custom-file-label" for="edit_image_{{ $shop->id }}">Choose file</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($shop->image)
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <small class="d-block text-muted mb-2">Current image:</small>
                                        <img src="{{ str_starts_with($shop->image, 'http') ? $shop->image : asset($shop->image) }}" alt="{{ $shop->product_name }}" class="img-fluid" style="max-height: 150px;">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <!-- View Shop Modal -->
    <div class="modal fade" id="viewShopModal" tabindex="-1" role="dialog" aria-labelledby="viewShopModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.3);">
                <div style="display:flex;background:linear-gradient(135deg,#2a1774,#401ce6);">
                    <div style="flex:0 0 300px;padding:24px;display:flex;align-items:center;justify-content:center;">
                        <img id="viewShopImage" src="" alt="" style="width:100%;height:200px;object-fit:contain;border-radius:10px;background:rgba(255,255,255,0.1);padding:8px;display:none;">
                        <div id="viewShopNoImage" style="width:100%;height:200px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.05);border-radius:10px;color:rgba(255,255,255,0.3);font-size:0.85rem;">No Image</div>
                    </div>
                    <div style="flex:1;padding:24px 24px 24px 4px;display:flex;flex-direction:column;justify-content:center;">
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;align-self:flex-start;">
                            <span id="viewShopCategory" style="display:inline-block;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);color:#fff;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:3px 12px;border-radius:50px;border:1px solid rgba(255,255,255,0.25);"></span>
                            <span id="viewShopPrice" style="display:inline-block;background:rgba(255,215,0,0.2);backdrop-filter:blur(8px);color:#ffd700;font-size:0.85rem;font-weight:700;padding:3px 12px;border-radius:50px;border:1px solid rgba(255,215,0,0.3);"></span>
                        </div>
                        <h2 id="viewShopModalLabel" style="font-size:1.2rem;font-weight:700;color:#fff;margin:0 0 8px 0;line-height:1.4;text-shadow:0 2px 6px rgba(0,0,0,0.3);"></h2>
                    </div>
                    <button type="button" data-dismiss="modal" style="position:absolute;top:10px;right:12px;width:30px;height:30px;border-radius:50%;border:none;background:rgba(0,0,0,0.25);color:#fff;font-size:1.2rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0.7;">&times;</button>
                </div>
                <div style="max-height:40vh;overflow-y:auto;padding:20px 28px 24px;background:#fafbfc;">
                    <div id="viewShopDescription" style="font-size:0.95rem;line-height:1.85;color:#374151;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Shop Modal -->
    <div class="modal fade" id="deleteShopModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <div class="modal-body text-center" style="padding:40px 32px 32px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:#fff0f0;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="fas fa-trash-alt" style="font-size:1.5rem;color:#e74c3c;"></i>
                    </div>
                    <h5 style="font-weight:700;color:#212529;margin-bottom:8px;">Delete Product</h5>
                    <p style="color:#6c757d;font-size:0.9rem;margin-bottom:24px;">Are you sure you want to delete this product? This action cannot be undone.</p>
                    <div style="display:flex;gap:10px;justify-content:center;">
                        <button type="button" id="confirmDeleteShopBtn" class="btn" style="background:#e74c3c;color:#fff;font-weight:600;padding:8px 24px;border-radius:8px;">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:600px;">
            <div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div class="modal-body" style="padding:0;background:#000;">
                    <img id="imagePreviewSrc" src="" alt="Preview" style="width:100%;height:auto;display:block;">
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
    #shopTable th,
    #shopTable td {
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
        let table = $('#shopTable').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "{{ route('shops.admin.data') }}",
                "type": "GET",
                "cache": false
            },
            "columns": [
                { "data": null, "name": "SI" },
                { "data": "category" },
                { "data": "product_name" },
                { "data": "price" },
                { "data": "id" }
            ],
            "columnDefs": [
                {
                    "targets": 0,
                    "orderable": false
                },
                {
                    "targets": 4,
                    "orderable": false,
                    "render": function(data, type, row) {
                        let viewBtn = '<button type="button" class="btn btn-sm btn-info view-shop-btn" data-id="' + data + '" title="View"><i class="fas fa-eye"></i></button> ';
                        let editBtn = '<button type="button" class="btn btn-sm btn-warning edit-shop-btn" data-id="' + data + '" title="Edit"><i class="fas fa-edit"></i></button> ';
                        let deleteBtn = '<button type="button" class="btn btn-sm btn-danger delete-shop-btn" data-id="' + data + '" title="Delete"><i class="fas fa-trash"></i></button>';
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

        // Handle view button click
        $(document).on('click', '.view-shop-btn', function() {
            let shopId = $(this).data('id');
            $.ajax({
                url: '/admin/shops/' + shopId + '/show',
                type: 'GET',
                success: function(shop) {
                    $('#viewShopModalLabel').text(shop.product_name);
                    $('#viewShopCategory').text(shop.category);
                    $('#viewShopPrice').text(shop.price);
                    $('#viewShopDescription').html(shop.description || 'No description available.');
                    if (shop.image) {
                        $('#viewShopImage').attr('src', shop.image).show();
                        $('#viewShopNoImage').hide();
                    } else {
                        $('#viewShopImage').hide();
                        $('#viewShopNoImage').show();
                    }
                    $('#viewShopModal').modal('show');
                },
                error: function() {
                    alert('Failed to load shop product details.');
                }
            });
        });

        // Handle edit button click
        $(document).on('click', '.edit-shop-btn', function() {
            let shopId = $(this).data('id');
            $('#editShopModal' + shopId).modal('show');
        });

        // Handle delete button click
        $(document).on('click', '.delete-shop-btn', function() {
            let shopId = $(this).data('id');
            $('#confirmDeleteShopBtn').data('shop-id', shopId);
            $('#deleteShopModal').modal('show');
        });

        // Handle confirm delete
        $(document).on('click', '#confirmDeleteShopBtn', function() {
            let shopId = $(this).data('shop-id');
            $.ajax({
                url: '/admin/shops/' + shopId,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#deleteShopModal').modal('hide');
                    location.reload();
                },
                error: function(error) {
                    alert('Error deleting product!');
                }
            });
        });

        // Reload table after form submission
        $('#addShopForm').on('submit', function(e) {
            let description = $('#add_description').summernote('code');
            if(!description || description.trim() === '' || description === '<p><br></p>') {
            }
        });

        // Reload table when modal is closed
        $(document).on('hidden.bs.modal', '.modal', function() {
            if($(this).attr('id') === 'addShopModal') {
                table.ajax.reload();
                document.getElementById('addShopForm').reset();
                $('#add_description').summernote('reset');
            }
        });
    });

    // Custom file input label update
    document.querySelectorAll('input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function(e) {
            let fileName = e.target.files[0]?.name || 'Choose file';
            let label = e.target.nextElementSibling;
            if(label) label.textContent = fileName;
        });
    });

    // Initialize Summernote editor
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
@stop
