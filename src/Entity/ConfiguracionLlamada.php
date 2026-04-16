<?php

namespace App\Entity;

use App\Enum\TipoInteraccion;
use App\Enum\TipoUsuario;
use App\Repository\ConfiguracionLlamadaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfiguracionLlamadaRepository::class)]
class ConfiguracionLlamada
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: TipoUsuario::class)]
    private ?TipoUsuario $tipoLlamada = null;

    #[ORM\Column(enumType: TipoInteraccion::class)]
    private ?TipoInteraccion $tipoInteraccion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $prompt = null;

    #[ORM\Column(nullable: true)]
    private ?array $opcionDesplegable = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipoLlamada(): ?TipoUsuario
    {
        return $this->tipoLlamada;
    }

    public function setTipoLlamada(TipoUsuario $tipoLlamada): static
    {
        $this->tipoLlamada = $tipoLlamada;

        return $this;
    }

    public function getTipoInteraccion(): ?TipoInteraccion
    {
        return $this->tipoInteraccion;
    }

    public function setTipoInteraccion(TipoInteraccion $tipoInteraccion): static
    {
        $this->tipoInteraccion = $tipoInteraccion;

        return $this;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): static
    {
        $this->prompt = $prompt;

        return $this;
    }

    public function getOpcionDesplegable(): ?array
    {
        return $this->opcionDesplegable;
    }

    public function setOpcionDesplegable(?array $opcionDesplegable): static
    {
        $this->opcionDesplegable = $opcionDesplegable;

        return $this;
    }
}
