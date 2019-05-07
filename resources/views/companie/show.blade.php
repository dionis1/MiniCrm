@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card">
            <img class="card-img-top" src="{{ asset('storage/'.$companie->logo) }}" alt="Card image cap" style="width: 700px;height: 300px;">
            <div class="card-body">
              <p class="card-text">{{ $companie->name }}</p>
              <p class="card-text">{{ $companie->email }}</p>
            </div>
          </div>
          <table class="table">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">{{ __("First Name")}}</th>
                <th scope="col">{{ __("Last Name")}}</th>
                <th scope="col">{{ __("Email")}}</th>
                <th scope="col">{{ __("Phone")}}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($employees as $employee)
                <tr>
                  <th scope="row">{{ $employee->id }}</th>
                  <td>{{ $employee->first_name }}</td>
                  <td>{{ $employee->last_name }}</td>
                  <td>{{ $employee->email }}</td>
                  <td>{{ $employee->phone }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
          {{ $employees->links() }}
        </div>
    </div>
</div>
@endsection