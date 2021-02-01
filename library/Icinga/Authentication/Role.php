<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

class Role
{
    /**
     * Name of the role
     *
     * @var string
     */
    protected $name;

    /**
     * The role from which to inherit privileges
     *
     * @var Role
     */
    protected $parent;

    /**
     * The roles to which privileges are inherited
     *
     * @var Role[]
     */
    protected $children;

    /**
     * Permissions of the role
     *
     * @var string[]
     */
    protected $permissions = [];

    /**
     * Refusals of the role
     *
     * @var string[]
     */
    protected $refusals = [];

    /**
     * Restrictions of the role
     *
     * @var string[]
     */
    protected $restrictions = [];

    /**
     * Get the name of the role
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the role
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the role from which privileges are inherited
     *
     * @return Role
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the role from which to inherit privileges
     *
     * @param Role $parent
     *
     * @return $this
     */
    public function setParent(Role $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get the roles to which privileges are inherited
     *
     * @return Role[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set the roles to which inherit privileges
     *
     * @param Role[] $children
     *
     * @return $this
     */
    public function setChildren(array $children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Add a role to which inherit privileges
     *
     * @param Role $role
     *
     * @return $this
     */
    public function addChild(Role $role)
    {
        $this->children[] = $role;

        return $this;
    }

    /**
     * Get the permissions of the role
     *
     * @return  string[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Set the permissions of the role
     *
     * @param   string[]    $permissions
     *
     * @return  $this
     */
    public function setPermissions(array $permissions)
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * Get the refusals of the role
     *
     * @return string[]
     */
    public function getRefusals()
    {
        return $this->refusals;
    }

    /**
     * Set the refusals of the role
     *
     * @param array $refusals
     *
     * @return $this
     */
    public function setRefusals(array $refusals)
    {
        $this->refusals = $refusals;

        return $this;
    }

    /**
     * Get the restrictions of the role
     *
     * @param   string  $name   Optional name of the restriction
     *
     * @return  string[]|null
     */
    public function getRestrictions($name = null)
    {
        $restrictions = $this->restrictions;

        if ($name === null) {
            return $restrictions;
        }

        if (isset($restrictions[$name])) {
            return $restrictions[$name];
        }

        return null;
    }

    /**
     * Set the restrictions of the role
     *
     * @param   string[]    $restrictions
     *
     * @return  $this
     */
    public function setRestrictions(array $restrictions)
    {
        $this->restrictions = $restrictions;

        return $this;
    }

    /**
     * Whether this role grants the given permission
     *
     * @param string $permission
     * @param bool $ignoreParent    Only evaluate the role's own permissions
     *
     * @return bool
     */
    public function grants($permission, $ignoreParent = false)
    {
        foreach ($this->permissions as $grantedPermission) {
            if ($this->match($grantedPermission, $permission)) {
                return true;
            }
        }

        if (! $ignoreParent && $this->getParent() !== null) {
            return $this->getParent()->grants($permission);
        }

        return false;
    }

    /**
     * Whether this role denies the given permission
     *
     * @param string $permission
     * @param bool $ignoreParent    Only evaluate the role's own refusals
     *
     * @return bool
     */
    public function denies($permission, $ignoreParent = false)
    {
        foreach ($this->refusals as $refusedPermission) {
            if ($this->match($refusedPermission, $permission)) {
                return true;
            }
        }

        if (! $ignoreParent && $this->getParent() !== null) {
            return $this->getParent()->denies($permission);
        }

        return false;
    }

    /**
     * Get whether the role expression matches the required permission
     *
     * @param string $roleExpression
     * @param string $requiredPermission
     *
     * @return bool
     */
    protected function match($roleExpression, $requiredPermission)
    {
        if ($roleExpression === '*' || $roleExpression === $requiredPermission) {
            return true;
        }

        $requiredWildcard = strpos($requiredPermission, '*');
        if ($requiredWildcard !== false) {
            if (($grantedWildcard = strpos($roleExpression, '*')) !== false) {
                $wildcard = min($requiredWildcard, $grantedWildcard);
            } else {
                $wildcard = $requiredWildcard;
            }
        } else {
            $wildcard = strpos($roleExpression, '*');
        }

        if ($wildcard !== false && $wildcard > 0) {
            if (substr($requiredPermission, 0, $wildcard) === substr($roleExpression, 0, $wildcard)) {
                return true;
            }
        } elseif ($requiredPermission === $roleExpression) {
            return true;
        }

        return false;
    }
}
