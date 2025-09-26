<?php

namespace App\Filament\Auth;

use App\Models\Employee;
use Filament\Auth\Pages\Login;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomLogin extends Login
{
    // Estado del formulario
//    public array $data = [];
    public ?array $data = null;

    /**
     * Define el formulario de login
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('login')
                    ->label(__('Usuario / Correo'))
                    ->required()
                    ->autofocus()
                    ->extraAttributes(['tabindex' => 1]),

                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable() // ✅ botón mostrar/ocultar automático
                    ->autocomplete('current-password')
                    ->required()
                    ->extraAttributes(['tabindex' => 2]),

                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    /**
     * Convierte los datos del formulario en credenciales
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $loginType = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        return [
            $loginType => $data['login'],
            'password' => $data['password'],
        ];
    }

    /**
     * Lanza excepción si falla el login
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    /**
     * Autentica al usuario
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        // ✅ Estado del formulario en Filament 4
//        $data = $this->data;
        $data = $this->form->getState();

        if (!Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (($user instanceof FilamentUser) && (!$user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        $empleado = Auth::user()->employee->id;
        $empleado = Employee::with('wherehouse')->find($empleado);
        $sucursal_actual = $empleado->wherehouse->name;
        session()->put('sucursal_id', $empleado->wherehouse->id);
        session()->put('sucursal_actual', $sucursal_actual);
        session()->put('empleado', $empleado->name);

        return app(LoginResponse::class);
    }
}
