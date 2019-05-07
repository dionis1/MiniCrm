@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ __("Companie")}}
                    <a href="{{ route('companie.create') }}" type="button" class="btn btn-success">
                    {{ __("Create new Companie")}}</a>
                </div>
                <div class="card-body">
                   <table class="table">
                      <thead>
                        <tr>
                          <th scope="col">#</th>
                          <th scope="col">{{ __("Name")}}</th>
                          <th scope="col">{{ __("Email")}}</th>
                          <th scope="col">{{ __("Action")}}</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($companies as $companie)
                            <tr>
                              <th scope="row">{{ $companie->id }}</th>
                              <td>{{ $companie->name }}</td>
                              <td>{{ $companie->email }}</td>
                              <td>
                                <a type="button" class="btn btn-info" href="{{ route('companie.edit', ['id' => $companie->id]) }}">{{ __("Edit")}}</a>
                                <a type="button" class="btn btn-warning" href="{{ route('companie.show', ['id' => $companie->id]) }}">{{ __("Show")}}</a>
                                <form action="{{ route('companie.destroy', ['id' => $companie->id]) }}" method="POST">
                                     @csrf
                                    {{ method_field('DELETE') }}
                                    <button class="btn btn-danger" type="submit" onclick="return confirm('Are you sure you want to delete this item?');">{{ __("Delete")}}</button>
                                </form>
                              </td>
                            </tr>
                        @endforeach
                      </tbody>
                    </table>
                    {{ $companies->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection