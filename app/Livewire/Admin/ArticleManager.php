<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\Country;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ArticleManager extends Component
{
    use WithPagination;

    public $title, $country_id, $excerpt, $content, $status = 'draft';
    public $articleId = null;
    public $isModalOpen = 0;

    protected $rules = [
        'title' => 'required|string|max:255',
        'country_id' => 'nullable|exists:countries,id',
        'excerpt' => 'nullable|string',
        'content' => 'required|string',
        'status' => 'required|in:published,draft,archived',
    ];

    public function render()
    {
        return view('livewire.admin.article-manager', [
            'articles' => Article::with('user', 'country')->orderBy('id', 'desc')->paginate(15),
            'countries' => Country::all(),
            'totalArticles' => Article::count(),
            'publishedArticles' => Article::where('status', 'published')->count(),
            'draftArticles' => Article::where('status', 'draft')->count(),
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    private function resetInputFields()
    {
        $this->title = '';
        $this->country_id = '';
        $this->excerpt = '';
        $this->content = '';
        $this->status = 'draft';
        $this->articleId = null;
    }

    public function store()
    {
        $this->validate();

        $article = Article::updateOrCreate(['id' => $this->articleId], [
            'user_id' => auth()->id(),
            'country_id' => $this->country_id ?: null,
            'title' => $this->title,
            'slug' => Str::slug($this->title),
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'status' => $this->status,
            'published_at' => $this->status === 'published' ? now() : null,
        ]);

        // Auto-sync published articles to public news website
        if ($article->status === 'published') {
            \App\Models\News::updateOrCreate(
                ['url' => 'article-admin-' . $article->id],
                [
                    'country_id' => $article->country_id,
                    'title' => $article->title,
                    'description' => $article->excerpt ?: Str::limit(strip_tags($article->content), 150),
                    'content' => $article->content,
                    'source' => 'Analisis Admin (' . (auth()->user()?->name ?? 'Admin') . ')',
                    'category' => 'analysis',
                    'sentiment' => 'positive',
                    'positive_score' => 1,
                    'negative_score' => 0,
                    'sentiment_score' => 1.0,
                    'published_at' => $article->published_at ?? now(),
                ]
            );
        } else {
            \App\Models\News::where('url', 'article-admin-' . $article->id)->delete();
        }

        session()->flash('success', $this->articleId ? 'Article updated and synced successfully.' : 'Article created and published successfully.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $article = Article::findOrFail($id);
        $this->articleId = $id;
        $this->title = $article->title;
        $this->country_id = $article->country_id;
        $this->excerpt = $article->excerpt;
        $this->content = $article->content;
        $this->status = $article->status;
    
        $this->openModal();
    }

    public function delete($id)
    {
        \App\Models\News::where('url', 'article-admin-' . $id)->delete();
        Article::find($id)->delete();
        session()->flash('success', 'Article deleted successfully.');
    }
}
