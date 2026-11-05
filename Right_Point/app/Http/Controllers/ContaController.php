<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ContaController extends Controller
{
    public function formulario(Request $request)
    {
        return view('conta', ['cadastro' => $request->routeIs('register')]);
    }

    public function entrar(Request $request)
    {
        $this->normalizarEmail($request);
        $dados = $request->validate(['email' => ['required', 'email', 'max:254'], 'password' => $this->regrasSenha()]);
        if (! Auth::attempt($dados)) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('explorar'));
    }

    public function cadastrar(Request $request)
    {
        $this->normalizarEmail($request);
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'password' => [...$this->regrasSenha(), 'confirmed', Password::min(10)->letters()->numbers()],
        ]);
        $usuario = User::create($dados);
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('explorar');
    }

    private function normalizarEmail(Request $request): void
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
    }

    private function regrasSenha(): array
    {
        // Bcrypt considera no máximo 72 bytes, não 72 caracteres Unicode.
        return ['required', 'string', 'max:72', function ($attribute, $value, $fail) {
            if (is_string($value) && strlen($value) > 72) {
                $fail('A senha deve ter no máximo 72 bytes; use uma combinação menor.');
            }
        }];
    }

    public function sair(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('explorar');
    }
}
