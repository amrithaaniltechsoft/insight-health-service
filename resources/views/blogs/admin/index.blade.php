@extends('adminlte::page')

@section('title', 'Manage Blogs')

@section('content_header')
    <div class="row">
        <div class="col-md-8">
            <h1>Manage Blogs</h1>
        </div>
        <div class="col-md-4 text-right">
            <button type="button" class="btn" style="background-color: #28a745; color: white; border: none;" data-toggle="modal" data-target="#addBlogModal">
                <i class="fas fa-plus mr-2"></i>Add New Blog
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
            <h3 class="card-title">Blog Articles List</h3>
        </div>
        <div class="card-body table-responsive">
            @if(true)
                <table class="table table-striped table-hover" id="blogsTable">
                    <thead>
                        <tr>
                            <th>SI</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- Add Blog Modal -->
    <div class="modal fade" id="addBlogModal" tabindex="-1" role="dialog" aria-labelledby="addBlogModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="addBlogModalLabel" style="font-size: 1.5rem;">Add New Blog Article</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('blogs.admin.store') }}" method="POST" enctype="multipart/form-data" id="addBlogForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="category_id" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Category <span class="text-danger">*</span></label>
                                    <select class="form-control" id="category_id" name="category_id" required onchange="loadSubCategories(this.value, 'sub_category_id')">
                                        <option value="">-- Select Category --</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sub_category_id" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Sub Category</label>
                                    <select class="form-control" id="sub_category_id" name="sub_category_id">
                                        <option value="">-- Select Sub Category --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="title" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Blog Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" placeholder="Enter blog title" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="image" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Featured Image</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="image" name="image" accept="image/*" required>
                                            <label class="custom-file-label" for="image">Choose file</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control summernote" id="description" name="description" rows="5" placeholder="Enter blog description"></textarea>
                        </div>

                        <hr>
                        <h5 style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">SEO Settings</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meta_title" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Title</label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title" placeholder="Enter meta title">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meta_keywords" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Keywords</label>
                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" placeholder="Enter meta keywords (comma separated)">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="meta_description" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="3" placeholder="Enter meta description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>Create Blog
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Blog Modals (for each blog) -->
    @foreach($blogs as $blog)
    <div class="modal fade" id="editBlogModal{{ $blog->id }}" tabindex="-1" role="dialog" aria-labelledby="editBlogModalLabel{{ $blog->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="editBlogModalLabel{{ $blog->id }}" style="font-size: 1.5rem;">Edit Blog Article</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('blogs.admin.update', $blog->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_category_{{ $blog->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Category <span class="text-danger">*</span></label>
                                    <select class="form-control" id="edit_category_{{ $blog->id }}" name="category_id" required onchange="loadSubCategories(this.value, 'edit_sub_category_{{ $blog->id }}', '{{ $blog->id }}')">
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ $blog->category_id == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_sub_category_{{ $blog->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Sub Category</label>
                                    <select class="form-control" id="edit_sub_category_{{ $blog->id }}" name="sub_category_id">
                                        <option value="">-- Select Sub Category --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_title_{{ $blog->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Blog Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_title_{{ $blog->id }}" name="title" value="{{ $blog->title }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_image_{{ $blog->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Featured Image</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="edit_image_{{ $blog->id }}" name="image" accept="image/*">
                                            <label class="custom-file-label" for="edit_image_{{ $blog->id }}">Choose file</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($blog->image)
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <small class="d-block text-muted mb-2">Current image:</small>
                                        <img src="{{ str_starts_with($blog->image, 'http') ? $blog->image : asset($blog->image) }}" alt="{{ $blog->title }}" class="img-fluid" style="max-height: 150px;">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="edit_description_{{ $blog->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control summernote" id="edit_description_{{ $blog->id }}" name="description" rows="5">{{ $blog->description }}</textarea>
                        </div>

                        <hr>
                        <h5 style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">SEO Settings</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_meta_title_{{ $blog->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Title</label>
                                    <input type="text" class="form-control" id="edit_meta_title_{{ $blog->id }}" name="meta_title" value="{{ $blog->meta_title }}" placeholder="Enter meta title">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_meta_keywords_{{ $blog->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Keywords</label>
                                    <input type="text" class="form-control" id="edit_meta_keywords_{{ $blog->id }}" name="meta_keywords" value="{{ $blog->meta_keywords }}" placeholder="Enter meta keywords (comma separated)">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit_meta_description_{{ $blog->id }}" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Meta Description</label>
                            <textarea class="form-control" id="edit_meta_description_{{ $blog->id }}" name="meta_description" rows="3" placeholder="Enter meta description">{{ $blog->meta_description }}</textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>Update Blog
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <!-- View Blog Modal -->
    <div class="modal fade" id="viewBlogModal" tabindex="-1" role="dialog" aria-labelledby="viewBlogModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.3);">

                <div style="display:flex;background:linear-gradient(135deg,#2a1774,#401ce6);">
                    <div id="viewBlogImageWrapper" style="width:300px;min-height:200px;flex-shrink:0;display:flex;align-items:center;justify-content:center;padding:16px;">
                        <img id="viewBlogImage" src="" alt="" style="max-width:100%;max-height:200px;border-radius:6px;display:none;box-shadow:0 4px 20px rgba(0,0,0,0.3);">
                        <div id="viewBlogNoImage" style="text-align:center;">
                            <i class="fas fa-image" style="font-size:3.5rem;color:rgba(255,255,255,0.15);"></i>
                        </div>
                    </div>
                    <div style="flex:1;padding:24px 24px 24px 4px;display:flex;flex-direction:column;justify-content:center;">
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;align-self:flex-start;">
                            <span id="viewBlogCategory" style="display:inline-block;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);color:#fff;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:3px 12px;border-radius:50px;border:1px solid rgba(255,255,255,0.25);"></span>
                            <span id="viewBlogSubCategory" style="display:none;background:rgba(181,102,214,0.3);backdrop-filter:blur(8px);color:#fff;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:3px 12px;border-radius:50px;border:1px solid rgba(181,102,214,0.4);"></span>
                        </div>
                        <h2 id="viewBlogModalLabel" style="font-size:1.2rem;font-weight:700;color:#fff;margin:0 0 8px 0;line-height:1.4;text-shadow:0 2px 6px rgba(0,0,0,0.3);"></h2>
                    </div>
                    <button type="button" data-dismiss="modal" style="position:absolute;top:10px;right:12px;width:30px;height:30px;border-radius:50%;border:none;background:rgba(0,0,0,0.25);color:#fff;font-size:1.2rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0.7;">&times;</button>
                </div>

                <div style="max-height:40vh;overflow-y:auto;padding:20px 28px 24px;background:#fafbfc;">
                    <div id="viewBlogDescription" class="view-blog-description" style="font-size:0.95rem;line-height:1.85;color:#374151;"></div>

                    <hr style="margin:20px 0;border-color:#e5e7eb;">

                    <div>
                        <h6 style="font-weight:700;color:#1a1a2e;margin-bottom:12px;font-size:0.9rem;text-transform:uppercase;letter-spacing:0.3px;">SEO Information</h6>
                        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                            <div style="margin-bottom:10px;">
                                <strong style="font-size:0.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.3px;">Meta Title</strong>
                                <p id="viewBlogMetaTitle" style="margin:4px 0 0 0;color:#374151;font-size:0.9rem;">N/A</p>
                            </div>
                            <div style="margin-bottom:10px;">
                                <strong style="font-size:0.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.3px;">Meta Description</strong>
                                <p id="viewBlogMetaDescription" style="margin:4px 0 0 0;color:#374151;font-size:0.9rem;">N/A</p>
                            </div>
                            <div>
                                <strong style="font-size:0.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.3px;">Meta Keywords</strong>
                                <p id="viewBlogMetaKeywords" style="margin:4px 0 0 0;color:#374151;font-size:0.9rem;">N/A</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border:none;background:transparent;box-shadow:none;">
                <div class="text-right mb-2">
                    <button type="button" class="btn btn-sm btn-dark" data-dismiss="modal"><i class="fas fa-times"></i></button>
                </div>
                <img id="imagePreviewSrc" src="" alt="Preview" style="width:100%;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteBlogModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <div class="modal-body text-center" style="padding:40px 32px 32px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:#fff0f0;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="fas fa-trash-alt" style="font-size:1.5rem;color:#e74c3c;"></i>
                    </div>
                    <h5 style="font-weight:700;color:#212529;margin-bottom:8px;">Delete Blog</h5>
                    <p style="color:#6c757d;font-size:0.9rem;margin-bottom:24px;">Are you sure you want to delete this blog? This action cannot be undone.</p>
                    <div style="display:flex;gap:10px;justify-content:center;">
                        <button type="button" class="btn" data-dismiss="modal" style="border:1px solid #dee2e6;color:#495057;font-weight:600;padding:8px 20px;border-radius:8px;">Cancel</button>
                        <button type="button" id="confirmDeleteBtn" class="btn" style="background:#e74c3c;color:#fff;font-weight:600;padding:8px 24px;border-radius:8px;">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<style>
    #blogsTable th,
    #blogsTable td {
        border-left: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
    }

    /* Modal right side positioning */
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

    /* ===== View Blog Description Styles ===== */
    .view-blog-description { font-size:0.95rem; line-height:1.85; color:#374151; }
    .view-blog-description p { margin-bottom: 1rem; }
    .view-blog-description h1, .view-blog-description h2,
    .view-blog-description h3, .view-blog-description h4 {
        color: #1a1a2e; font-weight: 700; margin-top: 1.4rem; margin-bottom: 0.6rem;
    }
    .view-blog-description img { max-width: 100%; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
    .view-blog-description blockquote {
        border-left: 4px solid #17a2b8; padding: 12px 20px;
        background: linear-gradient(135deg, #f0f9fb, #e8f4f8);
        border-radius: 0 8px 8px 0; color: #1a1a2e; font-style: italic; margin: 1.2rem 0;
    }
</style>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        // Initialize DataTables with AJAX
        $(document).ready(function() {
            let table = $('#blogsTable').DataTable({
                "processing": true,
                "serverSide": false,
                "deferLoading": 0, // Do not show any rows until data is loaded via AJAX
                "ajax": {
                    "url": "{{ route('blogs.admin.data') }}",
                    "type": "GET",
                    "cache": false
                },
                "columns": [
                    { "data": null, "name": "SI" },
                    { "data": "title" },
                    { "data": "category" },
                    { "data": "image", "name": "Image" },
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
                            return '<span class="badge badge-info">' + data + '</span>';
                        }
                    },
                    {
                        "targets": 3,
                        "orderable": false,
                        "render": function(data, type, row) {
                            if (data) {
                                return '<a href="javascript:void(0)" class="blog-image-thumb" data-image="' + data + '"><img src="' + data + '" alt="Blog Image" style="width:60px;height:40px;object-fit:cover;border-radius:4px;cursor:pointer;"></a>';
                            }
                            return '<div style="width:60px;height:40px;background:#f1f3f5;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#adb5bd;"><i class="fas fa-image"></i></div>';
                        }
                    },
                    {
                        "targets": 4,
                        "orderable": false,
                        "render": function(data, type, row) {
                            let viewBtn = '<button type="button" class="btn btn-sm btn-info view-blog-btn" data-id="' + data + '" title="View"><i class="fas fa-eye"></i></button> ';
                            let editBtn = '<button type="button" class="btn btn-sm btn-warning edit-blog-btn" data-id="' + data + '" title="Edit"><i class="fas fa-edit"></i></button> ';
                            let deleteBtn = '<button type="button" class="btn btn-sm btn-danger delete-blog-btn" data-id="' + data + '" title="Delete"><i class="fas fa-trash"></i></button>';
                            return viewBtn + editBtn + deleteBtn;
                        }
                    }
                ],
                "pageLength": 10,
                "order": [[2, "asc"]]
            });



        // Show table once data is loaded for the first time
        table.on('xhr', function() { $('#blogsTable').show(); });

        // Re-number SI column after each draw (sort, page, etc.)
        table.on('draw.dt', function() {
            var api = $(this).DataTable();
            var pageInfo = api.page.info();
            var rows = api.rows({page: 'current', order: 'current'}).nodes();
            $(rows).each(function(i) {
                $('td:eq(0)', this).text(pageInfo.start + i + 1);
            });
        });

        // Handle view button click — fetch blog data and open modal
        $(document).on('click', '.view-blog-btn', function() {
            let blogId = $(this).data('id');
            $.ajax({
                url: '/admin/blogs/' + blogId + '/show',
                type: 'GET',
                success: function(blog) {
                    $('#viewBlogModalLabel').text(blog.title);
                    $('#viewBlogCategory').text(blog.category);
                    if (blog.sub_category) {
                        $('#viewBlogSubCategory').text(blog.sub_category).show();
                    } else {
                        $('#viewBlogSubCategory').hide();
                    }
                    $('#viewBlogDescription').html(blog.description);
                    $('#viewBlogMetaTitle').text(blog.meta_title || 'N/A');
                    $('#viewBlogMetaDescription').text(blog.meta_description || 'N/A');
                    $('#viewBlogMetaKeywords').text(blog.meta_keywords || 'N/A');
                    if (blog.image) {
                        $('#viewBlogImage').attr('src', blog.image).show();
                        $('#viewBlogNoImage').hide();
                    } else {
                        $('#viewBlogImage').hide();
                        $('#viewBlogNoImage').show();
                    }
                    $('#viewBlogModal').modal('show');
                },
                error: function() {
                    alert('Failed to load blog details.');
                }
            });
        });

        // Handle image thumbnail click
        $(document).on('click', '.blog-image-thumb', function() {
            var src = $(this).data('image');
            $('#imagePreviewSrc').attr('src', src);
            $('#imagePreviewModal').modal('show');
        });

        // Load sub-categories for a given category into a select element
        window.loadSubCategories = function(categoryId, targetSelectId, blogId) {
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
                    // If editing a blog, pre-select its current sub_category_id
                    if (blogId) {
                        var currentBlogId = blogId;
                        // Get sub_category_id from the blog row data
                        var row = $('#blog-table').DataTable().rows().data().filter(function(r) { return r.id == currentBlogId; });
                        if (row.length) {
                            $select.val(row[0].sub_category_id);
                        }
                    }
                }
            });
        };

        // Handle edit button click — load sub-categories for the selected category
        $(document).on('click', '.edit-blog-btn', function() {
            let blogId = $(this).data('id');
            let categorySelect = document.getElementById('edit_category_' + blogId);
            if (categorySelect && categorySelect.value) {
                loadSubCategories(categorySelect.value, 'edit_sub_category_' + blogId, blogId);
            }
            $('#editBlogModal' + blogId).modal('show');
        });

        // Handle delete button click
        $(document).on('click', '.delete-blog-btn', function() {
            let blogId = $(this).data('id');
            $('#confirmDeleteBtn').data('blog-id', blogId);
            $('#deleteBlogModal').modal('show');
        });

        // Handle confirm delete
        $(document).on('click', '#confirmDeleteBtn', function() {
            let blogId = $(this).data('blog-id');
            $.ajax({
                url: '/admin/blogs/' + blogId,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#deleteBlogModal').modal('hide');
                    location.reload();
                },
                error: function(error) {
                    alert('Error deleting blog!');
                }
            });
        });

        // Reload table after form submission
        $('#addBlogForm').on('submit', function(e) {
            let description = $('#description').summernote('code');
            if(!description || description.trim() === '' || description === '<p><br></p>') {
                e.preventDefault();
                alert('Please enter a description for the blog.');
                $('#description').summernote('focus');
                return false;
            }
        });

        // Reload table when modal is closed
        $(document).on('hidden.bs.modal', '.modal', function() {
            if($(this).attr('id') === 'addBlogModal') {
                table.ajax.reload();
                document.getElementById('addBlogForm').reset();
                $('#description').summernote('reset');
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

    // Form validation for Edit Blog modals
    document.querySelectorAll('form[action*="/admin/blogs/"][method="POST"]').forEach(function(form) {
        if(form.id !== 'addBlogForm') {
            form.addEventListener('submit', function(e) {
                let textareas = form.querySelectorAll('.summernote');
                for(let textarea of textareas) {
                    let description = $(textarea).summernote('code');
                    if(!description || description.trim() === '' || description === '<p><br></p>') {
                        e.preventDefault();
                        alert('Please enter a description for the blog.');
                        $(textarea).summernote('focus');
                        return false;
                    }
                }
            });
        }
    });
</script>
@endsection
