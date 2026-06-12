<?php
namespace Modules\Deck\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Deck\Models\Deck;
use Modules\Language\Models\Language;

class DeckController extends Controller
{
    public function index(): Response
    {
        $decks = auth()->user()->decks()
            ->with(['sourceLanguage', 'targetLanguage'])
            ->withCount('terms')
            ->latest()
            ->get();

        return Inertia::render('Deck::Decks/Index', ['decks' => $decks]);
    }

    public function explore(Request $request): Response
    {
        $query = Deck::where('is_public', true)
            ->where('user_id', '!=', auth()->id())
            ->with(['sourceLanguage', 'targetLanguage', 'user'])
            ->withCount('terms');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('lang')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('sourceLanguage', fn ($s) => $s->where('code', $request->lang))
                  ->orWhereHas('targetLanguage', fn ($t) => $t->where('code', $request->lang));
            });
        }

        $decks = $query->latest()->paginate(12)->withQueryString();

        return Inertia::render('Deck::Decks/Explore', [
            'decks'     => $decks,
            'languages' => \Modules\Language\Models\Language::where('is_active', true)->get(),
            'filters'   => $request->only('search', 'lang'),
        ]);
    }

    public function clone(Deck $deck): RedirectResponse
    {
        $this->authorize('view', $deck);

        $newDeck = auth()->user()->decks()->create([
            'title'              => $deck->title . ' (kopya)',
            'description'        => $deck->description,
            'source_language_id' => $deck->source_language_id,
            'target_language_id' => $deck->target_language_id,
            'color'              => $deck->color,
            'is_public'          => false,
        ]);

        foreach ($deck->terms()->with('examples')->get() as $i => $term) {
            $newTerm = $newDeck->terms()->create([
                'term'          => $term->term,
                'definition'    => $term->definition,
                'pronunciation' => $term->pronunciation,
                'part_of_speech'=> $term->part_of_speech,
                'gender'        => $term->gender,
                'plural_form'   => $term->plural_form,
                'level'         => $term->level,
                'notes'         => $term->notes,
                'position'      => $i,
            ]);
            foreach ($term->examples as $ex) {
                $newTerm->examples()->create([
                    'sentence'    => $ex->sentence,
                    'translation' => $ex->translation,
                ]);
            }
        }

        return redirect()->route('decks.show', $newDeck)
            ->with('success', "«{$deck->title}» dəsti kopyalandı!");
    }

    public function create(): Response
    {
        return Inertia::render('Deck::Decks/Create', [
            'languages' => Language::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'source_language_id' => 'nullable|exists:languages,id',
            'target_language_id' => 'nullable|exists:languages,id',
            'color'              => 'nullable|string|max:7',
            'is_public'          => 'boolean',
        ]);

        $deck = auth()->user()->decks()->create($data);

        return redirect()->route('decks.show', $deck)->with('success', 'Dəst yaradıldı!');
    }

    public function show(Deck $deck): Response
    {
        $this->authorize('view', $deck);

        $deck->load(['sourceLanguage', 'targetLanguage']);
        $deck->loadCount('terms');

        return Inertia::render('Deck::Decks/Show', ['deck' => $deck]);
    }

    public function terms(Request $request, Deck $deck): JsonResponse
    {
        $this->authorize('view', $deck);

        $page    = max(1, (int) $request->get('page', 1));
        $search  = trim((string) $request->get('search', ''));
        $perPage = 10;

        $query = $deck->terms()->with(['primaryImage', 'examples']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('term',        'like', "%{$search}%")
                  ->orWhere('definition', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $items = $query->forPage($page, $perPage)->get();

        return response()->json([
            'terms'    => $items,
            'total'    => $total,
            'page'     => $page,
            'has_more' => ($page * $perPage) < $total,
        ]);
    }

    public function edit(Deck $deck): Response
    {
        $this->authorize('update', $deck);

        return Inertia::render('Deck::Decks/Edit', [
            'deck'      => $deck,
            'languages' => Language::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, Deck $deck): RedirectResponse
    {
        $this->authorize('update', $deck);

        $data = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'source_language_id' => 'nullable|exists:languages,id',
            'target_language_id' => 'nullable|exists:languages,id',
            'color'              => 'nullable|string|max:7',
            'is_public'          => 'boolean',
        ]);

        $deck->update($data);

        return back()->with('success', 'Dəst yeniləndi!');
    }

    public function destroy(Deck $deck): RedirectResponse
    {
        $this->authorize('delete', $deck);
        $deck->delete();

        return redirect()->route('decks.index')->with('success', 'Dəst silindi!');
    }
}
