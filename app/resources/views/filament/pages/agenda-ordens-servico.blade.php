<x-filament-panels::page>
<style>.os-tools{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.os-tools label{font-size:12px;font-weight:700}.os-tools input,.os-tools select{display:block;background:white;border:1px solid #d1d5db;border-radius:7px;padding:9px}.os-tools button{background:#d97706;color:white;border:0;border-radius:7px;padding:10px 14px;font-weight:700}.os-days{display:grid;grid-template-columns:repeat(7,minmax(220px,1fr));gap:12px;overflow:auto}.os-day{min-width:220px}.os-day h3{font-weight:800;margin:15px 0 8px}.os-slot{background:white;border:1px solid #e5e7eb;border-left:4px solid #10b981;border-radius:8px;padding:10px;margin-bottom:7px}.os-slot.busy{border-left-color:#d97706}.os-slot.attended{border-color:#bfdbfe;border-left-color:#2563eb;background:#eff6ff}.os-slot.blocked{border-left-color:#dc2626;background:#fef2f2;color:#991b1b}.os-week-appointment{border:0;background:transparent;color:#92400e;font-weight:800;padding:0;cursor:pointer}.os-slot.attended .os-week-appointment{color:#1d4ed8}.os-muted{font-size:12px;color:#6b7280}.os-daily{margin-top:16px;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#e5e7eb}.os-hour-row{display:grid;grid-template-columns:92px minmax(0,1fr);gap:1px;margin-bottom:1px}.os-hour-row:last-child{margin-bottom:0}.os-hour{background:#f9fafb;padding:12px;font-weight:800}.os-hour-content{background:white;padding:8px;display:flex;align-items:stretch;gap:8px;flex-wrap:wrap;min-height:76px}.os-appointment{display:block;width:190px;border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:8px;background:#fef2f2;padding:9px;color:#7f1d1d;text-decoration:none;text-align:left;cursor:pointer}.os-appointment:hover{background:#fee2e2}.os-appointment.attended{border-color:#bfdbfe;border-left-color:#2563eb;background:#eff6ff;color:#1e40af}.os-appointment.attended:hover{background:#dbeafe}.os-attended-label{font-size:11px;font-weight:800;text-transform:uppercase;color:#2563eb}.os-appointment-title{font-weight:800}.os-free,.os-open{min-width:180px;max-width:320px;border-radius:8px;padding:9px;text-align:left;cursor:pointer}.os-free{border:1px solid #bbf7d0;border-left:4px solid #16a34a;background:#f0fdf4;color:#166534}.os-free:hover{background:#dcfce7}.os-open{border:1px solid #bfdbfe;border-left:4px solid #2563eb;background:#eff6ff;color:#1e40af}.os-open:hover{background:#dbeafe}.os-free-title{font-size:12px;font-weight:800;text-transform:uppercase}.os-free-names{font-size:12px;line-height:1.35;margin-top:3px}.os-blocked{min-width:180px;border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:8px;background:#fef2f2;padding:9px;color:#991b1b}.os-blocked strong{display:block;font-size:12px;text-transform:uppercase}.os-unavailable{align-self:center;color:#9ca3af;font-size:12px}.os-empty{margin-top:16px;background:white;border:1px dashed #d1d5db;border-radius:10px;padding:24px;text-align:center;color:#6b7280}@media(max-width:640px){.os-hour-row{grid-template-columns:70px minmax(0,1fr)}.os-hour{padding:10px 7px}.os-appointment,.os-free,.os-open,.os-blocked{width:100%;max-width:none}}</style>
<style>
.os-status-label{font-size:11px;font-weight:800;text-transform:uppercase}
.os-appointment.status-aberta,.os-slot.status-aberta{border-color:#e5e7eb;border-left-color:#6b7280;background:#f9fafb;color:#374151}
.os-appointment.status-enviada,.os-slot.status-enviada{border-color:#fde68a;border-left-color:#d97706;background:#fffbeb;color:#92400e}
.os-appointment.status-aceita,.os-slot.status-aceita{border-color:#a5f3fc;border-left-color:#0891b2;background:#ecfeff;color:#155e75}
.os-appointment.status-em-atendimento,.os-slot.status-em-atendimento{border-color:#ddd6fe;border-left-color:#7c3aed;background:#f5f3ff;color:#5b21b6}
.os-appointment.status-correcao-cadastral,.os-slot.status-correcao-cadastral{border-color:#fed7aa;border-left-color:#ea580c;background:#fff7ed;color:#9a3412}
.os-appointment.status-em-conferencia,.os-slot.status-em-conferencia{border-color:#c7d2fe;border-left-color:#4f46e5;background:#eef2ff;color:#3730a3}
.os-appointment.status-pendente,.os-slot.status-pendente{border-color:#fecaca;border-left-color:#dc2626;background:#fef2f2;color:#991b1b}
.os-appointment.status-finalizada,.os-slot.status-finalizada{border-color:#bbf7d0;border-left-color:#16a34a;background:#f0fdf4;color:#166534}
.os-appointment.status-cancelada,.os-slot.status-cancelada{border-color:#d1d5db;border-left-color:#4b5563;background:#f3f4f6;color:#4b5563}
.os-slot.busy .os-week-appointment{color:inherit}
</style>
<div class="os-tools"><button wire:click="anterior">Anterior</button><button wire:click="hoje">Hoje</button><button wire:click="proximo">Próximo</button><label>Data<input type="date" wire:model.live="data"></label><label>Visualização<select wire:model.live="modo"><option value="dia">Dia</option><option value="semana">Semana</option></select></label><label>Técnico<select wire:model.live="tecnicoId"><option value="">Todos</option>@foreach($this->tecnicos() as $tecnico)<option value="{{ $tecnico->id }}">{{ $tecnico->nome }}</option>@endforeach</select></label></div>
@php($itensAgenda=$this->agenda())
@if($this->modo === 'dia')
    @php($horarios=$this->horariosDia($itensAgenda))
    @if($horarios->isEmpty())
        <div class="os-empty">Sem disponibilidade cadastrada para esta data.</div>
    @else
        <div class="os-daily">
            @foreach($horarios as $horario)
                @php($itensHorario=$itensAgenda->filter(fn($item)=>$item['horario']->format('H:i')===$horario->format('H:i')))
                @php($ocupados=$itensHorario->filter(fn($item)=>$item['ordem']))
                @php($bloqueios=$itensHorario->filter(fn($item)=>$item['bloqueio']))
                @php($tecnicosLivres=$itensHorario->reject(fn($item)=>$item['ordem'] || $item['bloqueio'])->filter(fn($item)=>$this->horarioPodeReceberOs($horario) && $this->tecnicoPodeReceberOs($item['disponibilidade']->tecnico))->pluck('disponibilidade.tecnico.nome')->unique()->sort()->values())
                <div class="os-hour-row">
                    <div class="os-hour">{{ $horario->format('H:i') }}<div class="os-muted">{{ $horario->addHour()->format('H:i') }}</div></div>
                    <div class="os-hour-content">
                        @foreach($ocupados as $item)
                            <button type="button" class="os-appointment {{ $item['status_classe'] }}" wire:click="mountAction('agendamento', { ordem: {{ $item['ordem']->id }} })"><div class="os-status-label">{{ $item['status_label'] }}</div><div class="os-appointment-title">{{ $item['ordem']->numero_formatado }}</div><div>{{ $item['disponibilidade']->tecnico->nome }}</div><div class="os-muted">{{ $item['ordem']->nome_atendimento }} · {{ $item['ordem']->veiculo->placa }}</div></button>
                        @endforeach
                        @foreach($bloqueios as $item)<div class="os-blocked"><strong>Bloqueado</strong><div>{{ $item['disponibilidade']->tecnico->nome }}</div></div>@endforeach
                        @if($tecnicosLivres->isNotEmpty())
                            @if($this->podeAtribuir())<button type="button" class="os-free" wire:click="mountAction('atribuir', { horario: '{{ $horario->format('Y-m-d H:i:s') }}' })"><div class="os-free-title">Livre</div><div class="os-free-names">{{ $tecnicosLivres->implode(', ') }}</div></button>@else<div class="os-free"><div class="os-free-title">Livre</div><div class="os-free-names">{{ $tecnicosLivres->implode(', ') }}</div></div>@endif
                        @elseif($ocupados->isEmpty() && ! $this->horarioPodeReceberOs($horario))
                            <div class="os-unavailable">Horário encerrado.</div>
                        @endif
                        @if($this->podeAtribuir() && $this->horarioPodeReceberOs($horario))<button type="button" class="os-open" wire:click="mountAction('atribuir', { horario: '{{ $horario->format('Y-m-d H:i:s') }}', abrir_horario: true })"><div class="os-free-title">Abrir horário</div><div class="os-free-names">Escolher outro técnico</div></button>@endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@else
    @php($agenda=$itensAgenda->groupBy(fn($item)=>$item['horario']->toDateString()))
    <div class="os-days">@foreach($this->dias() as $dia)<section class="os-day"><h3>{{ ucfirst($dia->locale('pt_BR')->translatedFormat('D, d/m')) }}</h3>
    @forelse($agenda->get($dia->toDateString(),collect()) as $item)<div class="os-slot {{ $item['ordem']?'busy '.$item['status_classe']:'' }} {{ $item['bloqueio']?'blocked':'' }}"><div><strong>{{ $item['horario']->format('H:i') }}</strong> · {{ $item['disponibilidade']->tecnico->nome }}</div>@if($item['ordem'])<div class="os-status-label">{{ $item['status_label'] }}</div><button type="button" class="os-week-appointment" wire:click="mountAction('agendamento', { ordem: {{ $item['ordem']->id }} })">{{ $item['ordem']->numero_formatado }}</button><div class="os-muted">{{ $item['ordem']->nome_atendimento }} · {{ $item['ordem']->veiculo->placa }}</div>@elseif($item['bloqueio'])<div class="os-muted">Bloqueado</div>@else<div class="os-muted">Livre</div>@endif</div>@empty<p class="os-muted">Sem disponibilidade cadastrada.</p>@endforelse</section>@endforeach</div>
@endif
</x-filament-panels::page>
