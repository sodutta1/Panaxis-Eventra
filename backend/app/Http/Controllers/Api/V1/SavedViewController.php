<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SavedView;
use Illuminate\Http\Request;

/** Per-user saved list views that follow the user across devices. */
class SavedViewController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['module' => ['required', 'string', 'max:80']]);

        return response()->json(['data' => SavedView::where('user_id', $request->user()->id)
            ->where('module', $data['module'])->orderBy('name')->get(['id', 'name', 'query'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'module' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:80'],
            'query' => ['nullable', 'string', 'max:1000'],
        ]);
        $user = $request->user();
        $view = SavedView::updateOrCreate(
            ['user_id' => $user->id, 'module' => $data['module'], 'name' => trim($data['name'])],
            ['company_id' => $user->company_id, 'query' => $data['query'] ?? '']
        );

        return response()->json(['data' => $view->only(['id', 'name', 'query'])], 201);
    }

    public function destroy(Request $request, string $id)
    {
        abort_unless(ctype_digit($id) && strlen($id) <= 18, 404);
        $deleted = SavedView::where('user_id', $request->user()->id)->whereKey((int) $id)->delete();
        abort_unless($deleted, 404);

        return response()->json(['message' => 'Saved view removed.']);
    }
}
