<?php
namespace Modules\Stories\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Deck\Models\Deck;
use Modules\Language\Models\Language;
use Modules\Stories\Models\Story;
use Modules\Stories\Models\StoryRead;
use Modules\Vocabulary\Models\Term;
use Modules\Vocabulary\Services\GeminiEnrichmentService;

class StoryController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = auth()->id();
        $sort   = $request->get('sort', 'desc');
        $sort   = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';

        $stories = Story::where('user_id', $userId)
            ->with('language', 'deck')
            ->withCount('reads')
            ->orderBy('id', $sort)
            ->get()
            ->map(function (Story $story) use ($userId) {
                $story->is_read = StoryRead::where('user_id', $userId)
                    ->where('story_id', $story->id)
                    ->exists();
                return $story;
            });

        return Inertia::render('Stories::Stories/Index', [
            'stories' => $stories,
            'sort'    => $sort,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Stories::Stories/Create', [
            'languages' => Language::where('is_active', true)->get(),
            'decks'     => auth()->user()->decks()->with('sourceLanguage')->get(['id', 'title', 'source_language_id', 'color']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'level'       => 'nullable|in:A1,A2,B1,B2,C1,C2',
            'deck_id'     => 'nullable|exists:decks,id',
            'language_id' => 'nullable|exists:languages,id',
            'audio'       => 'nullable|file|mimes:mp3,ogg,wav,aac,m4a|max:51200',
        ]);

        if (!empty($data['deck_id'])) {
            abort_if(
                Deck::where('id', $data['deck_id'])->where('user_id', auth()->id())->doesntExist(),
                403
            );
        }

        unset($data['audio']);
        $story = Story::create(array_merge($data, ['user_id' => auth()->id()]));

        if ($request->hasFile('audio')) {
            $ext  = $request->file('audio')->getClientOriginalExtension();
            $path = $request->file('audio')->storeAs("story_audio/{$story->id}", "audio.{$ext}", 'public');
            $story->update(['audio_path' => $path]);
        }

        return redirect()->route('stories.show', $story)->with('success', 'Hekayə əlavə edildi!');
    }

    public function show(Story $story): Response
    {
        abort_if($story->user_id !== auth()->id(), 403);

        $story->load('deck.sourceLanguage', 'deck.targetLanguage', 'language');

        $knownWords = $story->deck_id
            ? Term::where('deck_id', $story->deck_id)->pluck('normalized_term')->toArray()
            : [];

        StoryRead::updateOrCreate(
            ['user_id' => auth()->id(), 'story_id' => $story->id],
            ['read_at' => now()]
        );

        return Inertia::render('Stories::Stories/Show', [
            'story'      => $story,
            'knownWords' => $knownWords,
        ]);
    }

    public function edit(Story $story): Response
    {
        abort_if($story->user_id !== auth()->id(), 403);

        return Inertia::render('Stories::Stories/Edit', [
            'story'     => $story,
            'languages' => Language::where('is_active', true)->get(),
            'decks'     => auth()->user()->decks()->get(['id', 'title', 'color']),
        ]);
    }

    public function update(Request $request, Story $story): RedirectResponse
    {
        abort_if($story->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'level'        => 'nullable|in:A1,A2,B1,B2,C1,C2',
            'deck_id'      => 'nullable|exists:decks,id',
            'language_id'  => 'nullable|exists:languages,id',
            'audio'        => 'nullable|file|mimes:mp3,ogg,wav,aac,m4a|max:51200',
            'delete_audio' => 'nullable|boolean',
        ]);

        if (!empty($data['deck_id'])) {
            abort_if(
                Deck::where('id', $data['deck_id'])->where('user_id', auth()->id())->doesntExist(),
                403
            );
        }

        unset($data['audio'], $data['delete_audio']);

        if ($request->hasFile('audio')) {
            if ($story->audio_path) {
                Storage::disk('public')->delete($story->audio_path);
            }
            $ext  = $request->file('audio')->getClientOriginalExtension();
            $path = $request->file('audio')->storeAs("story_audio/{$story->id}", "audio.{$ext}", 'public');
            $data['audio_path'] = $path;
        } elseif ($request->boolean('delete_audio') && $story->audio_path) {
            Storage::disk('public')->delete($story->audio_path);
            $data['audio_path'] = null;
        }

        $story->update($data);

        return redirect()->route('stories.show', $story)->with('success', 'Hekayə yeniləndi!');
    }

    public function destroy(Story $story): RedirectResponse
    {
        abort_if($story->user_id !== auth()->id(), 403);
        $story->delete();

        return redirect()->route('stories.index')->with('success', 'Hekayə silindi!');
    }

    public function lookupWord(Request $request, Story $story): JsonResponse
    {
        abort_if($story->user_id !== auth()->id(), 403);

        $word       = trim($request->validate(['word' => 'required|string|max:200'])['word']);
        $normalized = Term::normalize($word);

        if ($story->deck_id) {
            // 1) Exact normalized match
            // 2) Prefix match: stored term is a prefix of the searched word
            //    e.g. "herausforderung" is a prefix of "herausforderungen" (diff 1-4 chars)
            //    Minimum stored-term length = 5 to avoid false positives on short words
            $term = Term::where('deck_id', $story->deck_id)
                ->where(function ($q) use ($normalized) {
                    $q->where('normalized_term', $normalized)
                      ->orWhereRaw(
                          "LENGTH(normalized_term) >= 5
                           AND ? LIKE CONCAT(normalized_term, '%')
                           AND (LENGTH(?) - LENGTH(normalized_term)) BETWEEN 1 AND 4",
                          [$normalized, $normalized]
                      );
                })
                ->with('examples')
                ->orderByRaw('normalized_term = ? DESC', [$normalized]) // exact match first
                ->first();

            if ($term) {
                return response()->json(['found' => true, 'term' => $term]);
            }
        }

        return response()->json(['found' => false]);
    }

    public function translateWord(Request $request, Story $story): JsonResponse
    {
        abort_if($story->user_id !== auth()->id(), 403);

        $word    = trim($request->validate(['word' => 'required|string|max:200'])['word']);
        $service = app(GeminiEnrichmentService::class);

        $srcLang = $story->language?->code ?? 'de';
        $tgtLang = 'az';

        $result = $service->translate($word, $srcLang, $tgtLang);

        if ($result) {
            return response()->json(['success' => true, 'data' => $result]);
        }

        return response()->json(['success' => false, 'message' => 'Tərcümə edilə bilmədi'], 422);
    }

    public function addWord(Request $request, Story $story): JsonResponse
    {
        abort_if($story->user_id !== auth()->id(), 403);

        if (!$story->deck_id) {
            return response()->json(['error' => 'Bu hekayəyə dəst əlavə edilməyib'], 422);
        }

        $data = $request->validate([
            'term'          => 'required|string|max:500',
            'definition'    => 'required|string|max:500',
            'pronunciation' => 'nullable|string|max:200',
            'part_of_speech'=> 'nullable|in:noun,verb,adjective,adverb,phrase,other',
            'gender'        => 'nullable|in:der,die,das',
        ]);

        $normalized = Term::normalize($data['term']);

        // Block exact match AND prefix match (same logic as lookupWord)
        $alreadyExists = Term::where('deck_id', $story->deck_id)
            ->where(function ($q) use ($normalized) {
                $q->where('normalized_term', $normalized)
                  ->orWhereRaw(
                      "LENGTH(normalized_term) >= 5
                       AND ? LIKE CONCAT(normalized_term, '%')
                       AND (LENGTH(?) - LENGTH(normalized_term)) BETWEEN 1 AND 4",
                      [$normalized, $normalized]
                  )
                  ->orWhereRaw(
                      "LENGTH(?) >= 5
                       AND normalized_term LIKE CONCAT(?, '%')
                       AND (LENGTH(normalized_term) - LENGTH(?)) BETWEEN 1 AND 4",
                      [$normalized, $normalized, $normalized]
                  );
            })
            ->exists();

        if ($alreadyExists) {
            return response()->json(['error' => 'Bu söz (və ya onun forması) artıq dəstdədir'], 409);
        }

        $position = $story->deck->terms()->max('position') + 1;
        $term     = $story->deck->terms()->create(array_merge($data, ['position' => $position]));

        return response()->json(['success' => true, 'term' => $term, 'normalized' => $normalized]);
    }
}
