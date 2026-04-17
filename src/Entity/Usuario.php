<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\Rol;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['username'], message: 'Este nombre de usuario ya esta en uso')]
#[UniqueEntity(fields: ['email'], message: 'Este correo ya esta en uso')]
#[UniqueEntity(fields: ['telefono'], message: 'Este teléfono ya esta en uso')]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $telefono = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: "string", enumType: Rol::class)]
    private ?Rol $rol = Rol::USER;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column]
    private string $fotoPerfil = 'default.jpg';

    /**
     * @var Collection<int, Valoracion>
     */
    #[ORM\OneToMany(targetEntity: Valoracion::class, mappedBy: 'usuario', orphanRemoval: true)]
    private Collection $valoracion;

    public function __construct()
    {
        $this->valoracion = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRol(): ?Rol
    {
        return $this->rol;
    }

    public function setRol(?Rol $rol): static
    {
        $this->rol = $rol;

        return $this;
    }

    public function getUserIdentifier(): string
{
    return $this->email;
}

public function getRoles(): array
{
    return [$this->rol->value];
}

public function eraseCredentials(): void
{
}

public function isVerified(): bool
{
    return $this->isVerified;
}

public function setIsVerified(bool $isVerified): static
{
    $this->isVerified = $isVerified;

    return $this;
}

public function getFotoPerfil(): ?string
    {
        return $this->fotoPerfil;
    }

    public function setFotoPerfil(string $fotoPerfil): static
    {
        $this->fotoPerfil = $fotoPerfil;

        return $this;
    }

    /**
     * @return Collection<int, Valoracion>
     */
    public function getValoracion(): Collection
    {
        return $this->valoracion;
    }

    public function addValoracion(Valoracion $valoracion): static
    {
        if (!$this->valoracion->contains($valoracion)) {
            $this->valoracion->add($valoracion);
            $valoracion->setUsuario($this);
        }

        return $this;
    }

    public function removeValoracion(Valoracion $valoracion): static
    {
        if ($this->valoracion->removeElement($valoracion)) {
            // set the owning side to null (unless already changed)
            if ($valoracion->getUsuario() === $this) {
                $valoracion->setUsuario(null);
            }
        }

        return $this;
    }
}
