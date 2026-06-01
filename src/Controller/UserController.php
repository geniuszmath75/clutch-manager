<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use InvalidArgumentException;
use RuntimeException;
use Src\Repository\TeamRoleRepository;
use Src\Repository\UserRepository;
use Src\Service\UserService;

final class UserController extends BaseController
{
    private UserService $userService;
    public function __construct()
    {
        $userRepository = new UserRepository();
        $teamRoleRepository = new TeamRoleRepository();
        $this->userService = new UserService($userRepository, $teamRoleRepository);
    }

    /**
     * PATCH /users/me
     */
    public function updateUserProfile(): void
    {
        Auth::requireLogin();

        try {
            $data   = $this->parseJsonBody();
            $result = $this->userService->updateProfile($data);

            Response::json(['success' => true, 'data' => $result]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * PATCH /users/me/password
     */
    public function updateUserPassword(): void
    {
        Auth::requireLogin();

        try {
            $data = $this->parseJsonBody();
            $this->userService->updatePassword($data);

            Response::json(['success' => true, 'message' => 'Password updated successfully']);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }
}