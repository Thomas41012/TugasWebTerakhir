<?php

namespace App\Livewire\Admin;

use App\Models\Port;
use App\Models\Country;
use Livewire\Component;
use Livewire\WithPagination;

class PortManager extends Component
{
    use WithPagination;

    public $name, $country_id, $latitude, $longitude, $status = 'active', $type = 'seaport', $congestion_level = 0, $risk_score = 0;
    public $portId = null;
    public $isModalOpen = 0;

    protected $rules = [
        'name' => 'required|string|max:255',
        'country_id' => 'required|exists:countries,id',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'status' => 'required|in:active,inactive,maintenance',
        'type' => 'required|string',
        'congestion_level' => 'required|numeric|min:0|max:100',
        'risk_score' => 'required|numeric|min:0|max:100',
    ];

    public function render()
    {
        return view('livewire.admin.port-manager', [
            'ports' => Port::with('country')->orderBy('id', 'desc')->paginate(20),
            'countries' => Country::all(),
            'activePorts' => Port::where('status', 'active')->count(),
            'highCongestion' => Port::where('congestion_level', '>=', 70)->count(),
            'highRisk' => Port::where('risk_score', '>=', 70)->count(),
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
        $this->name = '';
        $this->country_id = '';
        $this->latitude = '';
        $this->longitude = '';
        $this->status = 'active';
        $this->type = 'seaport';
        $this->congestion_level = 0;
        $this->risk_score = 0;
        $this->portId = null;
    }

    public function store()
    {
        $this->validate();

        Port::updateOrCreate(['id' => $this->portId], [
            'name' => $this->name,
            'country_id' => $this->country_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'type' => $this->type,
            'congestion_level' => $this->congestion_level,
            'risk_score' => $this->risk_score,
        ]);

        session()->flash('success', $this->portId ? 'Port updated successfully.' : 'Port created successfully.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $port = Port::findOrFail($id);
        $this->portId = $id;
        $this->name = $port->name;
        $this->country_id = $port->country_id;
        $this->latitude = $port->latitude;
        $this->longitude = $port->longitude;
        $this->status = $port->status;
        $this->type = $port->type;
        $this->congestion_level = $port->congestion_level;
        $this->risk_score = $port->risk_score;
    
        $this->openModal();
    }

    public function delete($id)
    {
        Port::find($id)->delete();
        session()->flash('success', 'Port deleted successfully.');
    }
}
