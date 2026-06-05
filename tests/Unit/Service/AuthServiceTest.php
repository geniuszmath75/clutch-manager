<?php

declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Src\Model\User;
use Src\Repository\SystemRoleRepository;
use Src\Repository\TeamRoleRepository;
use Src\Repository\UserRepository;
use Src\Service\AuthService;

/**
 * Unit tests for AuthService.
 *
 * All repository dependencies are mocked — no database access.
 */
final class AuthServiceTest extends TestCase
{
    private AuthService $authService;

    private UserRepository $userRepository;
    private SystemRoleRepository $systemRoleRepository;
    private TeamRoleRepository $teamRoleRepository;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->systemRoleRepository = $this->createMock(SystemRoleRepository::class);
        $this->teamRoleRepository = $this->createMock(TeamRoleRepository::class);

        $this->authService = new AuthService(
            $this->userRepository,
            $this->systemRoleRepository,
            $this->teamRoleRepository
        );
    }

    // -------------------------------------------------------------------------
    // login()
    // -------------------------------------------------------------------------

    /**
     * Test 1: login() throws RuntimeException (401) when password does not match.
     *
     * Covers the core authentication check. A known user is returned by the repo
     * but password_verify() will fail because the hash does not match "wrong".
     */
    public function testLoginThrowsOnInvalidPassword(): void
    {
        // Prepare a fake User object whose passwordHash does NOT match "wrong"
        $fakeUser = $this->createMock(User::class);
        $fakeUser->password = password_hash('correct_password_123', PASSWORD_BCRYPT);
        $fakeUser->isActive = true;

        $this->userRepository->method('findByEmail')->willReturn($fakeUser);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(401);

        $this->authService->login('player@example.com', 'wrong');
    }

    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    /**
     * Test 2: register() throws InvalidArgumentException (409) when email is already taken.
     *
     * Covers the duplicate-email guard. Repo reports the email exists, so the
     * service must reject before attempting to hash the password or insert a row.
     */
    public function testRegisterThrowsOnDuplicateEmail(): void
    {
        $this->userRepository->method('emailExists')->willReturn(true);

        // nicknameExists must NOT be called — short-circuit after email check
        $this->userRepository->expects($this->never())->method('nicknameExists');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(409);

        $this->authService->register(
            nickname:        'ProPlayer',
            email:           'taken@example.com',
            password:        'securepassword1',
            systemRoleIdent: 'PLAYER',
            teamRoleIdent:   'IGL',
        );
    }

    /**
     * Test 3: register() throws InvalidArgumentException (400) when a PLAYER
     * registers without providing a teamRoleIdent.
     *
     * Covers the business rule: PLAYER must declare a team role.
     * This is caught during input validation — before any repo call.
     */
    public function testRegisterThrowsWhenPlayerHasNoTeamRole(): void
    {
        // Repositories must not be touched — validation fails before any I/O
        $this->userRepository->expects($this->never())->method('emailExists');
        $this->userRepository->expects($this->never())->method('nicknameExists');
        $this->userRepository->expects($this->never())->method('create');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(400);

        $this->authService->register(
            nickname:        'ProPlayer',
            email:           'player@example.com',
            password:        'securepassword1',
            systemRoleIdent: 'PLAYER',
            teamRoleIdent:   null,       // ← missing — should fail validation
        );
    }
}
