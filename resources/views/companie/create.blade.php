@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Create New Companie 
                </div>
                <div class="card-body">
                    @if($errors->any())
                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger" role="alert">{{ $error }}</div>
                        @endforeach
                    @endif
                   <form method="POST" action="{{ route('companie.store') }}" enctype="multipart/form-data">
                      @csrf
                      <div class="form-group">
                        <label for="name">{{ __("Name")}}</label>
                        <input type="text" class="form-control" name="name" id="name" aria-describedby="name"
                        value="{{ old('name')}}">
                        <small id="name" class="form-text text-muted">{{ __("Enter Companie Name")}}</small>
                      </div>
                      <div class="form-group">
                        <label for="email">{{ __("Email")}}</label>
                        <input type="email" class="form-control" name="email" id="email" aria-describedby="emailHelp" value="{{ old('email')}}">
                        <small id="email" class="form-text text-muted">{{ __("Enter Companie email")}}</small>
                      </div>
                      <div class="form-group">
                        <label for="logo">{{ __("Upload Companie logo")}}</label>
                        <input type="file" class="form-control-file" id="logo" name="logo">
                      </div>
                      <div class="form-group">
                        <label for="website">{{ __("Website")}}</label>
                        <input type="text" class="form-control" name="website_url" id="website" aria-describedby="website" value="{{ old('website_url')}}">
                        <small id="website" class="form-text text-muted">{{ __("Enter Companie website")}}</small>
                      </div>
                      <button type="submit" class="btn btn-primary">{{ __("Create")}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection