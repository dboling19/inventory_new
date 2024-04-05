<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderLine;
use App\Repository\ItemRepository;
use App\Repository\LocationRepository;
use App\Repository\TransactionRepository;
use App\Repository\ItemLocationRepository;
use App\Repository\TermsRepository;
use App\Repository\VendorRepository;
use App\Repository\StatusRepository;
use Datetime;
use Datetimezone;


class PurchaseOrderFixtures extends Fixture implements DependentFixtureInterface
{
  public function __construct(
    private EntityManagerInterface $em,
    private ItemRepository $item_repo,
    private LocationRepository $loc_repo,
    private TransactionRepository $trans_repo,
    private ItemLocationRepository $item_loc_repo,
    private VendorRepository $vendor_repo,
    private TermsRepository $terms_repo,
    private StatusRepository $status_repo,
  ) { }


  public function load(ObjectManager $manager): void
  {
    $min = strtotime((new datetime('+1 week', new datetimezone('america/indiana/indianapolis')))->format('Y-m-d'));
    $max = strtotime((new datetime('+1 month', new datetimezone('america/indiana/indianapolis')))->format('Y-m-d'));

    $cupboard_items = array('Coffee', 'Sugar', 'Chocolate', 'Salt', 'Ketchup', 'Cookies', 'Crackers', 'Chips', 'Mustard', 'Candy', 'Apple', 'Orange', 'Lemon', 'Lime', 'description', 'politics', 'replacement', 'reception', 'discussion', 'driver', 'housing', 'payment', 'employer', 'customer', 'basket', 'cell', 'tongue');
    $shelf_items = array('Coffee', 'Sugar', 'Chocolate', 'Salt', 'Ketchup', 'Cookies', 'Crackers', 'Chips', 'Mustard', 'Candy', 'Apple', 'Orange', 'Lemon', 'Lime', 'description', 'politics', 'replacement', 'reception', 'discussion', 'driver', 'housing', 'payment', 'employer', 'customer', 'basket', 'cell', 'tongue');
    $fridge_items = array('Milk', 'Juice', 'Eggs', 'Cream', 'selection', 'climate', 'variation', 'garbage', 'outcome', 'college', 'diamond', 'guidance', 'arrival', 'mom', 'recipe', 'construction', 'appointment', 'sir', 'leadership', 'blood', 'inspection', 'paper', 'grocery', 'person', 'explanation', 'refrigerator', 'marketing', 'unit', 'perception', 'dirt', 'disaster', 'breath', 'media', 'buyer', 'penalty', 'satisfaction', 'writing', 'people', 'health', 'currency', 'complaint');
    $freezer_items = array('Ice Cream', 'Ice', 'Chicken', 'Hamburger', 'Steak', 'Cake');
    $item_categories = [$cupboard_items, $shelf_items, $fridge_items, $freezer_items];
    foreach ($item_categories as $key=>$item_category)
    {
      $po = new PurchaseOrder;
      $po->setPoDate($this->random_date($min,$max));
      $po->setPoVendor($this->vendor_repo->find('AMA123'));
      $po_total_cost = 0;

      // get location_name for finding item_loc later
      // put here to reduce cycles
      switch ($key)
      {
        case 0:
          $loc_code = 'CUP123';
          $whs_code = 'WHSIN';
          break;
        case 1:
          $loc_code = 'SHE123';
          $whs_code = 'WHSIL';
          break;
        case 2:
          $loc_code = 'FRI123';
          $whs_code = 'WHSS';
          break;
        case 3:
          $loc_code = 'FRE123';
          $whs_code = 'WHSOH';
          break;
      }
      foreach ($item_category as $item_name)
      {
        
        $item = $this->item_repo->find(substr($item_name, 0,4) . '123');
        $po_line = new PurchaseOrderLine;
        $po_line->setItem($item);
        $po_line->setPoStatus(1);
        $qty_ordered = mt_rand(1,30);
        $po_line->setQtyOrdered($qty_ordered);
        $po_line->setQtyReceived($qty_ordered - mt_rand(0,$qty_ordered));
        $po_line->setQtyRejected($qty_ordered - $po_line->getQtyReceived());
        $po_line->setPoDueDate(new datetime('+4 weeks', new datetimezone('america/indiana/indianapolis')));
        $po_line->setPoReceivedDate(new datetime('now', new datetimezone('america/indiana/indianapolis')));
        $po_line->setPoReceived(1);
        $po_line->setPoPaid(1);
        $po_line->setItemQuantity($po_line->getQtyReceived());
        $item_cost = mt_rand(10,100)/10;
        $po_line->setItemCost($item_cost);
        $po_total_cost += $item_cost;
        $po_line->setPo($po);
        $manager->persist($po_line);

        // Handle receiving items for item quantities
        $item_loc = $this->item_loc_repo->findOneBy(['item' => $item->getItemCode(), 'location' => $loc_code, 'warehouse' => $whs_code]);
        $item_loc->setItemQty($po_line->getQtyReceived());

      }
      $po->setPoTotalCost($po_total_cost);
      $po->setPoReceived(1);
      $po->setPoPaid(1);
      $po->setPoFreight(mt_rand(10,100)/10);
      $po->setPoStatus($this->status_repo->find('APPR'));
      $po->setPoShipCode('ASD123');
      $po->setPoTerms($this->terms_repo->find('D02'));
      $manager->persist($po);
    }

    $manager->flush();
  }

  public function random_date($min, $max)
  {
    // Convert back to desired date format
    return new datetime(date('Y-m-d', mt_rand($min, $max)), new datetimezone('america/indiana/indianapolis'));
  }


  public function getDependencies()
  {
    return [
      ItemFixtures::class,
      UnitFixtures::class,
      LocationFixtures::class,
      VendorFixtures::class,
      WarehouseFixtures::class,
      StatusFixtures::class,
    ];
  }
}
