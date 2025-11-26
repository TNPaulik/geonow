<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\File;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LocationRepository")
 */
class Location
{
    public static $ACTIVE = 0;
    public static $RESOLVED = 1;
    public static $CONFIRMED = 2;
    public static $INACTIVE = 3;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="decimal", precision=13, scale=10)
     */
    private $ltd;

    /**
     * @ORM\Column(type="decimal", precision=13, scale=10)
     */
    private $lgt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Geogroup", inversedBy="locations")
     */
    private $geogroup;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="locations")
     */
    private $user;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Geooption", inversedBy="locations")
     */
    private $geooptions;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="locationsSolved")
     */
    private $solver;

    /**
     * @ORM\Column(type="smallint")
     */
    private $status;

    public function __construct()
    {
        $this->geooptions = new ArrayCollection();
    }

    public function __toString()
    {
        return ("
            id => $this->id,
            ltd => $this->ltd,
            lgt => $this->lgt
        ");
    }

    public function json()
    {
        $o = new \stdClass();
        $o->id = $this->id;
        $o->ltd = $this->ltd;
        $o->lgt = $this->lgt;
        $o->group = $this->getGeogroup()->getName();

        $a = [
            'id' => $this->id,
            'ltd' => $this->ltd,
            'lgt' => $this->lgt,
            'image' => '/media/cache/makegroupsmall'.$this->getImgUrl(),
            'group' => $this->getGeogroup()->getName() . ' - ' . $this->getGeogroup()->getText(),
            'groupurl' => '/geogroup/show/' . $this->getGeogroup()->getId(),
            'groupimage' => '/media/cache/makegroupsmall'.$this->getGeogroup()->getImgUrl()
        ];

        if (isset($this->distance))
            $a['distance'] = $this->distance;

        return $a;
    }

    public function getMd5() {
        return md5($this->id);
    }

    public function getImgUrl() {
        if (file_exists($this->getImgFile()))
            return '/img/l/' . $this->getMd5() . '.jpg';
        else if (file_exists($this->getGeogroup()->getImgFile()))
            return $this->getGeogroup()->getImgUrl();
        else
            return '/img/defaults/l.jpg';
    }

    public function getImgUrlSolved() {
        if (file_exists($this->getRootDir() . '/' . $this->getMd5() . '_resolved.jpg'))
            return '/img/l/' . $this->getMd5() . '_resolved.jpg';
        else
            return '/img/defaults/l.jpg';
    }

    public function getRootDir() {
        return (__DIR__ . '/../../public/img/l');
    }

    public function getImgFile() {
        return (__DIR__ . '/../../public/img/l/'.$this->getMd5().'.jpg');
    }

    public function getId()
    {
        return $this->id;
    }

    public function getLtd(): ?string
    {
        return $this->ltd;
    }

    public function setLtd(string $ltd): self
    {
        $this->ltd = $ltd;

        return $this;
    }

    public function getLgt(): ?string
    {
        return $this->lgt;
    }

    public function setLgt(string $lgt): self
    {
        $this->lgt = $lgt;

        return $this;
    }

    public function getGeogroup(): ?Geogroup
    {
        return $this->geogroup;
    }

    public function setGeogroup(?Geogroup $geogroup): self
    {
        $this->geogroup = $geogroup;

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
     * @return Collection|Geooption[]
     */
    public function getGeooptions(): Collection
    {
        return $this->geooptions;
    }

    public function addGeooption(Geooption $geooption): self
    {
        if (!$this->geooptions->contains($geooption)) {
            $this->geooptions[] = $geooption;
        }

        return $this;
    }

    public function removeGeooption(Geooption $geooption): self
    {
        if ($this->geooptions->contains($geooption)) {
            $this->geooptions->removeElement($geooption);
        }

        return $this;
    }

    public function getSolver(): ?User
    {
        return $this->solver;
    }

    public function setSolver(?User $solver): self
    {
        $this->solver = $solver;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
