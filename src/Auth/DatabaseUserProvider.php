<?php

namespace Darkauth\Auth;

use Darkauth\Core\UserProviderInterface;
use Darkauth\Core\UserInterface;
use Darkauth\Models\GenericUser;
use Darkauth\Support\Hash;

/**
 * Class DatabaseUserProvider
 * 
 * A generic database user provider.
 */
class DatabaseUserProvider implements UserProviderInterface
{
    /**
     * @var callable
     */
    protected $queryCallback;

    /**
     * @var string
     */
    protected $table;

    /**
     * DatabaseUserProvider constructor.
     *
     * @param callable $queryCallback A callback that returns user data from DB
     * @param string $table
     */
    public function __construct(callable $queryCallback, string $table = 'users')
    {
        $this->queryCallback = $queryCallback;
        $this->table = $table;
    }

    /**
     * @inheritDoc
     */
    public function retrieveById($identifier): ?UserInterface
    {
        $data = call_user_func($this->queryCallback, $this->table, ['id' => $identifier]);
        return $data ? new GenericUser((array) $data) : null;
    }

    /**
     * @inheritDoc
     */
    public function retrieveByCredentials(array $credentials): ?UserInterface
    {
        if (empty($credentials)) {
            return null;
        }

        // We filter out password from the search criteria
        $criteria = array_filter($credentials, function($key) {
            return $key !== 'password';
        }, ARRAY_FILTER_USE_KEY);

        $data = call_user_func($this->queryCallback, $this->table, $criteria);
        
        return $data ? new GenericUser((array) $data) : null;
    }

    /**
     * @inheritDoc
     */
    public function validateCredentials(UserInterface $user, array $credentials): bool
    {
        $plain = $credentials['password'] ?? '';
        return Hash::check($plain, $user->getAuthPassword());
    }
}
