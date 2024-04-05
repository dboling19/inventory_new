<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Location;
use App\Repository\ItemRepository;
use App\Repository\LocationRepository;
use App\Repository\TransactionRepository;
use App\Repository\ItemLocationRepository;
use App\Repository\WarehouseRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Form\LocationType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LocationController extends AbstractController
{
  public function __construct(
    private EntityManagerInterface $em,
    private ItemRepository $item_repo,
    private LocationRepository $loc_repo,
    private TransactionRepository $trans_repo,
    private ItemLocationRepository $item_loc_repo,
    private WarehouseRepository $whs_repo,
    private PaginatorInterface $paginator,
    private RequestStack $request_stack,
    private ValidatorInterface $validator,
  ) { }

  
  /**
   * Function to display all locations in the system
   * 
   * @author Daniel Boling
   */
  #[Route('/location/list/', name:'loc_list')]
  public function loc_list(Request $request): Response
  {
    if (isset($request->query->all()['loc_code']))
    {
      $loc = $this->loc_repo->find($request->query->all()['loc_code']);
    } else {
      $loc = new Location;
    }
    $loc_form = $this->createForm(LocationType::class, $loc);
    $loc_thead = [
      'loc_code' => 'Loc Code',
      'loc_desc' => 'Loc Desc',
      'loc_notes' => 'Loc Notes',
      'item_total_qty' => 'Item Total Qty.',
    ];
    $result = $this->loc_repo->findAll();
    $result = $this->paginator->paginate($result, $request->query->getInt('loc_page', 1), 100, ['pageParamterName' => 'loc_page', 'sortParameterName' => 'loc_sort', 'sortDirectionParameterName' => 'loc_direction']);
    $normalized_locations = [];
    foreach ($result->getItems() as $item)
    {
      $normalized_locations[] = [
        'loc_code' => $item->getLocCode(),
        'loc_desc' => $item->getLocDesc(),
        'loc_notes' => $item->getLocNotes(),
        'item_total_qty' => $item->getItemQty(),
      ];
    }
    $result->setItems($normalized_locations);

    return $this->render('location/loc_list.html.twig', [
      'locations' => $result,
      'loc_thead' => $loc_thead,
      'form' => $loc_form,
    ]);
  }


  /**
   * Search for location using loc_code.
   * Should be the standard route for querying for locations
   * 
   * @author Daniel Boling
   */
  #[Route('/location/search/', name:'loc_search')]
  public function loc_search(Request $request): Response
  {
    $loc_form = $this->createForm(LocationType::class);
    $loc_form->handleRequest($request);
    $loc = $loc_form->getData();
    if (!$loc->getLocCode()) { return $this->redirectToRoute('loc_list'); }

    return $this->redirectToRoute('loc_list', [
      'loc_code' => $loc->getLocCode(),
    ]); 
  }


  /**
   * Creates location from loc_list page
   * 
   * @author Daniel Boling
   */
  #[Route('/location/new/', name:'loc_new')]
  public function loc_new(Request $request): Response
  {
    $loc_form = $this->createForm(LocationType::class);
    $loc_form->handleRequest($request);
    $loc = $loc_form->getData();
    if (!$loc->getLocCode()) { return $this->redirectToRoute('loc_list'); }

    return $this->redirectToRoute('loc_list', [
      'loc_code' => $loc->getLocCode(),
    ]);
  }


  /**
   * Modifies location details and redirects back to view location
   * 
   * @author Daniel Boling
   */
  #[Route('/location/save/', name:'loc_save')]
  public function loc_save(Request $request): Response
  {
    $loc_form = $this->createForm(LocationType::class);
    $loc_form->handleRequest($request);
    $loc = $loc_form->getData();
    if (!$loc_form->isValid())
    {
      $this->addFlash('error', 'Error: Invalid Submission - Location not updated');
      return $this->redirectToRoute('loc_search', ['loc_code' => $loc->getLocCode()]);
    }
    if ($this->loc_repo->find($loc->getLocCode())) {
      return $this->redirectToRoute('loc_modify', ['loc' => $loc], 307);
    } else {
      return $this->redirectToRoute('loc_create', ['loc' => $loc], 307);
    }
  }

  /**
 * Handle location creation
 * 
 * @author Daniel Boling
 */
  #[Route('/location/create/', name:'loc_create')]
  public function loc_create(Request $request): Response
  {
    $loc_form = $this->createForm(LocationType::class);
    $loc_form->handleRequest($request);
    $loc = $loc_form->getData();
    $errors = $this->validator->validate($loc);
    dd($errors);
    if ($errors > 0)
    {
      $this->addFlash('error', $errors);
    }
    $this->em->persist($loc);
    $this->em->flush();
    $this->addFlash('success', 'Location Created');
    return $this->redirectToRoute('loc_list', ['loc_code' => $loc->getLocCode()]);
  }


  /**
   * Handle item modification
   * 
   * @author Daniel Boling
   */
  #[Route('/location/modify/', name:'loc_modify')]
  public function loc_modify(Request $request): Response
  {
    $loc_form = $this->createForm(LocationType::class);
    $loc_form->handleRequest($request);
    $loc = $loc_form->getData();
    $this->em->merge($loc);
    $this->em->flush();
    $this->addFlash('success', 'Location Updated');
    return $this->redirectToRoute('loc_list', ['loc_code' => $loc->getLocCode()]);
  }


  /**
   * Deletes location if no entites are under it and redirects back to show locations
   * 
   * @author Daniel Boling
   */
  #[Route('/location/delete/', name:'loc_delete')]
  public function loc_delete(Request $request): Response
  {
    $loc_form = $this->createForm(LocationType::class);
    $loc_form->handleRequest($request);
    $loc = $loc_form->getData();
    $loc = $this->loc_repo->find($loc->getLocCode());
    if($loc->getItemQty() == 0 or $loc->getItemQty() == NULL)
    {
      $this->em->remove($loc);
      $this->em->flush();
      return $this->redirectToRoute('loc_list');
      $this->addFlash('success', 'Location removed.');
    } else {
      $this->addFlash('error', 'Location cannot be deleted.  Contains items.');
      return $this->redirectToRoute('loc_list', ['loc_code' => $loc->getLocCode()]);
    }
  }


  /**
   * Fetches selected location's items for template fragment
   * 
   * @author Daniel Boling
   */
  public function loc_items_list(Request $request, ?string $loc_code, ?int $item_page = 1): Response
  {
    $loc = $this->loc_repo->find($loc_code);
    $item_thead = [
      'item_code' => 'Item Code',
      'item_desc' => 'Item Desc',
      'item_unit' => 'Item Unit',
      'item_notes' => 'Item Notes',
      'item_exp_date' => 'Item Exp. Date',
      'item_qty' => 'Item Total Qty.',
    ];
    // to autofill form fields, or leave them null.
    $result = $this->item_repo->findByLoc($loc_code);
    $result = $this->paginator->paginate($result, $item_page, 10, ['pageParameterName' => 'item_page', 'sortParameterName' => 'item_sort', 'sortDirectionParameterName' => 'item_direction']);
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
    return $this->render('location/item_table.html.twig', [
      'items' => $result,
      'item_thead' => $item_thead,
    ]);
  }

}


// EOF