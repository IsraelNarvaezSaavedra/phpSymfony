<?php

namespace App\Entity;

use App\Repository\ValoracionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ValoracionRepository::class)]
#[ORM\UniqueConstraint(name: 'un_voto_por_usuario', columns: ['usuario_id', 'coche_id'])]
class Valoracion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'valoracion')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Usuario $usuario = null;

    #[ORM\ManyToOne(inversedBy: 'valoracion')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coche $coche = null;

    #[ORM\Column]
    private ?int $valor = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getCoche(): ?Coche
    {
        return $this->coche;
    }

    public function setCoche(?Coche $coche): static
    {
        $this->coche = $coche;

        return $this;
    }

    public function getValor(): ?int
    {
        return $this->valor;
    }

    public function setValor(int $valor): static
    {
        $this->valor = $valor;

        return $this;
    }
}
