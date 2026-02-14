<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Repository\MemberRepository;
use App\Service\JwtService;
use flight\Engine;

class AuthController extends Controller
{
    private MemberRepository $memberRepository;

    public function __construct(Engine $app)
    {
        parent::__construct($app);
        $this->memberRepository = new MemberRepository($this->getDb());
    }

    public function login(): void
    {
        $username = $this->app->request()->data->username;
        $password = $this->app->request()->data->password;

        if (empty($username)) {
            throw AppException::USERNAME_REQUIRED();
        }

        if (empty($password)) {
            throw AppException::PASSWORD_REQUIRED();
        }

        $member = $this->memberRepository->findByUsername($username);

        if (!$member || !password_verify($password, $member['password'])) {
            throw AppException::INVALID_CREDENTIALS();
        }

        $jwtService = new JwtService($this->app->get('jwt_secret'));

        $this->json([
            'access_token' => $jwtService->generateAccessToken((int) $member['id'], (int) $member['community_id']),
            'refresh_token' => $jwtService->generateRefreshToken((int) $member['id'], (int) $member['community_id']),
        ]);
    }

    public function refresh(): void
    {
        $refreshToken = $this->app->request()->data->refresh_token;

        if (empty($refreshToken)) {
            throw AppException::REFRESH_TOKEN_REQUIRED();
        }

        $jwtService = new JwtService($this->app->get('jwt_secret'));

        try {
            $decoded = $jwtService->decode($refreshToken);

            if (($decoded->type ?? '') !== 'refresh') {
                throw AppException::INVALID_TOKEN_TYPE();
            }

            $this->json([
                'access_token' => $jwtService->generateAccessToken((int) $decoded->sub, (int) $decoded->cid),
                'refresh_token' => $jwtService->generateRefreshToken((int) $decoded->sub, (int) $decoded->cid),
            ]);
        } catch (AppException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw AppException::INVALID_OR_EXPIRED_TOKEN();
        }
    }
}
