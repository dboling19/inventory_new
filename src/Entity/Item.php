<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ItemRepository;

#[ORM\Entity(repositoryClass:ItemRepository::class)]
class Item
{
  #[ORM\Id]
  #[ORM\Column(type:'string', length:8, nullable:false)]
  private ?string $item_code = null;

  #[ORM\Column(type:'string', length:50, nullable:false)]
  private ?string $item_desc = null;

  #[ORM\Column(type:'text', nullable:true)]
  private ?string $item_notes = null;

  #[ORM\OneToMany(targetEntity:Transaction::class, mappedBy:"item", cascade:["persist", "remove"])]
  #[ORM\InverseJoinColumn(nullable:false, name:'item_code', referencedColumnName:'item_code')]
  #[ORM\InverseJoinColumn(nullable:false, name:'loc_code', referencedColumnName:'loc_code')]
  private $item_trans;

  #[ORM\OneToMany(targetEntity:ItemLocation::class, mappedBy:"item", cascade:["persist", "remove"])]
  #[ORM\JoinColumn(name:'item_code', referencedColumnName:'item_code')]
  #[ORM\JoinColumn(name:'loc_code', referencedColumnName:'loc_code')]
  #[ORM\JoinColumn(name:'whs_code', referencedColumnName:'whs_code')]
  private $item_loc = null;

  #[ORM\Column(type:'datetime', nullable:true)]
  private $item_exp_date = null;

  #[ORM\ManyToOne(inversedBy: 'items')]
  #[ORM\JoinColumn(name: 'unit_code', referencedColumnName: 'unit_code', nullable: false)]
  private ?Unit $item_unit = null;

  private Collection $locations;

  private Collection $warehouses;

  private Collection $po_lines;


  public function __construct()
  {
    $this->item_trans = new ArrayCollection();
    $this->item_loc = new ArrayCollection();
  }

  public function getItemCode(): string
  {
    return $this->item_code;
  }

  public function setItemCode(string $item_code): static
  {
    $this->item_code = strtoupper($item_code);

    return $this;
  }

  public function getItemDesc(): ?string
  {
    return $this->item_desc;
  }

  public function setItemDesc(string $item_desc): static
  {
    $this->item_desc = $item_desc;

    return $this;
  }

  public function getItemNotes(): ?string
  {
    return $this->item_notes;
  }

  public function setItemNotes(?string $item_notes): static
  {
    $this->item_notes = $item_notes;

    return $this;
  }

  public function getItemQty(): ?int
  {
    $item_qty = 0;
    foreach ($this->item_loc as $item_loc)
    {
      $item_qty += $item_loc->getItemQty();
    }
    return $item_qty;
  }

  /**
   * @return Collection<int, Transaction>
   */
  public function getItemTrans(): Collection
  {
    return $this->item_trans;
  }

  public function addItemTrans(Transaction $item_trans): static
  {
    if (!$this->item_trans->contains($item_trans)) {
      $this->item_trans->add($item_trans);
      $item_trans->setItem($this);
    }

    return $this;
  }

  public function removeItemTrans(Transaction $item_trans): static
  {
    if ($this->item_trans->removeElement($item_trans)) {
      // set the owning side to null (unless already changed)
      if ($item_trans->getItem() === $this) {
        $item_trans->setItem(null);
      }
    }

    return $this;
  }

  /**
   * @return Collection<int, ItemLocation>
   */
  public function getItemLoc(): Collection
  {
    return $this->item_loc;
  }

  public function addItemLoc(ItemLocation $itemLoc): static
  {
    if (!$this->item_loc->contains($itemLoc)) {
      $this->item_loc->add($itemLoc);
      $itemLoc->setItem($this);
    }

    return $this;
  }

  public function removeItemLoc(ItemLocation $itemLoc): static
  {
    if ($this->item_loc->removeElement($itemLoc)) {
      // set the owning side to null (unless already changed)
      if ($itemLoc->getItem() === $this) {
        $itemLoc->setItem(null);
      }
    }

    return $this;
  }

  public function getItemExpDate(): ?\DateTimeInterface
  {
    return $this->item_exp_date;
  }

  public function setItemExpDate(?\DateTimeInterface $item_exp_date): static
  {
    $this->item_exp_date = $item_exp_date;

    return $this;
  }

  public function getItemUnit(): ?Unit
  {
    return $this->item_unit;
  }

  public function setItemUnit(?Unit $item_unit): static
  {
    $this->item_unit = $item_unit;

    return $this;
  }

  /**
   * @return Collection<int, Location>
   */
  public function getLocations(): Collection
  {
    $this->locations = new ArrayCollection;
    foreach ($this->item_loc as $item_loc) 
    {
      $this->locations->add($item_loc->getLocation());
    }

    return $this->locations;
  }

  /**
   * @return Collection<int, Warehouse>
   */
  public function getWarehouses(): Collection
  {
    $this->warehouses = new ArrayCollection;
    foreach ($this->item_loc as $item_loc)
    {
      $this->warehouses->add($item_loc->getWarehouse());
    }

    return $this->warehouses;
  }

  public function addLocation(Location $location, Warehouse $warehouse): static
  {
    if (!$this->getLocations()->contains($location) && !$this->getWarehouses()->contains($warehouse)) {
      $item_loc = new ItemLocation;
      $item_loc->setLocation($location);
      $item_loc->setWarehouse($warehouse);
      $item_loc->setItem($this);
      $this->item_loc->add($item_loc);
    }

    return $this;
  }
  
  /**
   * @return Collection<int, PO_Lines>
   */
  public function getPOLines(): Collection
  {
    $this->po_lines = new ArrayCollection;
    

    return $this->po_lines;
  }

}
