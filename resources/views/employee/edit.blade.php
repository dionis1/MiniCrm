@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ __("Edit Employee")}}
                </div>
                <div class="card-body">
                    @if($errors->any())
                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger" role="alert">{{ $error }}</div>
                        @endforeach
                    @endif
                   <form method="POST" action="{{ route('employee.update', ['id'=>$employee->id]) }}" >
                      @csrf
                      {{ method_field('PUT') }}                      
                      <div class="form-group">
                        <label for="first_name">{{ __("First Name")}}</label>
                        <input type="text" class="form-control" name="first_name" id="first_name" aria-describedby="first_name"
                        value="{{ $employee->first_name }}">
                        <small id="first_name" class="form-text text-muted">{{ __("Enter First Name")}}</small>
                      </div>
                      <div class="form-group">
                        <label for="last_name">{{ __("Last Name")}}</label>
                        <input type="text" class="form-control" name="last_name" id="last_name" aria-describedby="last_name"
                        value="{{ $employee->last_name }}">
                        <small id="last_name" class="form-text text-muted">{{ __("Enter Last Name")}}</small>
                      </div>
                      <div class="form-group">
                        <label for="companie_id">{{ __("Select Companie")}}</label>
                        <select name="companie_id" class="form-control" id="companie_id">
                          <option></option>
                          @foreach($companies as $companie)
                           <option value="{{ $companie->id }}" {{ $employee->companie_id =  $companie->id ? "selected":"" }}> {{ $companie->name }}</option>
                          @endforeach
                        </select>
                        <small id="email" class="form-text text-muted">{{ __("Select Companie")}}</small>
                      </div>
                      <div class="form-group">
                        <label for="email">{{ __("Email")}}</label>
                        <input type="email" class="form-control" name="email" id="email" aria-describedby="emailHelp" value="{{ $employee->email }}">
                        <small id="email" class="form-text text-muted">{{ __("Enter email")}}</small>
                      </div>
                      <div class="form-group">
                        <label for="phone">{{ __("Phone")}}</label>
                        <input type="number" class="form-control" name="phone" id="phone" aria-describedby="phone" value="{{ $employee->phone }}">
                        <small id="phone" class="form-text text-muted">{{ __("Enter Phone Number")}}</small>
                      </div>
                      <button type="submit" class="btn btn-primary">{{ __("Create")}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection