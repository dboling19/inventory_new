<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Item;
use App\Repository\ItemRepository;
use App\Repository\LocationRepository;
use App\Repository\TransactionRepository;
use App\Repository\ItemLocationRepository;
use App\Repository\UnitRepository;
use App\Repository\WarehouseRepository;
use App\Service\TransactionService;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\ItemType;
use Datetime;
use Datetimezone;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ItemController extends AbstractController
{
  public function __construct(
    private EntityManagerInterface $em,
    private ItemRepository $item_repo,
    private LocationRepository $loc_repo,
    private TransactionRepository $trans_repo,
    private ItemLocationRepository $item_loc_repo,
    private UnitRepository $unit_repo,
    private WarehouseRepository $whs_repo,
    private TransactionService $trans_service,
    private PaginatorInterface $paginator,
    private ValidatorInterface $validator,
  ) { }

  /**
   * Function to display all items in the system
   * 
   * @author Daniel Boling
   */
  #[Route('/', name:'item_list')]
  public function list_items(Request $request): Response
  {
    if (isset($request->query->all()['item_code']))
    {
      $item = $this->item_repo->find($request->query->all()['item_code']);
    } else {
      $item = new Item;
    }
    $item_form = $this->createForm(ItemType::class, $item);
    $item_thead = [
      'item_code' => 'Item Code',
      'item_desc' => 'Item Desc',
      'item_unit' => 'Item Unit',
      'item_notes' => 'Item Notes',
      'item_exp_date' => 'Item Exp. Date',
      'item_qty' => 'Item Total Qty.',
    ];
    // to autofill form fields, or leave them null.
    $result = $this->item_repo->findAll();
    $result = $this->paginator->paginate($result, $request->query->getInt('page', 1), 100);
    $normalized_items = [];
    foreach ($result->getItems() as $item)
    {
      $normalized_items[] = [
        'item_code' => $item->getItemCode(),
        'item_desc' => $item->getItemDesc(),
        'item_notes' => $item->getItemNotes(),
        'item_exp_date' => $item->getItemExpDate(),
        'item_qty' => $item->getItemQty(),
        'item_unit' => $item->getItemUnit()->getUnitCode(),
      ];
    }
    $result->setItems($normalized_items);
    return $this->render('item/list_items.html.twig', [
      'items' => $result,
      'item_thead' => $item_thead,
      'form' => $item_form,
    ]);
  }


  /**
   * Mainly a placeholder for the search functionality.
   * 
   * @author Daniel Boling
   */
  #[Route('/item/search/', name:'item_search')]
  public function item_search(Request $request): Response
  {
    $item_form = $this->createForm(ItemType::class);
    $item_form->handleRequest($request);
    $item = $item_form->getData();
    if (!$item->getItemCode()) { return $this->redirectToRoute('item_list'); }

    return $this->redirectToRoute('item_list', [
      'item_code' => $item->getItemCode(),
    ]);
  }

  
  /**
   * Handle item form submission.
   * Redirect to creation or modification fuctions
   * 
   * @author Daniel Boling
   */
  #[Route('/item/save/', name:'item_save')]
  public function item_save(Request $request): Response
  {
    $item_form = $this->createForm(ItemType::class);
    $item_form->handleRequest($request);
    $item = $item_form->getData();
    if (
      $item_form->get('item_code') == null ||
      $item_form->get('item_desc') == null ||
      $item_form->get('item_unit') == null
    ) {
      $this->addFlash('error', 'Error: Invalid Submission - Item not updated');
      return $this->redirectToRoute('item_list', ['item_code' => $item->getItemCode()]);
    }
    if ($this->item_repo->find($item->getItemCode())) {
      return $this->redirectToRoute('item_modify', ['item' => $item], 307);
    } else {
      return $this->redirectToRoute('item_create', ['item' => $item], 307);
    }
  }


  /**
   * Handle item modification
   * 
   * @author Daniel Boling
   */
  #[Route('/item/modify/', name:'item_modify')]
  public function item_modify(Request $request): Response
  {
    $item_form = $this->createForm(ItemType::class);
    $item_form->handleRequest($request);
    $item = $item_form->getData();
    $this->em->merge($item);
    $this->em->flush();
    $this->addFlash('success', 'Item Updated');
    return $this->redirectToRoute('item_list', ['item_code' => $item->getItemCode()]);
  }


  /**
   * Handle item creation
   * 
   * @author Daniel Boling
   */
  #[Route('/item/create/', name:'item_create')]
  public function item_create(Request $request): Response
  {
    $item_form = $this->createForm(ItemType::class);
    $item_form->handleRequest($request);
    $item = $item_form->getData();
    $this->em->persist($item);
    $this->em->flush();
    $this->addFlash('success', 'Item Created');
    return $this->redirectToRoute('item_list', ['item_code' => $item->getItemCode()]);
  }


  /**
   * Delete item only if quantity = 0
   * 
   * @author Daniel Boling
   */
  #[Route('/item/delete/', name:'item_delete')]
  public function delete_item(Request $request)
  {
    $item_form = $this->createForm(ItemType::class);
    $item_form->handleRequest($request);
    $item = $item_form->getData();
    $item = $this->item_repo->find($item->getItemCode());

    if ($item->getItemQty() !== 0)
    {
      return $this->redirectToRoute('item_list', ['item_code' => $item->getItemCode()]);
    }

    $this->em->remove($item);
    $this->em->flush();
    $this->addFlash('success', 'Removed Item Entry');
    return $this->redirectToRoute('item_list');
  }

}


// EOF