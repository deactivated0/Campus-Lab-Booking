<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Lab;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Equipment/Index', [
            'labs' => Lab::orderBy('name')->get(['id','name']),
            'items' => Equipment::with('lab')->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'category' => $e->category,
                    'serial_number' => $e->serial_number,
                    'is_active' => $e->is_active,
                    'sort_order' => $e->sort_order,
                    'lab_id' => $e->lab_id,
                    'lab' => $e->lab?->name,
                ]),
            'canManage' => $this->userHasAnyRole($request->user(), ['Admin','LabStaff']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);

        $data = $request->validate([
            'lab_id' => ['required','exists:labs,id'],
            'name' => ['required','string','max:120'],
            'category' => ['nullable','string','max:80'],
            'serial_number' => ['nullable','string','max:80'],
            'is_active' => ['boolean'],
        ]);

        $maxOrder = (int) Equipment::max('sort_order');
        Equipment::create(array_merge($data, ['sort_order' => $maxOrder + 1]));

        return redirect()->back()->with('success', 'Equipment added.');
    }

    public function update(Request $request, Equipment $equipment)
    {
        $this->authorizeManage($request);

        $data = $request->validate([
            'lab_id' => ['required','exists:labs,id'],
            'name' => ['required','string','max:120'],
            'category' => ['nullable','string','max:80'],
            'serial_number' => ['nullable','string','max:80'],
            'is_active' => ['boolean'],
        ]);

        $equipment->update($data);

        return redirect()->back()->with('success', 'Equipment updated.');
    }

    public function destroy(Request $request, Equipment $equipment)
    {
        $this->authorizeManage($request);

        $equipment->delete();
        return redirect()->back()->with('success', 'Equipment deleted.');
    }

    public function reorder(Request $request)
    {
        $this->authorizeManage($request);

        $data = $request->validate([
            'ids' => ['required','array'],
            'ids.*' => ['integer','exists:equipment,id'],
        ]);

        foreach ($data['ids'] as $index => $id) {
            Equipment::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['ok' => true]);
    }

    private function authorizeManage(Request $request): void
    {
        if (!$this->userHasAnyRole($request->user(), ['Admin','LabStaff'])) abort(403);
    }
}
