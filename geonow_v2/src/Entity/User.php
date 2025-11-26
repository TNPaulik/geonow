<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields="name", message="Name is already taken.")
 */
class User
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hash;

    private static $hashlen = 255;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Geogroup", mappedBy="user")
     */
    private $geogroups;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Location", mappedBy="user")
     */
    private $locations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Location", mappedBy="solver")
     */
    private $locationsSolved;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Geogroup")
     */
    private $activeGroup;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Geogroup", inversedBy="users")
     */
    private $joinedGroups;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Geogroup", inversedBy="admins", fetch="EAGER")
     * @ORM\JoinTable(name="user_groupadmin")
     */
    private $adminGroups;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $points;

    public function __construct()
    {
        $this->geogroups = new ArrayCollection();
        $this->locations = new ArrayCollection();
        $this->joinedGroups = new ArrayCollection();
        $this->adminGroups = new ArrayCollection();
    }

    public function __toString()
    {
        return ("
            id => $this->id,
            Name => $this->name
        ");
    }

    public function hasAccessTo(Geogroup $group) {
        if ($this->name == 'TNP') {
            return 'w';
        }

        if ($group->getUser()->getId() == $this->getId()) {
            return 'w';
        }

        if ($group->getAdmins()->contains($this)) {
            return 'w';
        }

        if ($group->getUsers()->contains($this)) {
            return 'r';
        }

        return false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return Collection|Geogroup[]
     */
    public function getGeogroups(): Collection
    {
        return $this->geogroups;
    }

    public function addGeogroup(Geogroup $geogroup): self
    {
        if (!$this->geogroups->contains($geogroup)) {
            $this->geogroups[] = $geogroup;
            $geogroup->setUser($this);
        }

        return $this;
    }

    public function removeGeogroup(Geogroup $geogroup): self
    {
        if ($this->geogroups->contains($geogroup)) {
            $this->geogroups->removeElement($geogroup);
            // set the owning side to null (unless already changed)
            if ($geogroup->getUser() === $this) {
                $geogroup->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Location[]
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): self
    {
        if (!$this->locations->contains($location)) {
            $this->locations[] = $location;
            $location->setUser($this);
        }

        return $this;
    }

    public function removeLocation(Location $location): self
    {
        if ($this->locations->contains($location)) {
            $this->locations->removeElement($location);
            // set the owning side to null (unless already changed)
            if ($location->getUser() === $this) {
                $location->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Location[]
     */
    public function getLocationsSolved(): Collection
    {
        return $this->locationsSolved;
    }

    public function addLocationSolved(Location $locationSolved): self
    {
        if (!$this->locationsSolved->contains($locationSolved)) {
            $this->locationsSolved[] = $locationSolved;
            $locationSolved->setUser($this);
        }

        return $this;
    }

    public function removeLocationSolved(Location $locationSolved): self
    {
        if ($this->locationsSolved->contains($locationSolved)) {
            $this->locationsSolved->removeElement($locationSolved);
            // set the owning side to null (unless already changed)
            if ($locationSolved->getUser() === $this) {
                $locationSolved->setUser(null);
            }
        }

        return $this;
    }

    public static function getCurrentUser($em) {

        $repository = $em->getRepository(User::class);

        $hash = self::getCookie();

        $user = $repository->findOneBy([
            'hash' => $hash
        ]);

        if($user === null) {
            $user = new User();
            $user->name = '';
            $user->password = '';
            $user->email = '';
            $user->hash = $hash;
            $user->setActiveGroup(
                $em->getRepository(Geogroup::class)->findAll()[0]
            );
            $em->persist($user);
            $em->flush();
        }

        if (!$user->getActiveGroup()) {
            $repository = $em->getRepository(Geogroup::class);
            $group = $repository->findOneBy([
            ]);

            $user->setActiveGroup($group);
            $em->getManager()->persist($user);
            $em->getManager()->flush();
        }

        return $user;

    }

    private static function getCookie() {

        if (isset($_COOKIE['userhash']) && strlen($_COOKIE['userhash']) == self::$hashlen) {
            return $_COOKIE['userhash'];
        } else {
            $hash = self::generateRandomString(self::$hashlen);
            setcookie('userhash', $hash, (int) (time() + 3600 * 24 * 30 * 12 * 15), '/', '.geonow.org');
            return $hash;
        }
    }

    private static function generateRandomString($length = 10) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }

    public function getActiveGroup(): ?Geogroup
    {
        if (!$this->activeGroup) {

        }
        return $this->activeGroup;
    }

    public function setActiveGroup(?Geogroup $activeGroup): self
    {
        $this->activeGroup = $activeGroup;

        return $this;
    }

    /**
     * @return Collection|Geogroup[]
     */
    public function getJoinedGroups(): Collection
    {
        return $this->joinedGroups;
    }

    public function addJoinedGroup(Geogroup $joinedGroup): self
    {
        if (!$this->joinedGroups->contains($joinedGroup)) {
            $this->joinedGroups[] = $joinedGroup;
        }

        return $this;
    }

    public function hasJoinedGroup(Geogroup $joinedGroup): bool
    {
        return $this->joinedGroups->contains($joinedGroup);
    }

    public function removeJoinedGroup(Geogroup $joinedGroup): self
    {
        if ($this->joinedGroups->contains($joinedGroup)) {
            $this->joinedGroups->removeElement($joinedGroup);
        }

        return $this;
    }

    /**
     * @return Collection|Geogroup[]
     */
    public function getAdminGroups(): Collection
    {
        return $this->adminGroups;
    }

    public function addAdminGroup(Geogroup $adminGroup): self
    {
        if (!$this->adminGroups->contains($adminGroup)) {
            $this->adminGroups[] = $adminGroup;
        }

        return $this;
    }

    public function hasAdminGroup(Geogroup $adminGroup): bool
    {
        return $this->adminGroups->contains($adminGroup);
    }

    public function removeAdminGroup(Geogroup $adminGroup): self
    {
        if ($this->adminGroups->contains($adminGroup)) {
            $this->adminGroups->removeElement($adminGroup);
        }

        return $this;
    }

    public function addPoints(?int $points, $em): self
    {
        $this->setPoints($this->getPoints()+$points);
        $em->persist($this);
        $em->flush();
        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): self
    {
        $this->points = $points;

        return $this;
    }
}
