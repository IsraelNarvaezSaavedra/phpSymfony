<?php

namespace App\Entity;

use App\Repository\CocheRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CocheRepository::class)]
class Coche
{
    #[Groups(['coche:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['coche:read'])]
    #[ORM\Column(length: 255)]
    private ?string $marca = null;

    #[Groups(['coche:read'])]
    #[ORM\Column(length: 255)]
    private ?string $modelo = null;

    #[Groups(['coche:read'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $precio = null;

    
    #[ORM\Column()]
    private ?string $fotoCoche = 'default.jpg';

    /**
     * @var Collection<int, Valoracion>
     */
    #[ORM\OneToMany(targetEntity: Valoracion::class, mappedBy: 'coche', orphanRemoval: true)]
    private Collection $valoracion;

    public function __construct()
    {
        $this->valoracion = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMarca(): ?string
    {
        return $this->marca;
    }

    public function setMarca(string $marca): static
    {
        $this->marca = $marca;

        return $this;
    }

    public function getModelo(): ?string
    {
        return $this->modelo;
    }

    public function setModelo(string $modelo): static
    {
        $this->modelo = $modelo;

        return $this;
    }

    public function getPrecio(): ?string
    {
        return $this->precio;
    }

    public function setPrecio(string $precio): static
    {
        $this->precio = $precio;

        return $this;
    }

    public function getFotoCoche(): ?string
    {
        return $this->fotoCoche;
    }

    public function setFotoCoche(string $fotoCoche): static
    {
        $this->fotoCoche = $fotoCoche;

        return $this;
    }

    #[Groups(['coche:read'])]
    public function getUrlFotoCoche(): ?string
    {
        return '/uploads/coches/' . ($this->getFotoCoche() ?: 'default.png');
    }

    /**
     * @return Collection<int, Valoracion>
     */
    public function getValoracion(): Collection
    {
        return $this->valoracion;
    }

    public function getLikes(): int
    {
        $cont = 0;
        $valoraciones = $this->getValoracion();
        foreach ($valoraciones as $like) {
            if ($like->getValor() > 0) {
                $cont++;
            }
        }
        return $cont;
    }

    public function getDislikes(): int
    {
        $cont = 0;
        $valoraciones = $this->getValoracion();
        foreach ($valoraciones as $dislike) {
            if ($dislike->getValor() < 0) {
                $cont++;
            }
        }
        return $cont;
    }

    public function addValoracion(Valoracion $valoracion): static
    {
        if (!$this->valoracion->contains($valoracion)) {
            $this->valoracion->add($valoracion);
            $valoracion->setCoche($this);
        }

        return $this;
    }

    public function removeValoracion(Valoracion $valoracion): static
    {
        if ($this->valoracion->removeElement($valoracion)) {
            // set the owning side to null (unless already changed)
            if ($valoracion->getCoche() === $this) {
                $valoracion->setCoche(null);
            }
        }

        return $this;
    }
}
