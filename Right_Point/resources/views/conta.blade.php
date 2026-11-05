<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $cadastro ? 'Criar conta' : 'Entrar' }} — RightPoint</title>
    <link rel="stylesheet" href="{{ asset('rightpoint.css') }}?v={{ filemtime(public_path('rightpoint.css')) }}">
</head>
<body>
<main class="page-shell account-shell">
    <a class="brand" href="{{ route('explorar') }}">RightPoint</a>
    <section class="panel account-panel" aria-labelledby="conta-titulo">
        <p class="eyebrow">SEU ESPAÇO DE ANÁLISE</p>
        <h1 id="conta-titulo">{{ $cadastro ? 'Crie sua conta.' : 'Bem-vindo de volta.' }}</h1>
        <p>Guarde perfis e diagnósticos privados no banco deste servidor. A exploração continua disponível sem conta.</p>
        @if ($errors->any())
            <div class="field-error" role="alert"><ul>@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>
        @endif
        <form method="post" action="{{ $cadastro ? route('register') : route('login') }}">
            @csrf
            @if ($cadastro)
                <div class="field"><label for="name">Nome</label><input id="name" name="name" autocomplete="name" value="{{ old('name') }}" maxlength="80" required></div>
            @endif
            <div class="field"><label for="email">E-mail</label><input id="email" name="email" type="email" autocomplete="email" spellcheck="false" value="{{ old('email') }}" maxlength="254" required></div>
            <div class="field"><label for="password">Senha</label><input id="password" name="password" type="password" autocomplete="{{ $cadastro ? 'new-password' : 'current-password' }}" maxlength="72" required>
                @if ($cadastro)<small>Use pelo menos 10 caracteres, com letras e números; máximo de 72 bytes.</small>@endif
            </div>
            @if ($cadastro)<div class="field"><label for="password_confirmation">Confirme a senha</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" maxlength="72" required></div>@endif
            <button class="button button--primary" type="submit">{{ $cadastro ? 'Criar minha conta' : 'Entrar na conta' }}</button>
        </form>
        <p class="account-note"><a href="{{ $cadastro ? route('login') : route('register') }}">{{ $cadastro ? 'Já tenho conta' : 'Quero criar uma conta' }}</a> · <a href="{{ route('explorar') }}">Voltar à exploração</a></p>
        <p class="account-note">Nesta versão acadêmica, recuperação de senha e confirmação de e-mail ainda não estão disponíveis. Contas de outro servidor não são sincronizadas automaticamente.</p>
    </section>
</main>
</body>
</html>
