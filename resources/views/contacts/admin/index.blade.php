@extends('adminlte::page')

@section('title', 'Manage Contact')

@section('content_header')
    <div class="row">
        <div class="col-md-8">
            <h1>Manage Contact</h1>
        </div>
        <div class="col-md-4 text-right">
            <button type="button" class="btn" style="background-color: #28a745; color: white; border: none;" data-toggle="modal" data-target="#editContactModal">
                <i class="fas fa-edit mr-2"></i>Edit Contact
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

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contact Details</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width:200px;">Contact 1</th>
                            <td>{{ $contact->contact1 ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Contact 2</th>
                            <td>{{ $contact->contact2 ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $contact->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>{!! nl2br(e($contact->address ?? 'N/A')) !!}</td>
                        </tr>
                        <tr>
                            <th>Mon - Fri</th>
                            <td>{{ $contact->mon_fri ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Saturday</th>
                            <td>{{ $contact->saturday ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Sunday</th>
                            <td>{{ $contact->sunday ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Contact Modal -->
    <div class="modal fade" id="editContactModal" tabindex="-1" role="dialog" aria-labelledby="editContactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-right" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #28a745; color: white;">
                    <h5 class="modal-title text-center w-100" id="editContactModalLabel" style="font-size: 1.5rem;">Edit Contact</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; position: absolute; right: 1rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('contacts.admin.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact1" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Contact 1</label>
                                    <input type="tel" class="form-control" id="contact1" name="contact1" value="{{ $contact->contact1 ?? '' }}" placeholder="Enter contact number 1" oninput="this.value=this.value.replace(/[^0-9+]/g,'')">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact2" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Contact 2</label>
                                    <input type="tel" class="form-control" id="contact2" name="contact2" value="{{ $contact->contact2 ?? '' }}" placeholder="Enter contact number 2" oninput="this.value=this.value.replace(/[^0-9+]/g,'')">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ $contact->email ?? '' }}" placeholder="Enter email address">
                        </div>
                        <div class="form-group">
                            <label for="address" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="4" placeholder="Enter address">{{ $contact->address ?? '' }}</textarea>
                        </div>
                        <hr>
                        <h5 style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Opening Hours</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="mon_fri" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Mon - Fri</label>
                                    <input type="text" class="form-control" id="mon_fri" name="mon_fri" value="{{ $contact->mon_fri ?? '' }}" placeholder="08:00 - 21:00">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="saturday" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Saturday</label>
                                    <input type="text" class="form-control" id="saturday" name="saturday" value="{{ $contact->saturday ?? '' }}" placeholder="08:00 - 21:00">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sunday" style="color: #6c757d; font-size: 16px; font-weight: 600 !important;">Sunday</label>
                                    <input type="text" class="form-control" id="sunday" name="sunday" value="{{ $contact->sunday ?? '' }}" placeholder="08:00 - 21:00">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-save mr-2"></i>Update Contact
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
