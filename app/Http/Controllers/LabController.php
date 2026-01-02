<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LabController extends Controller
{
    public function index(Request $request)
    {
        if (!$this->userHasRole($request->user(), 'Admin')) abort(403);

        return Inertia::render('Labs/Index', [
            'labs' => Lab::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->userHasRole($request->user(), 'Admin')) abort(403);

        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'location' => ['nullable','string','max:120'],
            'capacity' => ['nullable','integer','min:0'],
            'is_active' => ['boolean'],
        ]);

        Lab::create($data);

        return redirect()->back()->with('success', 'Lab created.');
    }

    public function update(Request $request, Lab $lab)
    {
        if (!$this->userHasRole($request->user(), 'Admin')) abort(403);

        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'location' => ['nullable','string','max:120'],
            'capacity' => ['nullable','integer','min:0'],
            'is_active' => ['boolean'],
        ]);

        $lab->update($data);

        return redirect()->back()->with('success', 'Lab updated.');
    }

    public function destroy(Request $request, Lab $lab)
    {
        if (!$this->userHasRole($request->user(), 'Admin')) abort(403);

        $lab->delete();
        return redirect()->back()->with('success', 'Lab deleted.');
    }
}
