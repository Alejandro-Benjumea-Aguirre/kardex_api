<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\{AuthResultData, LoginData};
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\TokenService;
use App\Exceptions\Auth\{
    InvalidCredentialsException,
    AccountInactiveException,
    EmailNotVerifiedException,
    TooManyAttemptsException
};

class LoginAction
{
    // Máximo de intentos fallidos antes de bloquear
    private const MAX_FAILED_ATTEMPTS = 5;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenService $tokenService,
    ) {}

    /**
     * @throws InvalidCredentialsException
     * @throws AccountInactiveException
     * @throws EmailNotVerifiedException
     * @throws TooManyAttemptsException
     */
    public function __invoke(LoginData $data): AuthResultData
    {
        $branchId = $data->branch_id ?? '';

        $user = $this->userRepository->findByEmail($data->email);

        if (! $user) {
            throw new InvalidCredentialsException();
        }

        if (! $user->isActive()) {
            throw new AccountInactiveException();
        }

        $failedAttempts = $this->userRepository->getFailedLoginCount($user);

        if ($failedAttempts >= self::MAX_FAILED_ATTEMPTS) {
            throw new TooManyAttemptsException(
                'Demasiados intentos fallidos. Intentá en 15 minutos.'
            );
        }

        if (! \Hash::check($data->password, $user->password)) {
            $this->userRepository->incrementFailedLogins($user);
            throw new InvalidCredentialsException();
        }

        $this->userRepository->resetFailedLogins($user);
        $this->userRepository->updateLastLogin($user);
    

        if (empty($branchId)) {
            $branchId = $user->company_id;
        }

        $permissions = $user->loadAndCachePermissions($branchId);

        $accessToken  = $this->tokenService->generateAccessToken($user, $branchId);
        $refreshToken = $this->tokenService->generateRefreshToken($user, $branchId);

        return new AuthResultData(
            user:          $user->load(['company', 'roles']),
            access_token:  $accessToken,
            refresh_token: $refreshToken,
            token_type:    'Bearer',
            expires_in:    15 * 60,
        );
    }
}
