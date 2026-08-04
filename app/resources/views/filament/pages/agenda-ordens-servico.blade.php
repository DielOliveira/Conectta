<x-filament-panels::page>
<style>.os-tools{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.os-tools label{font-size:12px;font-weight:700}.os-tools input,.os-tools select{display:block;background:white;border:1px solid #d1d5db;border-radius:7px;padding:9px}.os-tools button{background:#d97706;color:white;border:0;border-radius:7px;padding:10px 14px;font-weight:700}.os-days{display:grid;grid-template-columns:repeat(7,minmax(220px,1fr));gap:12px;overflow:auto}.os-day{min-width:220px}.os-day h3{font-weight:800;margin:15px 0 8px}.os-slot{background:white;border:1px solid #e5e7eb;border-left:4px solid #10b981;border-radius:8px;padding:10px;margin-bottom:7px}.os-slot.busy{border-left-color:#d97706}.os-slot a{color:#92400e;font-weight:800;text-decoration:none}.os-muted{font-size:12px;color:#6b7280}.os-daily-wrap{overflow-x:auto;margin-top:16px}.os-daily{display:grid;min-width:max-content;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#e5e7eb;gap:1px}.os-daily-head,.os-hour,.os-cell{background:white;padding:10px;min-width:190px}.os-daily-head{font-weight:800;text-align:center;background:#f9fafb}.os-hour{min-width:90px;font-weight:800;background:#f9fafb}.os-cell{display:block;min-height:74px;border-left:4px solid #10b981;text-decoration:none;color:#1f2937}.os-cell.busy{border-left-color:#d97706;background:#fffbeb}.os-cell.unavailable{border-left-color:#d1d5db;background:#f9fafb;color:#9ca3af}.os-cell-title{font-weight:800}.os-cell.busy .os-cell-title{color:#92400e}.os-empty{margin-top:16px;background:white;border:1px dashed #d1d5db;border-radius:10px;padding:24px;text-align:center;color:#6b7280}</style>
<div class="os-tools"><button wire:click="anterior">Anterior</button><button wire:click="hoje">Hoje</button><button wire:click="proximo">Próximo</button><label>Data<input type="date" wire:model.live="data"></label><label>Visualização<select wire:model.live="modo"><option value="dia">Dia</option><option value="semana">Semana</option></select></label><label>Técnico<select wire:model.live="tecnicoId"><option value="">Todos</option>@foreach($this->tecnicos() as $tecnico)<option value="{{ $tecnico->id }}">{{ $tecnico->nome }}</option>@endforeach</select></label></div>
@php($itensAgenda=$this->agenda())
@if($this->modo === 'dia')
    @php($tecnicosAgenda=$this->tecnicoId ? $this->tecnicos()->where('id',(int)$this->tecnicoId)->values() : $itensAgenda->pluck('disponibilidade.tecnico')->unique('id')->sortBy('nome')->values())
    @php($horarios=$itensAgenda->pluck('horario')->unique(fn($horario)=>$horario->format('H:i'))->sortBy(fn($horario)=>$horario->format('H:i'))->values())
    @if($tecnicosAgenda->isEmpty() || $horarios->isEmpty())
        <div class="os-empty">Sem disponibilidade cadastrada para esta data.</div>
    @else
        <div class="os-daily-wrap">
            <div class="os-daily" style="grid-template-columns:90px repeat({{ $tecnicosAgenda->count() }},minmax(190px,1fr))">
                <div class="os-daily-head">Horário</div>
                @foreach($tecnicosAgenda as $tecnico)<div class="os-daily-head">{{ $tecnico->nome }}</div>@endforeach
                @foreach($horarios as $horario)
                    <div class="os-hour">{{ $horario->format('H:i') }}<div class="os-muted">até {{ $horario->addHour()->format('H:i') }}</div></div>
                    @foreach($tecnicosAgenda as $tecnico)
                        @php($item=$itensAgenda->first(fn($bloco)=>(int)$bloco['disponibilidade']->tecnico_id===(int)$tecnico->id && $bloco['horario']->format('H:i')===$horario->format('H:i')))
                        @if(!$item)
                            <div class="os-cell unavailable"><div class="os-cell-title">Indisponível</div></div>
                        @elseif($item['ordem'])
                            <a class="os-cell busy" href="{{ $this->urlOrdem($item['ordem']->id) }}"><div class="os-cell-title">{{ $item['ordem']->numero_formatado }}</div><div>{{ $item['ordem']->cliente->nome }}</div><div class="os-muted">{{ $item['ordem']->veiculo->placa }}</div></a>
                        @else
                            <div class="os-cell"><div class="os-cell-title">Livre</div></div>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif
@else
    @php($agenda=$itensAgenda->groupBy(fn($item)=>$item['horario']->toDateString()))
    <div class="os-days">@foreach($this->dias() as $dia)<section class="os-day"><h3>{{ ucfirst($dia->locale('pt_BR')->translatedFormat('D, d/m')) }}</h3>
    @forelse($agenda->get($dia->toDateString(),collect()) as $item)<div class="os-slot {{ $item['ordem']?'busy':'' }}"><div><strong>{{ $item['horario']->format('H:i') }}</strong> · {{ $item['disponibilidade']->tecnico->nome }}</div>@if($item['ordem'])<a href="{{ $this->urlOrdem($item['ordem']->id) }}">{{ $item['ordem']->numero_formatado }}</a><div class="os-muted">{{ $item['ordem']->cliente->nome }} · {{ $item['ordem']->veiculo->placa }}</div>@else<div class="os-muted">Livre</div>@endif</div>@empty<p class="os-muted">Sem disponibilidade cadastrada.</p>@endforelse</section>@endforeach</div>
@endif
</x-filament-panels::page>
