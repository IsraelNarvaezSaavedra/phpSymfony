<?php

namespace App\Entity;

use App\Repository\MensajeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MensajeRepository::class)]
class Mensaje
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $telefono = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $texto = null;

    #[ORM\Column(length: 255)]
    private ?string $rol = null;

    #[ORM\Column(length: 255)]
    private ?string $estado = 'inicio';

    #[ORM\Column]
    private ?\DateTimeImmutable $fecha = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): static
    {
        $this->telefono = $telefono;

        return $this;
    }

    public function getTexto(): ?string
    {
        return $this->texto;
    }

    public function setTexto(string $texto): static
    {
        $this->texto = $texto;

        return $this;
    }

    public function getRol(): ?string
    {
        return $this->rol;
    }

    public function setRol(string $rol): static
    {
        $this->rol = $rol;

        return $this;
    }

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function __toString()
    {
        $formato = $this->rol . ": " . $this->texto;
        return $formato;
    }

    public function getFecha(): ?\DateTimeImmutable
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeImmutable $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

}
