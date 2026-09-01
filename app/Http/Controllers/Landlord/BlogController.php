<?php

namespace App\Http\Controllers\Landlord;

use App\DataTables\Landlord\BlogsDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\LandlordMedia;
use App\Models\landlord\Blog;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Str;
class BlogController extends Controller
{
    public function index(BlogsDataTable $dataTable)
    {
        return $dataTable->render('landlord.dashboard.blog.blogs');
    }

    public function create()
    {
        return view('landlord.dashboard.blog.create');
    }

   public function store(Request $request)
    {
        $validator = $request->validate([
            'title' => 'required',
            'slug' => 'required|unique:blogs,slug',
            'content' => 'required',
            'status' => 'required|in:published,draft,archived',
            'meta_title' => 'nullable',
            'meta_description' => 'nullable',
            'meta_keywords' => 'nullable',
            'image' => 'nullable|image|max:4096'
        ]);

        DB::beginTransaction();

        try {
            // 📌 Upload Thumbnail
            $thumbPath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $thumbPath = $file->store('blog/thumbnails', 'public');

                LandlordMedia::create([
                    'path' => $thumbPath,
                    'disk' => 'public',
                    'type' => $file->getClientMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'used' => true,
                ]);
            }

            // 📌 Content Image Tracking
            $content = $validator['content'];
            preg_match_all('/<img[^>]+src="([^">]+)"/', $content, $matches);
            $urls = $matches[1] ?? [];
            foreach ($urls as $url) {
                $path = str_replace(asset('storage/') . '/', '', $url);
                LandlordMedia::where('path', $path)->update(['used' => true]);
            }
            LandlordMedia::cleanupUnused();

            // 📌 Create Blog
            Blog::create([
                'title' => $validator['title'],
                'slug' => Str::slug($validator['slug'], '-'),
                'content' => Purifier::clean($validator['content']),
                'status' => $validator['status'],
                'image' => $thumbPath,
                'meta' => [
                    'meta_title' => $request->meta_title,
                    'meta_description' => $request->meta_description,
                    'meta_keywords' => $request->meta_keywords
                ]
            ]);

            DB::commit();
            return redirect()->route('landlord.blogs')->with('success', 'Blog created successfully');

        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function edit(Blog $blog)
    {
        return view('landlord.dashboard.blog.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog)
    {
        $validator = $request->validate([
            'title' => 'required',
            'slug' => 'required|unique:blogs,slug,' . $blog->id,
            'content' => 'required',
            'status' => 'required|in:published,draft,archived',
            'meta_title' => 'nullable',
            'meta_description' => 'nullable',
            'meta_keywords' => 'nullable',
            'image' => 'nullable|image|max:4096'
        ]);

        DB::beginTransaction();

        try {
            // 📌 Thumbnail update
            $thumbPath = $blog->image;
            if ($request->hasFile('image')) {

                if ($blog->image) {
                    LandlordMedia::where('path', $blog->image)->update(['used' => false]);
                }

                $file = $request->file('image');
                $thumbPath = $file->store('blog/thumbnails', 'public');

                LandlordMedia::create([
                    'path' => $thumbPath,
                    'disk' => 'public',
                    'type' => $file->getClientMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'used' => true,
                ]);
            }

            // 📌 Content image tracking
            preg_match_all('/<img[^>]+src="([^">]+)"/', $blog->content ?? '', $oldMatches);
            $oldUrls = $oldMatches[1] ?? [];
            preg_match_all('/<img[^>]+src="([^">]+)"/', $validator['content'], $newMatches);
            $newUrls = $newMatches[1] ?? [];

            foreach ($newUrls as $url) {
                $path = str_replace(asset('storage/') . '/', '', $url);
                LandlordMedia::where('path', $path)->update(['used' => true]);
            }
            foreach ($oldUrls as $url) {
                if (!in_array($url, $newUrls)) {
                    $path = str_replace(asset('storage/') . '/', '', $url);
                    LandlordMedia::where('path', $path)->update(['used' => false]);
                }
            }
            LandlordMedia::cleanupUnused();

            // 📌 Update Blog
            $blog->update([
                'title' => $validator['title'],
                'slug' => Str::slug($validator['slug'], '-'),
                'content' => Purifier::clean($validator['content']),
                'status' => $validator['status'],
                'image' => $thumbPath,
                'meta' => [
                    'meta_title' => $request->meta_title,
                    'meta_description' => $request->meta_description,
                    'meta_keywords' => $request->meta_keywords
                ]
            ]);

            DB::commit();
            return redirect()->route('landlord.blogs')->with('success', 'Blog updated successfully');

        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }
    }


    public function updateStatus(Request $request, Blog $blog)
    {
        $request->validate([
            'status' => 'required|in:published,draft,archived',
        ]);

        // If status is already the same, no need to update
        if ($blog->status === $request->status) {
            return response()->json([
                'message' => "Blog status is already '{$request->status}'.",
            ], 200);
        }

        $blog->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Blog status updated successfully.',
            'data' => [
                'id' => $blog->id,
                'status' => $blog->status,
            ],
        ], 200);
    }

    public function destroy(Blog $blog)
    {
        DB::beginTransaction();

        try {
            if ($blog->content) {
                preg_match_all('/<img[^>]+src="([^">]+)"/', $blog->content, $matches);
                $urls = $matches[1] ?? [];

                if (!empty($urls)) {
                    foreach ($urls as $url) {
                        $path = str_replace(asset('storage/') . '/', '', $url);
                        LandlordMedia::where('path', $path)->update(['used' => false]);
                    }
                }
            }

            LandlordMedia::cleanupUnused();

            $blog->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'blog deleted successfully.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete blog. Please try again later.',
            ], 500);
        }
    }


}
