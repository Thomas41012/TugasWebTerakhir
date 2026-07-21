<?php

namespace App\Livewire\Admin;

use App\Models\PositiveWord;
use App\Models\NegativeWord;
use Livewire\Component;
use Livewire\WithPagination;

class SentimentManager extends Component
{
    use WithPagination;

    public $activeTab = 'positive'; // positive or negative
    public $word, $weight = 1.0;
    public $wordId = null;
    public $isModalOpen = false;

    protected $rules = [
        'word' => 'required|string|max:100',
        'weight' => 'required|numeric|min:0.1|max:10',
    ];

    public function render()
    {
        $words = $this->activeTab === 'positive' 
            ? PositiveWord::orderBy('id', 'desc')->paginate(20)
            : NegativeWord::orderBy('id', 'desc')->paginate(20);

        return view('livewire.admin.sentiment-manager', [
            'words' => $words,
            'totalPositive' => PositiveWord::count(),
            'totalNegative' => NegativeWord::count(),
        ]);
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
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
        $this->word = '';
        $this->weight = 1.0;
        $this->wordId = null;
    }

    public function store()
    {
        $this->validate();

        if ($this->activeTab === 'positive') {
            PositiveWord::updateOrCreate(['id' => $this->wordId], [
                'word' => strtolower($this->word),
                'weight' => $this->weight,
            ]);
        } else {
            NegativeWord::updateOrCreate(['id' => $this->wordId], [
                'word' => strtolower($this->word),
                'weight' => $this->weight,
            ]);
        }

        session()->flash('success', 'Word successfully ' . ($this->wordId ? 'updated.' : 'added.'));
        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $model = $this->activeTab === 'positive' ? PositiveWord::findOrFail($id) : NegativeWord::findOrFail($id);
        $this->wordId = $id;
        $this->word = $model->word;
        $this->weight = $model->weight;
        $this->openModal();
    }

    public function delete($id)
    {
        if ($this->activeTab === 'positive') {
            PositiveWord::find($id)->delete();
        } else {
            NegativeWord::find($id)->delete();
        }
        
        session()->flash('success', 'Word deleted successfully.');
    }
}
