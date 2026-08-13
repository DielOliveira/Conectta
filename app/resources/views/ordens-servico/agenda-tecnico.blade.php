<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#fff7ed">
    <title>Minha agenda · Conectta</title>
    <style>
        :root{--brand:#d97706;--brand-dark:#92400e;--ink:#172033;--muted:#667085;--line:#e5e7eb;--surface:#fff;--soft:#f8fafc;--danger:#b42318;--success:#067647}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:linear-gradient(180deg,#fff7ed 0,#f5f6f8 280px);color:var(--ink);font:16px/1.5 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.wrap{max-width:760px;margin:auto;padding:18px}.card{background:rgba(255,255,255,.98);border:1px solid rgba(229,231,235,.9);border-radius:20px;padding:20px;margin-bottom:16px;box-shadow:0 10px 30px rgba(15,23,42,.06)}.hero{padding:24px;background:linear-gradient(135deg,#fffbeb,#fff7ed);border-color:#fed7aa}.kicker{display:block;color:var(--brand-dark);font-size:.76rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.hero h1{margin:5px 0 6px;font-size:1.6rem;line-height:1.2}.hero p,.muted{margin:0;color:var(--muted)}h2{font-size:1.15rem;margin:0 0 5px}.hint{margin:0 0 17px;color:var(--muted);font-size:.9rem}.tabs{display:grid;grid-template-columns:1fr 1fr;gap:5px;margin-bottom:16px;padding:5px;border:1px solid var(--line);border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.05);position:sticky;top:8px;z-index:10}.tab{display:flex;align-items:center;justify-content:center;gap:8px;min-height:46px;padding:9px;border-radius:11px;color:var(--muted);font-size:.91rem;font-weight:800;text-decoration:none}.tab.active{background:#fff7ed;color:var(--brand-dark);box-shadow:inset 0 0 0 1px #fed7aa}.tab svg,.week-button svg,.route-link svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.form-grid{display:grid;grid-template-columns:1.35fr 1fr 1fr;gap:12px}label{display:block;font-size:.86rem;font-weight:750}input{width:100%;min-height:46px;margin-top:5px;padding:10px 12px;border:1px solid #d0d5dd;border-radius:11px;background:#fff;color:var(--ink);font:inherit}input:focus{outline:0;border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.16)}button,.button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:46px;border:0;border-radius:12px;padding:11px 16px;background:var(--brand);color:#fff;font:inherit;font-weight:780;cursor:pointer;text-decoration:none}.add{width:100%;margin-top:16px}.day{margin-top:18px}.day:first-child{margin-top:4px}.day-title{display:flex;align-items:center;gap:9px;margin:0 0 9px;font-size:.93rem;text-transform:capitalize}.day-title:after{content:"";height:1px;flex:1;background:var(--line)}.slot{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px;margin-top:8px;border:1px solid var(--line);border-radius:14px;background:var(--soft)}.time{font-size:1.08rem;font-weight:800}.slot-meta{display:block;color:var(--muted);font-size:.82rem}.delete{min-height:38px;padding:8px 11px;background:#fff;color:var(--danger);border:1px solid #fecaca}.locked{display:inline-flex;align-items:center;padding:6px 9px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:.76rem;font-weight:750}.week-nav{display:grid;grid-template-columns:44px 1fr 44px;align-items:center;gap:9px;margin:16px 0}.week-button{display:flex;align-items:center;justify-content:center;height:44px;border:1px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);text-decoration:none}.week-label{text-align:center}.week-label strong{display:block;font-size:.96rem}.week-label span{display:block;color:var(--muted);font-size:.78rem}.today-link{display:block;width:max-content;margin:-5px auto 16px;color:var(--brand-dark);font-size:.82rem;font-weight:800;text-decoration:none}.os-day{margin-top:20px}.os-day:first-of-type{margin-top:4px}.os-card{position:relative;overflow:hidden;margin-top:10px;padding:15px;border:1px solid var(--line);border-radius:16px;background:#fff;box-shadow:0 5px 16px rgba(15,23,42,.045)}.os-card:before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:#f59e0b}.os-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.os-number{display:block;font-size:.75rem;font-weight:850;letter-spacing:.04em;color:var(--muted)}.os-type{display:block;margin-top:1px;font-size:1.03rem;font-weight:850}.status{flex:0 0 auto;display:inline-flex;padding:5px 8px;border-radius:999px;font-size:.7rem;font-weight:850;line-height:1.15;text-align:center}.status-blue{background:#dbeafe;color:#1d4ed8}.status-amber{background:#fef3c7;color:#92400e}.status-green{background:#dcfce7;color:#166534}.status-red{background:#fee2e2;color:#991b1b}.status-gray{background:#f1f5f9;color:#475569}.status-purple{background:#f3e8ff;color:#7e22ce}.os-details{display:grid;gap:7px;margin:13px 0 0;padding-top:12px;border-top:1px solid #eef2f6}.detail{display:grid;grid-template-columns:68px 1fr;gap:8px;font-size:.86rem}.detail span{color:var(--muted)}.detail strong{font-weight:720;overflow-wrap:anywhere}.route-link{display:flex;align-items:center;justify-content:center;gap:7px;width:100%;min-height:42px;margin-top:13px;border:1px solid #fed7aa;border-radius:11px;background:#fff7ed;color:var(--brand-dark);font-size:.86rem;font-weight:820;text-decoration:none}.empty{text-align:center;padding:28px 12px;color:var(--muted)}.empty-icon{display:grid;place-items:center;width:48px;height:48px;margin:0 auto 10px;border-radius:50%;background:#fff7ed;color:var(--brand);font-size:1.4rem}.ok,.error{padding:13px 15px;border-radius:12px;margin-bottom:14px}.ok{background:#dcfce7;color:#166534}.error{background:#fee2e2;color:#991b1b}.footer{text-align:center;color:#98a2b3;font-size:.78rem;padding:4px 0 18px}
        @media(max-width:600px){body{background:linear-gradient(180deg,#fff7ed 0,#f5f6f8 210px)}.wrap{padding:12px;padding-bottom:max(18px,env(safe-area-inset-bottom))}.card{padding:16px;border-radius:17px;margin-bottom:12px}.hero{padding:19px}.hero h1{font-size:1.4rem}.tabs{top:max(8px,env(safe-area-inset-top));margin-bottom:12px}.form-grid{grid-template-columns:1fr 1fr}.form-grid label:first-child{grid-column:1/-1}.slot{padding:13px 12px}.delete{font-size:.85rem}.locked{max-width:140px;text-align:center}.add{position:sticky;bottom:max(10px,env(safe-area-inset-bottom));box-shadow:0 8px 20px rgba(146,64,14,.22)}.os-card{padding:14px 13px 14px 16px}.status{max-width:118px}.detail{grid-template-columns:62px 1fr}}
    </style>
</head>
<body>
@php
    $aba = request('aba') === 'ordens' ? 'ordens' : 'agenda';
    $statusClasses = [
        'aberta' => 'status-gray', 'enviada' => 'status-blue', 'aceita' => 'status-green',
        'em_atendimento' => 'status-amber', 'aguardando_correcao_cadastral' => 'status-red',
        'em_conferencia' => 'status-purple', 'pendente' => 'status-red',
        'finalizada' => 'status-green', 'cancelada' => 'status-gray',
    ];
@endphp
<main class="wrap">
    <section class="card hero">
        <span class="kicker">Conectta Rastreamento</span>
        <h1>Olá, {{ $tecnico->nome }}</h1>
        <p>Organize seus horários e acompanhe suas ordens de serviço.</p>
    </section>

    @if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="error">@foreach($errors->all() as $erro)<div>{{ $erro }}</div>@endforeach</div>@endif

    <nav class="tabs" aria-label="Seções da agenda">
        <a class="tab {{ $aba === 'agenda' ? 'active' : '' }}" href="{{ route('tecnicos.agenda', $token) }}">
            <svg viewBox="0 0 24 24"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z"/></svg>
            Disponibilidade
        </a>
        <a class="tab {{ $aba === 'ordens' ? 'active' : '' }}" href="{{ route('tecnicos.agenda', ['token' => $token, 'aba' => 'ordens', 'semana' => $inicioSemana->format('Y-m-d')]) }}">
            <svg viewBox="0 0 24 24"><path d="M9 5h6M9 9h6M9 13h3M5 3h14v18H5z"/></svg>
            Minhas O.S.
        </a>
    </nav>

    @if($aba === 'agenda')
        <section class="card">
            <h2>Adicionar disponibilidade</h2>
            <p class="hint">Informe um período futuro de pelo menos 1 hora. Os atendimentos são organizados em blocos de 1 hora.</p>
            <form method="post" action="{{ route('tecnicos.agenda.store', $token) }}">@csrf
                <div class="form-grid">
                    <label>Data<input type="date" name="data" min="{{ today()->format('Y-m-d') }}" value="{{ old('data', today()->addDay()->format('Y-m-d')) }}" required></label>
                    <label>Início<input type="time" name="hora_inicio" value="{{ old('hora_inicio', '08:00') }}" required></label>
                    <label>Fim<input type="time" name="hora_fim" value="{{ old('hora_fim', '12:00') }}" required></label>
                </div>
                <button class="add" type="submit">+ Adicionar à agenda</button>
            </form>
        </section>
        <section class="card">
            <h2>Próximos horários</h2>
            <p class="hint">Períodos com uma OS vinculada ficam protegidos e não podem ser removidos.</p>
            @forelse($disponibilidades->groupBy(fn($item) => $item->data->format('Y-m-d')) as $data => $periodos)
                <div class="day">
                    <h3 class="day-title">{{ \Carbon\CarbonImmutable::parse($data)->translatedFormat('l, d \d\e F') }}</h3>
                    @foreach($periodos as $periodo)
                        <div class="slot">
                            <div><span class="time">{{ substr($periodo->hora_inicio,0,5) }} às {{ substr($periodo->hora_fim,0,5) }}</span><span class="slot-meta">{{ \Carbon\CarbonImmutable::parse($periodo->hora_inicio)->diffInMinutes(\Carbon\CarbonImmutable::parse($periodo->hora_fim)) / 60 }} hora(s) disponível(is)</span></div>
                            @if($periodo->ordens_count > 0)
                                <span class="locked">Possui OS cadastrada</span>
                            @else
                                <form method="post" action="{{ route('tecnicos.agenda.destroy', [$token,$periodo]) }}" onsubmit="return confirm('Remover este período da sua agenda?')">@csrf @method('DELETE')<button class="delete" type="submit">Excluir</button></form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="empty">Você ainda não possui horários futuros cadastrados.</div>
            @endforelse
        </section>
    @else
        <section class="card">
            <h2>Ordens atribuídas</h2>
            <p class="hint">Acompanhe todos os atendimentos desta semana, independentemente do status.</p>
            <div class="week-nav">
                <a class="week-button" aria-label="Semana anterior" href="{{ route('tecnicos.agenda', ['token' => $token, 'aba' => 'ordens', 'semana' => $inicioSemana->subWeek()->format('Y-m-d')]) }}"><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg></a>
                <div class="week-label"><strong>{{ $inicioSemana->translatedFormat('d M') }} — {{ $fimSemana->translatedFormat('d M') }}</strong><span>{{ $inicioSemana->year === $fimSemana->year ? $inicioSemana->year : $inicioSemana->year.' / '.$fimSemana->year }}</span></div>
                <a class="week-button" aria-label="Próxima semana" href="{{ route('tecnicos.agenda', ['token' => $token, 'aba' => 'ordens', 'semana' => $inicioSemana->addWeek()->format('Y-m-d')]) }}"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></a>
            </div>
            @unless($semanaAtual)
                <a class="today-link" href="{{ route('tecnicos.agenda', ['token' => $token, 'aba' => 'ordens']) }}">Voltar para esta semana</a>
            @endunless

            @forelse($ordens->groupBy(fn($ordem) => $ordem->agendado_em->format('Y-m-d')) as $data => $ordensDoDia)
                <div class="os-day">
                    <h3 class="day-title">{{ \Carbon\CarbonImmutable::parse($data)->translatedFormat('l, d \d\e F') }}</h3>
                    @foreach($ordensDoDia as $ordem)
                        <article class="os-card">
                            <div class="os-top">
                                <div><span class="os-number">{{ $ordem->numero_formatado }} · {{ $ordem->agendado_em->format('H:i') }}</span><strong class="os-type">{{ $ordem->tipo->label() }}</strong></div>
                                <span class="status {{ $statusClasses[$ordem->status->value] ?? 'status-gray' }}">{{ $ordem->status->label() }}</span>
                            </div>
                            <div class="os-details">
                                <div class="detail"><span>Cliente</span><strong>{{ $ordem->nome_atendimento ?: '—' }}</strong></div>
                                <div class="detail"><span>Veículo</span><strong>{{ $ordem->veiculo?->veiculo ?: '—' }}{{ $ordem->veiculo?->placa ? ' · '.$ordem->veiculo->placa : '' }}</strong></div>
                                <div class="detail"><span>Endereço</span><strong>{{ $ordem->endereco ?: 'Não informado' }}</strong></div>
                            </div>
                            @if(filled($ordem->token_credencial) && filled($ordem->token_hash) && blank($ordem->token_invalidado_em))
                                <a class="route-link" href="{{ route('ordens-servico.tecnico', $ordem->token_credencial) }}"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Abrir atendimento</a>
                            @endif
                        </article>
                    @endforeach
                </div>
            @empty
                <div class="empty"><span class="empty-icon">✓</span><strong>Nenhuma O.S. nesta semana</strong><div>Use as setas para consultar outros períodos.</div></div>
            @endforelse
        </section>
    @endif
    <div class="footer">Seu link é pessoal e identifica seu acesso. Não o compartilhe.</div>
</main>
</body>
</html>
