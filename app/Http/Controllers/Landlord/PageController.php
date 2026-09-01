<?php

namespace App\Http\Controllers\Landlord;

use App\DataTables\Landlord\PagesDataTable;
use App\Http\Controllers\Controller;
use App\Models\landlord\LandlordMedia;
use App\Models\landlord\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(PagesDataTable $dataTable)
    {
        return $dataTable->render('landlord.dashboard.page.pages');
    }

    public function create()
    {
        return view('landlord.dashboard.page.create');
    }

    public function store(Request $request)
    {

        $validator = $request->validate([
            'title' => 'required',
            'slug' => 'required|unique:pages,slug',
            'content' => 'required',
            'status' => 'required|in:published,draft,archived',
            'meta_title' => 'nullable',
            'meta_description' => 'nullable',
            'meta_keywords' => 'nullable',
        ]);

        DB::beginTransaction();

    
        try {
            

            $content = $validator['content'];

            preg_match_all('/<img[^>]+src="([^">]+)"/', $content, $matches);
            $urls = $matches[1] ?? [];

            foreach ($urls as $url) {
                $path = str_replace(asset('storage/').'/', '', $url);
                LandlordMedia::where('path', $path)->update(['used' => true]);
            }

            LandlordMedia::cleanupUnused();

            $page = Page::create([
                'title' => $validator['title'],
                'slug' => Str::slug($validator['slug'], '-'),
                'content' => Purifier::clean($validator['content']),
                'status' => $validator['status'],
                'meta' => [
                    'meta_title' => $request->meta_title,
                    'meta_description' => $request->meta_description,
                    'meta_keywords' => $request->meta_keywords
                ]
            ]);

            DB::commit();

            return redirect()->route('landlord.pages')->with('success', 'Page created successfully');

        }catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }

        return view('landlord.dashboard.page.create');
    }

    public function edit(Page $page)
    {
        return view('landlord.dashboard.page.edit', compact('page'));
    }
    
    public function update(Request $request, Page $page)
    {
        $validator = $request->validate([
            'title' => 'required',
            'slug' => 'required|unique:pages,slug,' . $page->id,
            'content' => 'required',
            'status' => 'required|in:published,draft,archived',
            'meta_title' => 'nullable',
            'meta_description' => 'nullable',
            'meta_keywords' => 'nullable',
        ]);

        DB::beginTransaction();

        try {
            $content = $validator['content'];

            // 🧩 পুরানো কনটেন্টের ইমেজ খুঁজে বের করো
            preg_match_all('/<img[^>]+src="([^">]+)"/', $page->content ?? '', $oldMatches);
            $oldUrls = $oldMatches[1] ?? [];

            // 🧩 নতুন কনটেন্টের ইমেজ খুঁজে বের করো
            preg_match_all('/<img[^>]+src="([^">]+)"/', $content, $newMatches);
            $newUrls = $newMatches[1] ?? [];

            // 🧠 নতুন ইউজড ইমেজ গুলো মার্ক করো
            foreach ($newUrls as $url) {
                $path = str_replace(asset('storage/') . '/', '', $url);
                LandlordMedia::where('path', $path)->update(['used' => true]);
            }

            // ⚙️ পুরানো যেগুলো ডিলিট হইছে সেগুলো আনইউজড করো
            foreach ($oldUrls as $url) {
                if (!in_array($url, $newUrls)) {
                    $path = str_replace(asset('storage/') . '/', '', $url);
                    LandlordMedia::where('path', $path)->update(['used' => false]);
                }
            }

            // 🧹 ক্লিনআপ করো
            LandlordMedia::cleanupUnused();

            // ✍️ আপডেট করো
            $page->update([
                'title' => $validator['title'],
                'slug' => Str::slug($validator['slug'], '-'),
                'content' => Purifier::clean($validator['content']),
                'status' => $validator['status'],
                'meta' => [
                    'meta_title' => $request->meta_title,
                    'meta_description' => $request->meta_description,
                    'meta_keywords' => $request->meta_keywords
                ]
            ]);

            DB::commit();

            return redirect()->route('landlord.pages')->with('success', 'Page updated successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function updateStatus(Request $request, Page $page)
    {
        $request->validate([
            'status' => 'required|in:published,draft,archived',
        ]);

        // If status is already the same, no need to update
        if ($page->status === $request->status) {
            return response()->json([
                'message' => "Page status is already '{$request->status}'.",
            ], 200);
        }

        $page->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Page status updated successfully.',
            'data' => [
                'id' => $page->id,
                'status' => $page->status,
            ],
        ], 200);
    }

    public function destroy(Page $page)
    {
        DB::beginTransaction();

        try {
            if ($page->content) {
                preg_match_all('/<img[^>]+src="([^">]+)"/', $page->content, $matches);
                $urls = $matches[1] ?? [];

                if (!empty($urls)) {
                    foreach ($urls as $url) {
                        $path = str_replace(asset('storage/') . '/', '', $url);
                        LandlordMedia::where('path', $path)->update(['used' => false]);
                    }
                }
            }

            LandlordMedia::cleanupUnused();

            $page->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Page deleted successfully.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete page. Please try again later.',
            ], 500);
        }
    }


}
