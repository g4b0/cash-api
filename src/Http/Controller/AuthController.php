<?php

namespace App\Http\Controller;

use App\Exception\AppException;
use App\Http\Response\TokenPairResponse;
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

        $accessToken = $jwtService->generateAccessToken((int) $member['id'], (int) $member['communityId']);
        $refreshToken = $jwtService->generateRefreshToken((int) $member['id'], (int) $member['communityId']);

        $this->json(new TokenPairResponse($accessToken, $refreshToken));
    }

    public function refresh(): void
    {
        $refreshToken = $this->app->request()->data->refreshToken;

        if (empty($refreshToken)) {
            throw AppException::REFRESH_TOKEN_REQUIRED();
        }

        $jwtService = new JwtService($this->app->get('jwt_secret'));

        try {
            $decoded = $jwtService->decode($refreshToken);

            if (($decoded->type ?? '') !== 'refresh') {
                throw AppException::INVALID_TOKEN_TYPE();
            }

            $accessToken = $jwtService->generateAccessToken((int) $decoded->sub, (int) $decoded->cid);
            $refreshToken = $jwtService->generateRefreshToken((int) $decoded->sub, (int) $decoded->cid);

            $this->json(new TokenPairResponse($accessToken, $refreshToken));
        } catch (AppException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw AppException::INVALID_OR_EXPIRED_TOKEN();
        }
    }
}
