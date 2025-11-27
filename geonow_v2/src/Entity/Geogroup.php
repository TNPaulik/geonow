<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GeogroupsRepository")
 */
class Geogroup
{
    public static $RESOLVE = 0;
    public static $CONFIRM = 1;

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
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="geogroups")
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Location", mappedBy="geogroup")
     */
    private $locations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Geooption", mappedBy="geogroup")
     */
    private $options;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="joinedGroups")
     */
    private $users;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="adminGroups")
     * @ORM\JoinTable(name="user_groupadmin")
     */
    private $admins;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    public function __construct()
    {
        $this->locations = new ArrayCollection();
        $this->options = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->admins = new ArrayCollection();
    }

    public function __toString()
    {
        return ("
            id => $this->id,
            name => $this->name
        ");
    }

    public function getUserCount() {
        return count($this->users);
    }

    public function getImgUrl() {
        if (file_exists($this->getRootDir() . '/img/g/' . $this->id . '.jpg'))
            return '/img/g/' . $this->id . '.jpg';
        else
            return '/img/defaults/g.jpg';
    }

    public function getUrl() {
        return '/geogroup/show/' . $this->id;
    }

    public function getImgFile() {
        return (__DIR__ . '/../../public/img/g/'.$this->id.'.jpg');
    }

    public function getRootDir() {
        return (__DIR__ . '/../../public');
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

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection|Location[]
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    /**
     * @return Collection|Location[]
     */
    public function getLocationsGood(): Collection
    {
        foreach ($this->locations AS $key => $location) {
            if ($location->getStatus() !== Location::$ACTIVE) {
                unset($this->locations[$key]);
            }
        }
        return $this->locations;
    }

    public function addLocation(Location $location): self
    {
        if (!$this->locations->contains($location)) {
            $this->locations[] = $location;
            $location->setGeogroup($this);
        }

        return $this;
    }

    public function removeLocation(Location $location): self
    {
        if ($this->locations->contains($location)) {
            $this->locations->removeElement($location);
            // set the owning side to null (unless already changed)
            if ($location->getGeogroup() === $this) {
                $location->setGeogroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Geooption[]
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(Geooption $option): self
    {
        if (!$this->options->contains($option)) {
            $this->options[] = $option;
            $option->setGeogroup($this);
        }

        return $this;
    }

    public function removeOption(Geooption $option): self
    {
        if ($this->options->contains($option)) {
            $this->options->removeElement($option);
            // set the owning side to null (unless already changed)
            if ($option->getGeogroup() === $this) {
                $option->setGeogroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addJoinedGroup($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeJoinedGroup($this);
        }

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password, UserPasswordHasherInterface $passwordHasher): self
    {
        $this->password = $passwordHasher->hashPassword($this, $password);

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getAdmins(): Collection
    {
        return $this->admins;
    }

    public function addAdmin(User $admin): self
    {
        if (!$this->admins->contains($admin)) {
            $this->admins[] = $admin;
            $admin->addAdminGroup($this);
        }

        return $this;
    }

    public function removeAdmin(User $admin): self
    {
        if ($this->admins->contains($admin)) {
            $this->admins->removeElement($admin);
            $admin->removeAdminGroup($this);
        }

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }
}
