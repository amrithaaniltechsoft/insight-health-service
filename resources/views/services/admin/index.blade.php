@extends('adminlte::page')

@section('title', 'Manage Services')

@section('content_header')
    <div class="row">
        <div class="col-md-12">
            <h1>All Services</h1>
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
            <h3 class="card-title">All Service Categories</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover" id="servicesTable">
                <thead>
                    <tr>
                        <th>SI</th>
                        <th>Category</th>
                        <th>Services</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Category Modal -->
    <div class="modal fade" id="viewCategoryModal" tabindex="-1" role="dialog" aria-labelledby="viewCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:500px;">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.3);">
                <div style="display:flex;background:linear-gradient(135deg,#2a1774,#401ce6);padding:24px 28px;align-items:center;justify-content:space-between;">
                    <h4 style="font-size:1.2rem;font-weight:700;color:#fff;margin:0;" id="viewCategoryModalLabel"></h4>
                    <button type="button" data-dismiss="modal" style="width:30px;height:30px;border-radius:50%;border:none;background:rgba(0,0,0,0.25);color:#fff;font-size:1.2rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0.7;">&times;</button>
                </div>
                <div style="padding:24px 28px;background:#fafbfc;">
                    <p style="font-size:0.95rem;color:#374151;margin:0;" id="viewCategoryDescription">No description available.</p>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<style>
    #servicesTable th,
    #servicesTable td {
        border-left: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
</style>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        let table = $('#servicesTable').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "{{ route('services.admin.data') }}",
                "type": "GET",
                "cache": false
            },
            "columns": [
                { "data": null, "name": "SI" },
                { "data": "category" },
                { "data": "services_count" },
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
                        return '<span class="badge badge-info">' + data + ' service(s)</span>';
                    }
                },
                {
                    "targets": 3,
                    "orderable": false,
                    "render": function(data, type, row) {
                        let manageBtn = '<a href="/admin/services/category/' + data + '" class="btn btn-sm btn-success" title="Manage Services"><i class="fas fa-cog"></i></a>';
                        return manageBtn;
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
    });
</script>
@stop
