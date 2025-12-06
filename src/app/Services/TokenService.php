<?php

namespace App\Services;

use App\Models\User;
use Laravel\Passport\Token;
use Laravel\Passport\RefreshToken;
use Illuminate\Support\Facades\DB;
use GraphQL\Error\Error;
use Carbon\Carbon;

class TokenService
{
    /**
     * Calculate token expiration time in seconds based on Passport configuration.
     * Personal access tokens are set to expire in 6 months in AppServiceProvider.
     */
    public function getTokenExpirationSeconds(): int
    {
        return now()->addMonths(6)->diffInSeconds(now());
    }

    /**
     * Calculate refresh token expiration time in seconds.
     * Refresh tokens are set to expire in 30 days in AppServiceProvider.
     */
    public function getRefreshTokenExpirationSeconds(): int
    {
        return now()->addDays(30)->diffInSeconds(now());
    }

    /**
     * Create access token with refresh token for user.
     *
     * @param User $user The user to create tokens for
     * @param string $tokenName The name for the token (defaults to 'Personal Access Token')
     * @return array Contains access_token, token_type, expires_in, and refresh_token
     */
    public function createTokenForUser(User $user, string $tokenName = 'Personal Access Token'): array
    {
        $tokenResult = $user->createToken($tokenName);
        $accessToken = $tokenResult->token;
        $refreshToken = $this->createRefreshTokenForAccessToken($accessToken);

        return [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->getTokenExpirationSeconds(),
            'refresh_token' => $refreshToken->id,
        ];
    }

    /**
     * Create a refresh token for the given access token.
     *
     * Note: Personal Access Tokens don't natively support refresh tokens,
     * so we manually create them in the oauth_refresh_tokens table.
     *
     * @param Token $accessToken The access token to create a refresh token for
     * @return RefreshToken The created refresh token
     */
    protected function createRefreshTokenForAccessToken(Token $accessToken): RefreshToken
    {
        return DB::transaction(function () use ($accessToken) {
            RefreshToken::where('access_token_id', $accessToken->id)->delete();

            $refreshToken = new RefreshToken();
            $refreshToken->id = bin2hex(random_bytes(40));
            $refreshToken->access_token_id = $accessToken->id;
            $refreshToken->revoked = false;
            $refreshToken->expires_at = now()->addDays(30);
            $refreshToken->save();

            return $refreshToken;
        });
    }

    /**
     * Refresh an access token using a refresh token.
     * Implements token rotation for security - old tokens are revoked.
     *
     * @param string $refreshTokenId The refresh token ID
     * @return array Contains new access_token, token_type, expires_in, and refresh_token
     * @throws Error if refresh token is invalid, revoked, or expired
     */
    public function refreshToken(string $refreshTokenId): array
    {
        return DB::transaction(function () use ($refreshTokenId) {
            $refreshToken = RefreshToken::find($refreshTokenId);

            if (!$refreshToken) {
                throw new Error('Invalid refresh token');
            }

            if ($refreshToken->revoked) {
                throw new Error('Refresh token has been revoked');
            }

            if ($refreshToken->expires_at && Carbon::parse($refreshToken->expires_at)->isPast()) {
                throw new Error('Refresh token has expired');
            }

            $oldAccessToken = Token::find($refreshToken->access_token_id);

            if (!$oldAccessToken) {
                throw new Error('Access token not found');
            }

            $user = User::find($oldAccessToken->user_id);

            if (!$user) {
                throw new Error('User not found');
            }

            $oldAccessToken->revoke();
            $refreshToken->revoked = true;
            $refreshToken->save();

            return $this->createTokenForUser($user, 'Refreshed Personal Access Token');
        });
    }

    /**
     * Revoke all tokens for a user (used for logout from all devices).
     *
     * @param User $user The user whose tokens should be revoked
     * @return void
     */
    public function revokeAllTokensForUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->tokens()->update(['revoked' => true]);

            $accessTokenIds = $user->tokens()->pluck('id');
            RefreshToken::whereIn('access_token_id', $accessTokenIds)
                ->update(['revoked' => true]);
        });
    }

    /**
     * Revoke a specific access token and its refresh token.
     *
     * @param Token $token The access token to revoke
     * @return void
     */
    public function revokeToken(Token $token): void
    {
        DB::transaction(function () use ($token) {
            $token->revoke();

            RefreshToken::where('access_token_id', $token->id)
                ->update(['revoked' => true]);
        });
    }
}
