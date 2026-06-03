<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Core\ServiceContainer;
use InvalidArgumentException;
use RuntimeException;

final class UserController extends BaseController
{
    public function __construct()
    {
    }

    /**
     * PATCH /users/me
     */
    public function updateUserProfile(): void
    {
        Auth::requireLogin();

        try {
            $data   = $this->parseJsonBody();
            $result = ServiceContainer::getUserService()->updateProfile($data);

            Response::json(['success' => true, 'data' => $result]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
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
            ServiceContainer::getUserService()->updatePassword($data);

            Response::json(['success' => true, 'message' => 'Password updated successfully']);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }
}