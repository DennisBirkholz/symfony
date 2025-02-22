<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Tests\Functional\Bundle\SecuredPageBundle\Security\Core\User;

use Symfony\Bundle\SecurityBundle\Tests\Functional\UserWithoutEquatable;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ArrayUserProvider implements UserProviderInterface
{
    /** @var UserInterface[] */
    private $users = [];

    public function addUser(UserInterface $user)
    {
        $this->users[$user->getUsername()] = $user;
    }

    public function setUser($username, UserInterface $user)
    {
        $this->users[$username] = $user;
    }

    public function getUser($username)
    {
        return $this->users[$username];
    }

    public function loadUserByUsername($username)
    {
        $user = $this->getUser($username);

        if (null === $user) {
            $e = new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
            $e->setUsername($username);

            throw $e;
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof UserInterface) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $storedUser = $this->getUser($user->getUsername());
        $class = \get_class($storedUser);

        return new $class($storedUser->getUsername(), $storedUser->getPassword(), $storedUser->getRoles(), $storedUser->isEnabled(), $storedUser->isAccountNonExpired(), $storedUser->isCredentialsNonExpired() && $storedUser->getPassword() === $user->getPassword(), $storedUser->isAccountNonLocked());
    }

    public function supportsClass($class)
    {
        return User::class === $class || UserWithoutEquatable::class === $class;
    }
}
