<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Like;
use App\Models\Posts;
use App\Models\SavedPosts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OuterController extends Controller
{
  public function index(Request $request)
  {
    $posts = Posts::query()->with(['comments', 'users:id,image'])
      ->when($request->search, fn ($q, $key) => $q->where('description', 'like', "%{$key}%"))
      ->when($request->filtered, function ($q, $value) {
        switch ($value) {
          case 'latest':
            $q->latest();
            break;
          case 'oldest':
            $q->oldest();
            break;

          default:
            abort(404);
            break;
        }
      });

    return inertia('Posts', [
      'title' => "CUY UNIVERSE",
      'root' => 'HOME',
      'description' => "Tempat Nongkrongnya Programmer Indie",
      'posts' => PostResource::collection($posts->latest()->paginate(22)->withQueryString()),
      'filter' => $request->only(['search', 'page', 'filtered'])
    ]);
  }

  public function Teams()
  {
    return Inertia::render('Teams', [
      'title' => "Coders Contributor",
      'root' => "TEAMS",
      'description' => "Cuy Universe Dev",
      'repo_link' => "https://github.com/deaaprizal/laract9"
    ]);
  }

  public function find($post_id)
  {
    $posts = Posts::with('comments.users:username,image')->withCount('likes')->where('id', $post_id)->first();

    if ($posts == null) {
      return abort(404);
    }

    if (Auth::user()) {
      $isSavedPost = SavedPosts::where('post_id', $post_id)->where('user_id', Auth::user()->id)->get();
      $isLikedPost = Like::where('post_id', $post_id)->where('user_id', Auth::user()->id)->get();
    }

    return Inertia::render('Post', [
      'posts' => $posts->only(['id', 'description', 'image', 'author', 'created_at', 'likes_count']),
      'is_saved_post' => Auth::user() ? count($isSavedPost) !== 0 : null,
      'is_liked_post' => Auth::user() ? count($isLikedPost) !== 0 : null,
      'comments' => $posts->comments,
      'author_image' => $posts->users->image,
      'title' => "Postingan Dari CuyPeople",
      'description' => "komentari postingan ini",
      'next' => 'HOME',
      'root' => 'HOME',
      'page' => 'POSTING COMMENT',
      'nextRoute' => 'outer.main'
    ]);
  }

  public function MorePosts(Request $request)
  {
    $posts = Posts::query()
      ->when($request->keyword, fn ($q, $key) => $q->where('description', 'like', "%{$key}%"))
      ->with(['comments', 'users:id,image'])
      ->paginate(12);

    return response()->json($posts, 200);
  }
}
