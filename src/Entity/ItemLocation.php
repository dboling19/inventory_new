<?php

namespace App\Entity;

use App\Repository\ItemLocationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass:ItemLocationRepository::class)]
class ItemLocation
{
  #[ORM\Id]
  #[ORM\ManyToOne(targetEntity:Item::class, inversedBy:"item_loc", cascade:['persist'])]
  #[ORM\JoinColumn(nullable:false, name:'item_code', referencedColumnName:'item_code')]
  #[MaxDepth(1)]
  private ?Item $item;

  #[ORM\Id]
  #[ORM\ManyToOne(targetEntity:Location::class, inversedBy:'item_loc', cascade:['persist'])]
  #[ORM\JoinColumn(nullable:false, name:'loc_code', referencedColumnName:'loc_code')]
  #[MaxDepth(1)]
  private ?Location $location;

  #[ORM\Id]
  #[ORM\ManyToOne(targetEntity:Warehouse::class, inversedBy:"item_loc", cascade:['persist'])]
  #[ORM\JoinColumn(nullable:false, name:'whs_code', referencedColumnName:'whs_code')]
  #[MaxDepth(1)]
  private ?Warehouse $warehouse;

  #[ORM\Column(type:'integer', nullable:false)]
  #[Assert\PositiveOrZero]
  private ?int $item_qty = 0;

  private $trans;

  public function getItemQty(): ?int
  {
      return $this->item_qty;
  }

  public function setItemQty(int $item_qty): static
  {
      $this->item_qty = $item_qty;

      return $this;
  }

  public function getItem(): ?Item
  {
      return $this->item;
  }

  public function setItem(?Item $item): static
  {
      $this->item = $item;

      return $this;
  }

  public function getLocation(): Location
  {
      return $this->location;
  }

  public function setLocation(Location $location): static
  {
      $this->location = $location;

      return $this;
  }

  public function getWarehouse(): Warehouse
  {
      return $this->warehouse;
  }

  public function setWarehouse(Warehouse $warehouse): static
  {
      $this->warehouse = $warehouse;

      return $this;
  }

  public function setQtyChange(?string $qty_change): self
  {
      $this->trans = new Transaction();
      $this->getItem()->addItemTrans($this->trans);
      $this->trans->setTransDatetime(new \DateTime());
      $this->trans->setTransQtyChange($qty_change);

      return $this;
  }

}
