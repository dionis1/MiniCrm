<?php

namespace App\Http\Controllers;

use App\Companie;
use Auth;
use Illuminate\Http\Request;
use App\Http\Requests\CreateCompanie;
use App\Http\Requests\UpdateCompanie;
use App\Notifications\CompanyCreated;

use Illuminate\Support\Facades\Storage;

class CompanieController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {  
       $companies = Companie::paginate(10);
       return view('companie.index',compact('companies'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      return view('companie.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateCompanie $request)
    {   
        $data = $request->all();
        if ($request->hasFile('logo')) {
            $path =$request->file('logo')->store('public');
            $filename = explode("/",$path);
            $data['logo'] = $filename[1];
        }
        Companie::create($data);
        $user = Auth::user();
        $user->notify(new CompanyCreated($request->name));
        return redirect('/companie')->with('success', 'Companie has been created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Companie  $companie
     * @return \Illuminate\Http\Response
     */
    public function show(Companie $companie)
    {   
        $employees = $companie->employee()->paginate(10);
        return view('companie.show',compact('companie','employees'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Companie  $companie
     * @return \Illuminate\Http\Response
     */
    public function edit(Companie $companie)
    {
       return view('companie.edit',compact('companie'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Companie  $companie
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCompanie $request, Companie $companie)
    {
       $data = $request->all();
        if ($request->hasFile('logo')) {
            $path =$request->file('logo')->store('public');
            $filename = explode("/",$path);
            $data['logo'] = $filename[1];
        }
        Companie::find($companie->id)->update($data);
        return redirect('/companie')->with('success', 'Companie has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Companie  $companie
     * @return \Illuminate\Http\Response
     */
    public function destroy(Companie $companie)
    {
        Companie::find($companie->id)->delete();
        return redirect('/companie')->with('success', 'Companie has been deleted');
    }
}
